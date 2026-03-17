<?php

declare(strict_types=1);

namespace AIEngine;

use AIEngine\Providers\Gemini;
use AIEngine\Providers\MetaLlama;
use AIEngine\Providers\Groq;
use AIEngine\Providers\ProviderInterface;
use AIEngine\Knowledge\KnowledgeBase;
use AIEngine\Exceptions\AIEngineException;
use AIEngine\Exceptions\ConfigurationException;
use AIEngine\Exceptions\ApiException;

class AIEngine
{
    protected ProviderInterface $provider;
    protected array $config;
    /** @var callable|null */
    protected $logger = null;
    protected ?KnowledgeBase $knowledgeBase = null;

    /**
     * Default models for each provider.
     */
    protected static array $defaultModels = [
        'gemini' => 'gemini-2.0-flash',
        'meta' => 'Llama-4-Maverick-17B-128E-Instruct-FP8',
        'groq' => 'llama-3.3-70b-versatile',
    ];

    /**
     * Constructor.
     *
     * @param string $apiKey The API key for the provider
     * @param array $config Additional configuration options
     *                      - 'provider': 'gemini', 'meta', or 'groq' (default: 'gemini')
     *                      - 'model': Model name (optional, uses provider default)
     *                      - 'timeout': Request timeout in seconds (default: 60)
     *                      - 'enable_logging': Enable logging (default: false)
     */
    public function __construct(string $apiKey, array $config = [])
    {
        $this->config = array_merge([
            'provider' => 'gemini',
            'model' => null,
            'timeout' => 60,
            'enable_logging' => false
        ], $config);

        $provider = strtolower((string) $this->config['provider']);
        if ($this->config['model'] === null) {
            $this->config['model'] = self::$defaultModels[$provider] ?? 'gemini-2.0-flash';
        }

        $this->provider = $this->createProvider($provider, $apiKey, (string) $this->config['model'], (int) $this->config['timeout']);
    }

    /**
     * Create a provider instance.
     */
    protected function createProvider(string $providerName, string $apiKey, string $model, int $timeout): ProviderInterface
    {
        switch (strtolower($providerName)) {
            case 'meta':
            case 'llama':
            case 'meta-llama':
                return new MetaLlama($apiKey, $model, $timeout);

            case 'groq':
                return new Groq($apiKey, $model, $timeout);

            case 'gemini':
            default:
                return new Gemini($apiKey, $model, $timeout);
        }
    }

    /**
     * Static factory method to create AIEngine with a specific provider.
     */
    public static function create(string $provider, string $apiKey, array $config = []): self
    {
        $config['provider'] = $provider;
        return new self($apiKey, $config);
    }

    /**
     * Switch to a different provider.
     */
    public function switchProvider(string $providerName, string $apiKey, ?string $model = null): void
    {
        if ($model === null) {
            $model = self::$defaultModels[strtolower($providerName)] ?? 'gemini-2.0-flash';
        }

        $this->provider = $this->createProvider($providerName, $apiKey, $model, (int) $this->config['timeout']);
        $this->config['provider'] = $providerName;
        $this->config['model'] = $model;

        // Sync knowledge base to new provider
        if ($this->knowledgeBase !== null) {
            $this->syncKnowledgeToProvider();
        }

        $this->log("Switched to provider: " . $this->provider->getName());
    }

    /**
     * Generate content using the current provider (single prompt, no history).
     *
     * @throws ConfigurationException If not configured
     * @throws ApiException If API call fails
     */
    public function generateContent(string $prompt): Response
    {
        $this->log("Generating content with provider: " . $this->provider->getName());

        try {
            $result = $this->provider->generateContent($prompt);
            return $result;
        } catch (AIEngineException $e) {
            $this->log("Error: " . $e->getMessage(), 'error');
            throw $e;
        }
    }

    /**
     * Send a message in a conversation context (maintains history).
     *
     * @throws ConfigurationException If not configured
     * @throws ApiException If API call fails
     */
    public function chat(string $message): Response
    {
        $this->log("Sending chat message with provider: " . $this->provider->getName());

        try {
            $result = $this->provider->sendMessage($message);
            return $result;
        } catch (AIEngineException $e) {
            $this->log("Error: " . $e->getMessage(), 'error');
            throw $e;
        }
    }

    /**
     * Start a new conversation (clears history).
     */
    public function newConversation(): void
    {
        $this->provider->startNewConversation();
        $this->log("Started new conversation");
    }

    /**
     * Get the current conversation history.
     */
    public function getHistory(): array
    {
        return $this->provider->getConversationHistory();
    }

    /**
     * Set a system instruction for the conversation.
     */
    public function setSystemInstruction(string $instruction): void
    {
        $this->provider->setSystemInstruction($instruction);
        $this->log("System instruction set");
    }

    // ==========================================
    // Knowledge Base Methods (RAG Support)
    // ==========================================

    /**
     * Add knowledge from a URL.
     * Now supported by all providers.
     *
     * @return array{success: bool, error?: string, title?: string}
     */
    public function addKnowledgeFromUrl(string $url): array
    {
        $this->ensureKnowledgeBase();

        $result = $this->knowledgeBase->addUrl($url);

        if ($result['success']) {
            $this->log("Added knowledge from URL: {$url}");
            $this->syncKnowledgeToProvider();
        } else {
            $this->log("Failed to add knowledge from URL: {$url} - " . ($result['error'] ?? 'Unknown error'), 'error');
        }

        return $result;
    }

    /**
     * Add knowledge from multiple URLs.
     *
     * @return array{success: int, failed: int, results: array}
     */
    public function addKnowledgeFromUrls(array $urls): array
    {
        $this->ensureKnowledgeBase();

        $result = $this->knowledgeBase->addUrls($urls);

        $this->log("Added knowledge from {$result['success']} URLs ({$result['failed']} failed)");
        $this->syncKnowledgeToProvider();

        return $result;
    }

    /**
     * Add raw text as knowledge.
     */
    public function addKnowledgeText(string $text, string $source, ?string $title = null): bool
    {
        $this->ensureKnowledgeBase();

        $result = $this->knowledgeBase->addText($text, $source, $title);

        if ($result) {
            $this->log("Added text knowledge from: {$source}");
            $this->syncKnowledgeToProvider();
        }

        return $result;
    }

    /**
     * Get the knowledge base instance.
     */
    public function getKnowledgeBase(): KnowledgeBase
    {
        $this->ensureKnowledgeBase();
        return $this->knowledgeBase;
    }

    /**
     * Get knowledge base summary.
     *
     * @return array{count: int, sources: array, totalChars: int}
     */
    public function getKnowledgeSummary(): array
    {
        if ($this->knowledgeBase === null) {
            return ['count' => 0, 'sources' => [], 'totalChars' => 0];
        }
        return $this->knowledgeBase->getSummary();
    }

    /**
     * Clear all knowledge.
     */
    public function clearKnowledge(): void
    {
        if ($this->knowledgeBase !== null) {
            $this->knowledgeBase->clear();
            $this->log("Knowledge base cleared");
        }

        $this->provider->setKnowledgeBase(null);
    }

    /**
     * Check if knowledge base has content.
     */
    public function hasKnowledge(): bool
    {
        return $this->knowledgeBase !== null && !$this->knowledgeBase->isEmpty();
    }

    /**
     * Save knowledge base to file.
     */
    public function saveKnowledge(string $path): bool
    {
        if ($this->knowledgeBase === null) {
            return false;
        }
        return $this->knowledgeBase->save($path);
    }

    /**
     * Load knowledge base from file.
     */
    public function loadKnowledge(string $path): bool
    {
        $this->ensureKnowledgeBase();

        $result = $this->knowledgeBase->load($path);

        if ($result) {
            $this->log("Loaded knowledge from: {$path}");
            $this->syncKnowledgeToProvider();
        }

        return $result;
    }

    /**
     * Ensure knowledge base is initialized.
     */
    protected function ensureKnowledgeBase(): void
    {
        if ($this->knowledgeBase === null) {
            $this->knowledgeBase = new KnowledgeBase();
        }
    }

    /**
     * Sync knowledge base to provider (now supports all providers).
     */
    protected function syncKnowledgeToProvider(): void
    {
        if ($this->knowledgeBase !== null) {
            $this->provider->setKnowledgeBase($this->knowledgeBase);
        }
    }

    // ==========================================
    // Provider Management
    // ==========================================

    /**
     * Set a new provider.
     */
    public function setProvider(ProviderInterface $provider): void
    {
        $this->provider = $provider;
        $this->log("Provider changed to: " . $provider->getName());
    }

    /**
     * Get the current provider.
     */
    public function getProvider(): ProviderInterface
    {
        return $this->provider;
    }

    /**
     * Get the current provider name.
     */
    public function getProviderName(): string
    {
        return $this->provider->getName();
    }

    /**
     * Check if the current provider is properly configured.
     */
    public function isConfigured(): bool
    {
        return $this->provider->isConfigured();
    }

    /**
     * Get configuration value.
     *
     * @param mixed $default Default value if key not found
     * @return mixed The configuration value
     */
    public function getConfig(string $key, $default = null)
    {
        return $this->config[$key] ?? $default;
    }

    /**
     * Set configuration value.
     *
     * @param mixed $value The value to set
     */
    public function setConfig(string $key, $value): void
    {
        $this->config[$key] = $value;
    }

    /**
     * Enable or disable logging.
     */
    public function enableLogging(bool $enable = true): void
    {
        $this->config['enable_logging'] = $enable;
    }

    /**
     * Set a custom logger.
     */
    public function setLogger(callable $logger): void
    {
        $this->logger = $logger;
    }

    /**
     * Log a message if logging is enabled.
     */
    protected function log(string $message, string $level = 'info'): void
    {
        if (!$this->config['enable_logging']) {
            return;
        }

        if ($this->logger !== null && is_callable($this->logger)) {
            call_user_func($this->logger, $message, $level);
        } else {
            $timestamp = date('Y-m-d H:i:s');
            error_log("[{$timestamp}] AIEngine [{$level}]: {$message}");
        }
    }

    /**
     * Validate prompt before processing.
     */
    public function validatePrompt(string $prompt): bool
    {
        return !empty(trim($prompt));
    }

    /**
     * Get available providers.
     *
     * @return array Available provider names
     */
    public function getAvailableProviders(): array
    {
        return ['gemini', 'meta', 'groq'];
    }

    /**
     * Get default models for all providers.
     *
     * @return array Provider => default model mapping
     */
    public static function getDefaultModels(): array
    {
        return self::$defaultModels;
    }

    /**
     * Get available models for a specific provider.
     *
     * @return array List of available models
     */
    public static function getModelsForProvider(string $provider): array
    {
        switch (strtolower($provider)) {
            case 'meta':
            case 'llama':
            case 'meta-llama':
                return MetaLlama::getAvailableModels();

            case 'groq':
                return Groq::getAvailableModels();

            case 'gemini':
            default:
                return Gemini::getAvailableModels();
        }
    }
}
