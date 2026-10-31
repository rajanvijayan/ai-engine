<?php

declare(strict_types=1);

namespace AIEngine\Providers;

use AIEngine\Exceptions\ApiException;
use AIEngine\Exceptions\ConfigurationException;
use AIEngine\Knowledge\KnowledgeBase;
use AIEngine\Response;

/**
 * Anthropic API Provider
 *
 * Uses the Anthropic Messages API to access Claude models
 * Get your API key from: https://platform.claude.com/settings/keys
 */
class Anthropic implements ProviderInterface
{
    public const DEFAULT_MODEL = 'claude-opus-5-5';
    public const API_VERSION = '2026-10-21';
    public const FALLBACK_BETA = 'server-side-fallback-2026-07-01';

    /**
     * Models that accept the server-side refusal fallback parameter.
     */
    protected const FALLBACK_MODELS = [
        'claude-fable-5-1',
        'claude-opus-5-5',
        'claude-opus-5',
        'claude-sonnet-5-5',
    ];

    protected string $api_key;
    protected string $model;
    protected int $timeout;
    protected int $maxTokens = 16000;
    protected bool $fallbacksEnabled = true;
    protected array $conversationHistory = [];
    protected ?string $systemInstruction = null;
    protected string $api_url = 'https://api.anthropic.com/v1/messages';
    protected ?KnowledgeBase $knowledgeBase = null;
    protected int $maxKnowledgeChars = 8000;

    public function __construct(string $api_key, string $model = self::DEFAULT_MODEL, int $timeout = 60)
    {
        $this->api_key = $api_key;
        $this->model = $model;
        $this->timeout = $timeout;
    }

    public function isConfigured(): bool
    {
        return !empty($this->api_key);
    }

    public function getName(): string
    {
        return 'Anthropic';
    }

    public function getModel(): string
    {
        return $this->model;
    }

    public function setModel(string $model): void
    {
        $this->model = $model;
    }

    public function setTimeout(int $timeout): void
    {
        $this->timeout = $timeout;
    }

    /**
     * Set the maximum number of tokens Claude may generate per response.
     */
    public function setMaxTokens(int $maxTokens): void
    {
        $this->maxTokens = $maxTokens;
    }

    public function getMaxTokens(): int
    {
        return $this->maxTokens;
    }

    /**
     * Enable or disable server-side refusal fallbacks.
     *
     * When enabled (default), a request declined by a model's safety classifiers
     * is automatically retried on a fallback model chosen by the API.
     */
    public function enableFallbacks(bool $enable = true): void
    {
        $this->fallbacksEnabled = $enable;
    }

    public function setKnowledgeBase(?KnowledgeBase $knowledgeBase): void
    {
        $this->knowledgeBase = $knowledgeBase;
    }

    public function getKnowledgeBase(): ?KnowledgeBase
    {
        return $this->knowledgeBase;
    }

    public function hasKnowledgeBase(): bool
    {
        return $this->knowledgeBase !== null && !$this->knowledgeBase->isEmpty();
    }

    public function setMaxKnowledgeChars(int $maxChars): void
    {
        $this->maxKnowledgeChars = $maxChars;
    }

    public function clearKnowledgeBase(): void
    {
        if ($this->knowledgeBase !== null) {
            $this->knowledgeBase->clear();
        }
    }

    /**
     * Extract text from a Messages API response by joining all text blocks.
     */
    protected function getTextFromResponse(string $json): ?string
    {
        $data = json_decode($json, true);

        if (!isset($data['content']) || !is_array($data['content'])) {
            return null;
        }

        $texts = [];
        foreach ($data['content'] as $block) {
            if (is_array($block) && ($block['type'] ?? null) === 'text' && isset($block['text'])) {
                $texts[] = $block['text'];
            }
        }

        if (empty($texts)) {
            return null;
        }

        return implode('', $texts);
    }

    protected function validatePrompt(string $prompt): bool
    {
        return !empty(trim($prompt)) && strlen($prompt) <= 30000;
    }

    /**
     * Build the system prompt with knowledge base context.
     */
    protected function buildSystemPrompt(): ?string
    {
        $parts = [];

        if ($this->systemInstruction !== null) {
            $parts[] = $this->systemInstruction;
        }

        if ($this->knowledgeBase !== null && !$this->knowledgeBase->isEmpty()) {
            $knowledgeContext = $this->knowledgeBase->buildContext($this->maxKnowledgeChars);
            if (!empty($knowledgeContext)) {
                $parts[] = "\n" . $knowledgeContext;
                $parts[] = 'Answer questions based on the knowledge base above. If the answer is not in the knowledge base, say so.';
            }
        }

        if (empty($parts)) {
            return null;
        }

        return implode("\n\n", $parts);
    }

    /**
     * Build the request payload.
     */
    protected function buildRequestData(array $messages): array
    {
        $data = [
            'model' => $this->model,
            'max_tokens' => $this->maxTokens,
            'messages' => $messages,
        ];

        $systemPrompt = $this->buildSystemPrompt();
        if ($systemPrompt !== null) {
            $data['system'] = $systemPrompt;
        }

        if ($this->usesFallbacks()) {
            $data['fallbacks'] = 'default';
        }

        return $data;
    }

    protected function usesFallbacks(): bool
    {
        return $this->fallbacksEnabled && in_array($this->model, self::FALLBACK_MODELS, true);
    }

    public function generateContent(string $prompt): Response
    {
        if (!$this->isConfigured()) {
            throw new ConfigurationException('Provider not properly configured');
        }

        if (!$this->validatePrompt($prompt)) {
            throw new ConfigurationException('Invalid prompt: must be a non-empty string under 30000 characters');
        }

        return $this->makeApiRequest([['role' => 'user', 'content' => $prompt]]);
    }

    public function sendMessage(string $message): Response
    {
        if (!$this->isConfigured()) {
            throw new ConfigurationException('Provider not properly configured');
        }

        if (!$this->validatePrompt($message)) {
            throw new ConfigurationException('Invalid message: must be a non-empty string under 30000 characters');
        }

        $this->conversationHistory[] = [
            'role' => 'user',
            'content' => $message,
        ];

        try {
            $response = $this->makeApiRequest($this->conversationHistory);
        } catch (ApiException $e) {
            array_pop($this->conversationHistory);
            throw $e;
        }

        $this->conversationHistory[] = [
            'role' => 'assistant',
            'content' => $response->getText(),
        ];

        return $response;
    }

    protected function makeApiRequest(array $messages): Response
    {
        $headers = "Content-Type: application/json\r\n"
                 . "x-api-key: {$this->api_key}\r\n"
                 . 'anthropic-version: ' . self::API_VERSION . "\r\n";

        if ($this->usesFallbacks()) {
            $headers .= 'anthropic-beta: ' . self::FALLBACK_BETA . "\r\n";
        }

        $options = [
            'http' => [
                'header'  => $headers,
                'method'  => 'POST',
                'content' => json_encode($this->buildRequestData($messages)),
                'timeout' => $this->timeout,
                'ignore_errors' => true,
            ],
        ];

        $context = stream_context_create($options);

        $errorMessage = null;
        set_error_handler(function (int $errno, string $errstr) use (&$errorMessage): bool {
            $errorMessage = $errstr;
            return true;
        });
        $response = file_get_contents($this->api_url, false, $context);
        restore_error_handler();

        if ($response === false) {
            throw new ApiException(
                'Error contacting API' . ($errorMessage ? ": {$errorMessage}" : ''),
                'Anthropic'
            );
        }

        $text = $this->getTextFromResponse($response);

        if ($text === null) {
            $errorData = json_decode($response, true);
            $apiError = $errorData['error']['message'] ?? null;
            if ($apiError === null && ($errorData['stop_reason'] ?? null) === 'refusal') {
                $apiError = 'Request declined by the model'
                    . (isset($errorData['stop_details']['category']) ? " ({$errorData['stop_details']['category']})" : '');
            }
            throw new ApiException(
                $apiError ?? 'Failed to parse API response',
                'Anthropic',
                $apiError
            );
        }

        $rawResponse = json_decode($response, true);
        return new Response($text, 'Anthropic', $this->model, is_array($rawResponse) ? $rawResponse : null);
    }

    public function startNewConversation(): void
    {
        $this->conversationHistory = [];
    }

    public function getConversationHistory(): array
    {
        return $this->conversationHistory;
    }

    public function setSystemInstruction(string $instruction): void
    {
        $this->systemInstruction = $instruction;
    }

    public function getSystemInstruction(): ?string
    {
        return $this->systemInstruction;
    }

    public function addToHistory(string $role, string $text): void
    {
        $this->conversationHistory[] = [
            'role' => $role,
            'content' => $text,
        ];
    }

    public static function getAvailableModels(): array
    {
        return [
            'claude-opus-5-5',
            'claude-sonnet-5-5',
            'claude-haiku-5-5',
            'claude-fable-5-1',
        ];
    }
}
