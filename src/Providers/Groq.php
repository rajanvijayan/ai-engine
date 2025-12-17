<?php
namespace AIEngine\Providers;

/**
 * Groq API Provider
 * 
 * Uses Groq's API to access Llama and other models with ultra-fast inference
 * Get your FREE API key from: https://console.groq.com
 */
class Groq implements ProviderInterface {

    protected $api_key;
    protected $model;
    protected $timeout;
    protected $conversationHistory = [];
    protected $systemInstruction = null;
    protected $api_url = 'https://api.groq.com/openai/v1/chat/completions';

    /**
     * Constructor.
     *
     * @param string $api_key The API key for authentication.
     * @param string $model The model to use (default: llama-3.3-70b-versatile).
     * @param int $timeout Request timeout in seconds (default: 60).
     */
    public function __construct($api_key, $model = 'llama-3.3-70b-versatile', $timeout = 60) {
        $this->api_key = $api_key;
        $this->model = $model;
        $this->timeout = $timeout;
    }

    /**
     * Validate the configuration (API key, etc.).
     *
     * @return bool True if configuration is valid
     */
    public function isConfigured() {
        return !empty($this->api_key) && is_string($this->api_key);
    }

    /**
     * Get the provider name.
     *
     * @return string The provider name
     */
    public function getName() {
        return 'Groq';
    }

    /**
     * Get the current model being used.
     *
     * @return string The current model name
     */
    public function getModel() {
        return $this->model;
    }

    /**
     * Set the model to use.
     *
     * @param string $model The model name
     */
    public function setModel($model) {
        $this->model = $model;
    }

    /**
     * Set the request timeout.
     *
     * @param int $timeout Timeout in seconds
     */
    public function setTimeout($timeout) {
        $this->timeout = $timeout;
    }

    /**
     * Extract text from the OpenAI-compatible API response.
     *
     * @param string $json The JSON response from the API.
     * @return string|null The extracted text or null if not found.
     */
    protected function getTextFromResponse($json) {
        $data = json_decode($json, true);

        if (isset($data['choices']) && is_array($data['choices']) && !empty($data['choices'])) {
            $choice = $data['choices'][0];
            if (isset($choice['message']['content'])) {
                return $choice['message']['content'];
            }
        }

        return null;
    }

    /**
     * Validate the prompt input.
     *
     * @param string $prompt The prompt to validate
     * @return bool True if prompt is valid
     */
    protected function validatePrompt($prompt) {
        return is_string($prompt) && !empty(trim($prompt)) && strlen($prompt) <= 30000;
    }

    /**
     * Generate content using a single prompt (no history).
     *
     * @param string $prompt The prompt to send to the API.
     * @return array|string The response data or an error message.
     */
    public function generateContent($prompt) {
        // Validate configuration
        if (!$this->isConfigured()) {
            return ['error' => 'Provider not properly configured'];
        }

        // Validate prompt
        if (!$this->validatePrompt($prompt)) {
            return ['error' => 'Invalid prompt: must be a non-empty string under 30000 characters'];
        }

        // Build messages array
        $messages = [];
        
        if ($this->systemInstruction !== null) {
            $messages[] = ['role' => 'system', 'content' => $this->systemInstruction];
        }
        
        $messages[] = ['role' => 'user', 'content' => $prompt];

        return $this->makeApiRequest($messages);
    }

    /**
     * Send a message in a conversation context (maintains history).
     *
     * @param string $message The user message to send
     * @return string|array The AI response or error response
     */
    public function sendMessage($message) {
        // Validate configuration
        if (!$this->isConfigured()) {
            return ['error' => 'Provider not properly configured'];
        }

        // Validate message
        if (!$this->validatePrompt($message)) {
            return ['error' => 'Invalid message: must be a non-empty string under 30000 characters'];
        }

        // Add user message to history
        $this->conversationHistory[] = [
            'role' => 'user',
            'content' => $message
        ];

        // Build messages with system instruction
        $messages = [];
        if ($this->systemInstruction !== null) {
            $messages[] = ['role' => 'system', 'content' => $this->systemInstruction];
        }
        $messages = array_merge($messages, $this->conversationHistory);

        // Make API request
        $response = $this->makeApiRequest($messages);

        if (is_array($response) && isset($response['error'])) {
            // Remove the failed user message from history
            array_pop($this->conversationHistory);
            return $response;
        }

        // Add assistant response to history
        $this->conversationHistory[] = [
            'role' => 'assistant',
            'content' => $response
        ];

        return $response;
    }

    /**
     * Make API request to Groq API.
     *
     * @param array $messages The messages array
     * @return string|array The response text or error array
     */
    protected function makeApiRequest($messages) {
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
        $response = @file_get_contents($this->api_url, false, $context);

        if ($response === FALSE) {
            return ['error' => 'Error contacting API'];
        }

        $text = $this->getTextFromResponse($response);
        
        if ($text === null) {
            $errorData = json_decode($response, true);
            if (isset($errorData['error']['message'])) {
                return ['error' => $errorData['error']['message']];
            }
            return ['error' => 'Failed to parse API response'];
        }

        return $text;
    }

    /**
     * Start a new conversation (clears history).
     *
     * @return void
     */
    public function startNewConversation() {
        $this->conversationHistory = [];
    }

    /**
     * Get the current conversation history.
     *
     * @return array The conversation history
     */
    public function getConversationHistory() {
        return $this->conversationHistory;
    }

    /**
     * Set a system instruction for the conversation.
     *
     * @param string $instruction The system instruction
     * @return void
     */
    public function setSystemInstruction($instruction) {
        $this->systemInstruction = $instruction;
    }

    /**
     * Get the current system instruction.
     *
     * @return string|null The system instruction or null if not set
     */
    public function getSystemInstruction() {
        return $this->systemInstruction;
    }

    /**
     * Add a message to history without sending (useful for restoring conversations).
     *
     * @param string $role The role ('user' or 'assistant')
     * @param string $text The message text
     * @return void
     */
    public function addToHistory($role, $text) {
        $this->conversationHistory[] = [
            'role' => $role,
            'content' => $text
        ];
    }

    /**
     * Get available models for Groq API.
     *
     * @return array List of available models
     */
    public static function getAvailableModels() {
        return [
            // Llama models
            'llama-3.3-70b-versatile',
            'llama-3.1-8b-instant',
            'llama-3.2-1b-preview',
            'llama-3.2-3b-preview',
            'llama-3.2-11b-vision-preview',
            'llama-3.2-90b-vision-preview',
            // Mixtral models
            'mixtral-8x7b-32768',
            // Gemma models
            'gemma2-9b-it',
            // Whisper (audio)
            'whisper-large-v3',
            'whisper-large-v3-turbo',
        ];
    }
}

