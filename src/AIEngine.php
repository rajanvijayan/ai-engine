<?php

namespace AIEngine;

use AIEngine\Providers\Gemini;
use AIEngine\Providers\ProviderInterface;

class AIEngine
{
    protected $provider;
    protected $config;
    protected $logger;

    /**
     * Constructor.
     *
     * @param string $apiKey The API key for the default provider
     * @param array $config Additional configuration options
     */
    public function __construct( $apiKey, $config = [] )
    {
        $this->config = array_merge([
            'default_provider' => 'gemini',
            'model' => 'gemini-2.0-flash',
            'timeout' => 60,
            'enable_logging' => false
        ], $config);

        // For now, let's configure Gemini; later this can handle multiple AI services.
        $this->provider = new Gemini( $apiKey, $this->config['model'], $this->config['timeout'] );
    }

    /**
     * Generate content using the current provider.
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
     * Get available providers (for future expansion).
     *
     * @return array Available provider names
     */
    public function getAvailableProviders()
    {
        return ['gemini']; // Will be expanded when more providers are added
    }
}