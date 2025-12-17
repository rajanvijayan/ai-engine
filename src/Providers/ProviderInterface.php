<?php

namespace AIEngine\Providers;

interface ProviderInterface
{
    /**
     * Generate content using the AI provider (single prompt, no history).
     *
     * @param string $prompt The prompt to send to the AI service
     * @return string|array The generated content or error response
     */
    public function generateContent($prompt);

    /**
     * Send a message in a conversation context (maintains history).
     *
     * @param string $message The user message to send
     * @return string|array The AI response or error response
     */
    public function sendMessage($message);

    /**
     * Start a new conversation (clears history).
     *
     * @return void
     */
    public function startNewConversation();

    /**
     * Get the current conversation history.
     *
     * @return array The conversation history
     */
    public function getConversationHistory();

    /**
     * Set a system instruction for the conversation.
     *
     * @param string $instruction The system instruction
     * @return void
     */
    public function setSystemInstruction($instruction);

    /**
     * Validate the configuration (API key, etc.).
     *
     * @return bool True if configuration is valid
     */
    public function isConfigured();

    /**
     * Get the provider name.
     *
     * @return string The provider name
     */
    public function getName();
} 