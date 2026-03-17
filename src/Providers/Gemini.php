<?php

declare(strict_types=1);

namespace AIEngine\Providers;

use AIEngine\Exceptions\ApiException;
use AIEngine\Exceptions\ConfigurationException;
use AIEngine\Knowledge\KnowledgeBase;
use AIEngine\Response;

class Gemini implements ProviderInterface
{
    protected string $api_key;
    protected string $model;
    protected int $timeout;
    protected array $conversationHistory = [];
    protected ?string $systemInstruction = null;
    protected ?KnowledgeBase $knowledgeBase = null;
    protected int $maxKnowledgeChars = 8000;

    public function __construct(string $api_key, string $model = 'gemini-2.0-flash', int $timeout = 60)
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
        return 'Gemini';
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

    public function setKnowledgeBase(?KnowledgeBase $knowledgeBase): void
    {
        $this->knowledgeBase = $knowledgeBase;
    }

    public function hasKnowledgeBase(): bool
    {
        return $this->knowledgeBase !== null && !$this->knowledgeBase->isEmpty();
    }

    public function setMaxKnowledgeChars(int $maxChars): void
    {
        $this->maxKnowledgeChars = $maxChars;
    }

    /**
     * Extract text from the Gemini API response.
     */
    public function getTextFromParts(string $json): ?string
    {
        $data = json_decode($json, true);

        if (isset($data['candidates']) && is_array($data['candidates'])) {
            foreach ($data['candidates'] as $candidate) {
                if (isset($candidate['content']['parts']) && is_array($candidate['content']['parts'])) {
                    foreach ($candidate['content']['parts'] as $part) {
                        if (isset($part['text'])) {
                            return $part['text'];
                        }
                    }
                }
            }
        }

        return null;
    }

    protected function validatePrompt(string $prompt): bool
    {
        return !empty(trim($prompt)) && strlen($prompt) <= 30000;
    }

    /**
     * Build the system instruction including knowledge base context.
     */
    protected function buildSystemInstruction(): ?string
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

    public function generateContent(string $prompt): Response
    {
        if (!$this->isConfigured()) {
            throw new ConfigurationException('Provider not properly configured');
        }

        if (!$this->validatePrompt($prompt)) {
            throw new ConfigurationException('Invalid prompt: must be a non-empty string under 30000 characters');
        }

        $prompt = htmlspecialchars($prompt, ENT_QUOTES, 'UTF-8');

        $api_url = "https://generativelanguage.googleapis.com/v1beta/models/{$this->model}:generateContent";

        $data = [
            'contents' => [
                [
                    'parts' => [
                        ['text' => $prompt],
                    ],
                ],
            ],
        ];

        $systemInstruction = $this->buildSystemInstruction();
        if ($systemInstruction !== null) {
            $data['systemInstruction'] = [
                'parts' => [['text' => $systemInstruction]],
            ];
        }

        $options = [
            'http' => [
                'header'  => "Content-Type: application/json\r\n"
                           . "X-goog-api-key: {$this->api_key}\r\n",
                'method'  => 'POST',
                'content' => json_encode($data),
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
        $response = file_get_contents($api_url, false, $context);
        restore_error_handler();

        if ($response === false) {
            throw new ApiException(
                'Error contacting API' . ($errorMessage ? ": {$errorMessage}" : ''),
                'Gemini'
            );
        }

        $text = $this->getTextFromParts($response);

        if ($text === null) {
            $errorData = json_decode($response, true);
            $apiError = $errorData['error']['message'] ?? null;
            throw new ApiException(
                $apiError ?? 'Failed to parse API response',
                'Gemini',
                $apiError
            );
        }

        $rawResponse = json_decode($response, true);
        return new Response($text, 'Gemini', $this->model, is_array($rawResponse) ? $rawResponse : null);
    }

    public function sendMessage(string $message): Response
    {
        if (!$this->isConfigured()) {
            throw new ConfigurationException('Provider not properly configured');
        }

        if (!$this->validatePrompt($message)) {
            throw new ConfigurationException('Invalid message: must be a non-empty string under 30000 characters');
        }

        $message = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');

        $this->conversationHistory[] = [
            'role' => 'user',
            'parts' => [['text' => $message]],
        ];

        try {
            $response = $this->makeConversationRequest();
        } catch (ApiException $e) {
            array_pop($this->conversationHistory);
            throw $e;
        }

        $this->conversationHistory[] = [
            'role' => 'model',
            'parts' => [['text' => $response->getText()]],
        ];

        return $response;
    }

    protected function makeConversationRequest(): Response
    {
        $api_url = "https://generativelanguage.googleapis.com/v1beta/models/{$this->model}:generateContent";

        $data = [
            'contents' => $this->conversationHistory,
        ];

        $systemInstruction = $this->buildSystemInstruction();
        if ($systemInstruction !== null) {
            $data['systemInstruction'] = [
                'parts' => [['text' => $systemInstruction]],
            ];
        }

        $options = [
            'http' => [
                'header'  => "Content-Type: application/json\r\n"
                           . "X-goog-api-key: {$this->api_key}\r\n",
                'method'  => 'POST',
                'content' => json_encode($data),
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
        $response = file_get_contents($api_url, false, $context);
        restore_error_handler();

        if ($response === false) {
            throw new ApiException(
                'Error contacting API' . ($errorMessage ? ": {$errorMessage}" : ''),
                'Gemini'
            );
        }

        $text = $this->getTextFromParts($response);

        if ($text === null) {
            $errorData = json_decode($response, true);
            $apiError = $errorData['error']['message'] ?? null;
            throw new ApiException(
                $apiError ?? 'Failed to parse API response',
                'Gemini',
                $apiError
            );
        }

        $rawResponse = json_decode($response, true);
        return new Response($text, 'Gemini', $this->model, is_array($rawResponse) ? $rawResponse : null);
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
            'parts' => [['text' => $text]],
        ];
    }

    public static function getAvailableModels(): array
    {
        return [
            'gemini-2.0-flash',
            'gemini-2.0-flash-lite',
            'gemini-2.5-flash',
            'gemini-2.5-pro',
        ];
    }
}
