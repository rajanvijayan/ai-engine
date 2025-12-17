<?php

namespace AIEngine;

use AIEngine\Providers\Gemini;
use AIEngine\Providers\MetaLlama;
use AIEngine\Providers\Groq;
use AIEngine\Providers\ProviderInterface;
use AIEngine\Knowledge\KnowledgeBase;

class AIEngine
{
    protected $provider;
    protected $config;
    protected $logger;
    protected ?KnowledgeBase $knowledgeBase = null;

    /**
     * Default models for each provider.
     */
    protected static $defaultModels = [
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
    public function __construct( $apiKey, $config = [] )
    {
        $this->config = array_merge([
            'provider' => 'gemini',
            'model' => null,
            'timeout' => 60,
            'enable_logging' => false
        ], $config);

        // Use default model if not specified
        $provider = strtolower($this->config['provider']);
        if ($this->config['model'] === null) {
            $this->config['model'] = self::$defaultModels[$provider] ?? 'gemini-2.0-flash';
        }

        // Create the appropriate provider
        $this->provider = $this->createProvider($provider, $apiKey, $this->config['model'], $this->config['timeout']);
    }

    /**
     * Create a provider instance.
     *
     * @param string $providerName The provider name ('gemini', 'meta', 'groq')
     * @param string $apiKey The API key
     * @param string $model The model to use
     * @param int $timeout Request timeout
     * @return ProviderInterface The provider instance
     */
    protected function createProvider($providerName, $apiKey, $model, $timeout)
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
     *
     * @param string $provider The provider name ('gemini', 'meta', 'groq')
     * @param string $apiKey The API key
     * @param array $config Additional configuration
     * @return AIEngine
     */
    public static function create($provider, $apiKey, $config = [])
    {
        $config['provider'] = $provider;
        return new self($apiKey, $config);
    }

    /**
     * Switch to a different provider.
     *
     * @param string $providerName The provider name ('gemini', 'meta', 'groq')
     * @param string $apiKey The API key for the new provider
     * @param string|null $model The model to use (optional)
     * @return void
     */
    public function switchProvider($providerName, $apiKey, $model = null)
    {
        if ($model === null) {
            $model = self::$defaultModels[strtolower($providerName)] ?? 'gemini-2.0-flash';
        }
        
        $this->provider = $this->createProvider($providerName, $apiKey, $model, $this->config['timeout']);
        $this->config['provider'] = $providerName;
        $this->config['model'] = $model;
        $this->log("Switched to provider: " . $this->provider->getName());
    }

    /**
     * Generate content using the current provider (single prompt, no history).
     *
     * @param string $prompt The prompt to send to the AI service
     * @return string|array The generated content or error response
     */
    public function generateContent($prompt)
    {
        $this->log("Generating content with provider: " . $this->provider->getName());
        
        $result = $this->provider->generateContent($prompt);
        
        if (is_array($result) && isset($result['error'])) {
            $this->log("Error: " . $result['error']);
        }
        
        return $result;
    }

    /**
     * Send a message in a conversation context (maintains history).
     *
     * @param string $message The user message to send
     * @return string|array The AI response or error response
     */
    public function chat($message)
    {
        $this->log("Sending chat message with provider: " . $this->provider->getName());
        
        $result = $this->provider->sendMessage($message);
        
        if (is_array($result) && isset($result['error'])) {
            $this->log("Error: " . $result['error']);
        }
        
        return $result;
    }

    /**
     * Start a new conversation (clears history).
     *
     * @return void
     */
    public function newConversation()
    {
        $this->provider->startNewConversation();
        $this->log("Started new conversation");
    }

    /**
     * Get the current conversation history.
     *
     * @return array The conversation history
     */
    public function getHistory()
    {
        return $this->provider->getConversationHistory();
    }

    /**
     * Set a system instruction for the conversation.
     *
     * @param string $instruction The system instruction
     * @return void
     */
    public function setSystemInstruction($instruction)
    {
        $this->provider->setSystemInstruction($instruction);
        $this->log("System instruction set");
    }

    // ==========================================
    // Knowledge Base Methods (RAG Support)
    // ==========================================

    /**
     * Add knowledge from a URL.
     * Currently only supported by Groq provider.
     *
     * @param string $url The URL to fetch and add to knowledge base
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
     * @param array $urls Array of URLs to fetch
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
     *
     * @param string $text The text content
     * @param string $source Source identifier
     * @param string|null $title Optional title
     * @return bool True if added successfully
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
     *
     * @return KnowledgeBase
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
     *
     * @return void
     */
    public function clearKnowledge(): void
    {
        if ($this->knowledgeBase !== null) {
            $this->knowledgeBase->clear();
            $this->log("Knowledge base cleared");
        }
        
        // Clear from provider if supported
        if ($this->provider instanceof Groq) {
            $this->provider->clearKnowledgeBase();
        }
    }

    /**
     * Check if knowledge base has content.
     *
     * @return bool True if knowledge base has documents
     */
    public function hasKnowledge(): bool
    {
        return $this->knowledgeBase !== null && !$this->knowledgeBase->isEmpty();
    }

    /**
     * Save knowledge base to file.
     *
     * @param string $path File path
     * @return bool True if saved successfully
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
     *
     * @param string $path File path
     * @return bool True if loaded successfully
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
     *
     * @return void
     */
    protected function ensureKnowledgeBase(): void
    {
        if ($this->knowledgeBase === null) {
            $this->knowledgeBase = new KnowledgeBase();
        }
    }

    /**
     * Sync knowledge base to provider.
     *
     * @return void
     */
    protected function syncKnowledgeToProvider(): void
    {
        // Currently only Groq supports knowledge base
        if ($this->provider instanceof Groq && $this->knowledgeBase !== null) {
            $this->provider->setKnowledgeBase($this->knowledgeBase);
        }
    }

    // ==========================================
    // Provider Management
    // ==========================================

    /**
     * Set a new provider.
     *
     * @param ProviderInterface $provider The provider to use
     */
    public function setProvider(ProviderInterface $provider)
    {
        $this->provider = $provider;
        $this->log("Provider changed to: " . $provider->getName());
    }

    /**
     * Get the current provider.
     *
     * @return ProviderInterface The current provider
     */
    public function getProvider()
    {
        return $this->provider;
    }

    /**
     * Get the current provider name.
     *
     * @return string The provider name
     */
    public function getProviderName()
    {
        return $this->provider->getName();
    }

    /**
     * Check if the current provider is properly configured.
     *
     * @return bool True if configured
     */
    public function isConfigured()
    {
        return $this->provider->isConfigured();
    }

    /**
     * Get configuration value.
     *
     * @param string $key The configuration key
     * @param mixed $default Default value if key not found
     * @return mixed The configuration value
     */
    public function getConfig($key, $default = null)
    {
        return isset($this->config[$key]) ? $this->config[$key] : $default;
    }

    /**
     * Set configuration value.
     *
     * @param string $key The configuration key
     * @param mixed $value The value to set
     */
    public function setConfig($key, $value)
    {
        $this->config[$key] = $value;
    }

    /**
     * Enable or disable logging.
     *
     * @param bool $enable Whether to enable logging
     */
    public function enableLogging($enable = true)
    {
        $this->config['enable_logging'] = $enable;
    }

    /**
     * Set a custom logger.
     *
     * @param callable $logger The logger function
     */
    public function setLogger($logger)
    {
        $this->logger = $logger;
    }

    /**
     * Log a message if logging is enabled.
     *
     * @param string $message The message to log
     * @param string $level The log level (info, error, warning)
     */
    protected function log($message, $level = 'info')
    {
        if (!$this->config['enable_logging']) {
            return;
        }

        if ($this->logger && is_callable($this->logger)) {
            call_user_func($this->logger, $message, $level);
        } else {
            $timestamp = date('Y-m-d H:i:s');
            error_log("[{$timestamp}] AIEngine [{$level}]: {$message}");
        }
    }

    /**
     * Validate prompt before processing.
     *
     * @param string $prompt The prompt to validate
     * @return bool True if valid
     */
    public function validatePrompt($prompt)
    {
        return is_string($prompt) && !empty(trim($prompt));
    }

    /**
     * Get available providers.
     *
     * @return array Available provider names
     */
    public function getAvailableProviders()
    {
        return ['gemini', 'meta', 'groq'];
    }

    /**
     * Get default models for all providers.
     *
     * @return array Provider => default model mapping
     */
    public static function getDefaultModels()
    {
        return self::$defaultModels;
    }

    /**
     * Get available models for a specific provider.
     *
     * @param string $provider The provider name
     * @return array List of available models
     */
    public static function getModelsForProvider($provider)
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
                return [
                    'gemini-2.0-flash',
                    'gemini-2.0-flash-lite',
                    'gemini-2.5-flash',
                    'gemini-2.5-pro',
                ];
        }
    }
}