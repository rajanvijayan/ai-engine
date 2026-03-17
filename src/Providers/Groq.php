<?php

declare(strict_types=1);

namespace AIEngine\Providers;

use AIEngine\Response;
use AIEngine\Knowledge\KnowledgeBase;
use AIEngine\Exceptions\ConfigurationException;
use AIEngine\Exceptions\ApiException;

/**
 * Groq API Provider
 *
 * Uses Groq's API to access Llama and other models with ultra-fast inference
 * Get your FREE API key from: https://console.groq.com
 */
class Groq implements ProviderInterface
{
    protected string $api_key;
    protected string $model;
    protected int $timeout;
    protected array $conversationHistory = [];
    protected ?string $systemInstruction = null;
    protected string $api_url = 'https://api.groq.com/openai/v1/chat/completions';
    protected ?KnowledgeBase $knowledgeBase = null;
    protected int $maxKnowledgeChars = 8000;

    public function __construct(string $api_key, string $model = 'llama-3.3-70b-versatile', int $timeout = 60)
    {
        $this->api_key = $api_key;
        $this->model = $model;
        $this->timeout = $timeout;
    }

    public function isConfigured(): bool
    {
        return !empty($this->api_key) && is_string($this->api_key);
    }

    public function getName(): string
    {
        return 'Groq';
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
     * Extract text from the OpenAI-compatible API response.
     */
    protected function getTextFromResponse(string $json): ?string
    {
        $data = json_decode($json, true);

        if (isset($data['choices']) && is_array($data['choices']) && !empty($data['choices'])) {
            $choice = $data['choices'][0];
            if (isset($choice['message']['content'])) {
                return $choice['message']['content'];
            }
        }

        return null;
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
                $parts[] = "Answer questions based on the knowledge base above. If the answer is not in the knowledge base, say so.";
            }
        }

        if (empty($parts)) {
            return null;
        }

        return implode("\n\n", $parts);
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

    public function generateContent(string $prompt): Response
    {
        if (!$this->isConfigured()) {
            throw new ConfigurationException('Provider not properly configured');
        }

        if (!$this->validatePrompt($prompt)) {
            throw new ConfigurationException('Invalid prompt: must be a non-empty string under 30000 characters');
        }

        $messages = [];

        $systemPrompt = $this->buildSystemPrompt();
        if ($systemPrompt !== null) {
            $messages[] = ['role' => 'system', 'content' => $systemPrompt];
        }

        $messages[] = ['role' => 'user', 'content' => $prompt];

        return $this->makeApiRequest($messages);
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
            'content' => $message
        ];

        $messages = [];
        $systemPrompt = $this->buildSystemPrompt();
        if ($systemPrompt !== null) {
            $messages[] = ['role' => 'system', 'content' => $systemPrompt];
        }
        $messages = array_merge($messages, $this->conversationHistory);

        try {
            $response = $this->makeApiRequest($messages);
        } catch (ApiException $e) {
            array_pop($this->conversationHistory);
            throw $e;
        }

        $this->conversationHistory[] = [
            'role' => 'assistant',
            'content' => $response->getText()
        ];

        return $response;
    }

    protected function makeApiRequest(array $messages): Response
    {
        $data = [
            'model' => $this->model,
            'messages' => $messages
        ];

        $options = [
            'http' => [
                'header'  => "Content-Type: application/json\r\n" .
                           "Authorization: Bearer {$this->api_key}\r\n",
                'method'  => 'POST',
                'content' => json_encode($data),
                'timeout' => $this->timeout,
                'ignore_errors' => true
            ]
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
                'Groq'
            );
        }

        $text = $this->getTextFromResponse($response);

        if ($text === null) {
            $errorData = json_decode($response, true);
            $apiError = $errorData['error']['message'] ?? null;
            throw new ApiException(
                $apiError ?? 'Failed to parse API response',
                'Groq',
                $apiError
            );
        }

        $rawResponse = json_decode($response, true);
        return new Response($text, 'Groq', $this->model, is_array($rawResponse) ? $rawResponse : null);
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
            'content' => $text
        ];
    }

    public static function getAvailableModels(): array
    {
        return [
            'llama-3.3-70b-versatile',
            'llama-3.1-8b-instant',
            'llama-3.2-1b-preview',
            'llama-3.2-3b-preview',
            'llama-3.2-11b-vision-preview',
            'llama-3.2-90b-vision-preview',
            'mixtral-8x7b-32768',
            'gemma2-9b-it',
            'whisper-large-v3',
            'whisper-large-v3-turbo',
        ];
    }
}
