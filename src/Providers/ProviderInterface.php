<?php

declare(strict_types=1);

namespace AIEngine\Providers;

use AIEngine\Knowledge\KnowledgeBase;
use AIEngine\Response;

interface ProviderInterface
{
    /**
     * Generate content using the AI provider (single prompt, no history).
     *
     * @param string $prompt The prompt to send to the AI service
     * @return Response The generated content
     * @throws \AIEngine\Exceptions\ConfigurationException If not configured
     * @throws \AIEngine\Exceptions\ApiException If API call fails
     */
    public function generateContent(string $prompt): Response;

    /**
     * Send a message in a conversation context (maintains history).
     *
     * @param string $message The user message to send
     * @return Response The AI response
     * @throws \AIEngine\Exceptions\ConfigurationException If not configured
     * @throws \AIEngine\Exceptions\ApiException If API call fails
     */
    public function sendMessage(string $message): Response;

    /**
     * Start a new conversation (clears history).
     */
    public function startNewConversation(): void;

    /**
     * Get the current conversation history.
     *
     * @return array The conversation history
     */
    public function getConversationHistory(): array;

    /**
     * Set a system instruction for the conversation.
     *
     * @param string $instruction The system instruction
     */
    public function setSystemInstruction(string $instruction): void;

    /**
     * Validate the configuration (API key, etc.).
     *
     * @return bool True if configuration is valid
     */
    public function isConfigured(): bool;

    /**
     * Get the provider name.
     *
     * @return string The provider name
     */
    public function getName(): string;

    /**
     * Get the current model.
     *
     * @return string The model name
     */
    public function getModel(): string;

    /**
     * Set a knowledge base for RAG support.
     *
     * @param KnowledgeBase|null $knowledgeBase The knowledge base instance or null to clear
     */
    public function setKnowledgeBase(?KnowledgeBase $knowledgeBase): void;

    /**
     * Check if a knowledge base is attached and has content.
     *
     * @return bool True if knowledge base has content
     */
    public function hasKnowledgeBase(): bool;
}
