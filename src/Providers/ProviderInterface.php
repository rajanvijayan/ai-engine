<?php

namespace AIEngine\Providers;

interface ProviderInterface
{
    /**
     * Generate content using the AI provider.
     *
     * @param string $prompt The prompt to send to the AI service
     * @return string|array The generated content or error response
     */
    public function generateContent($prompt);

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