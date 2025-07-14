<?php

namespace AIEngine\Examples;

use AIEngine\AIEngine;
use AIEngine\Utils\ConfigValidator;

class BasicUsage
{
    /**
     * Example of basic usage (backward compatible).
     */
    public static function basicExample()
    {
        // Original usage still works
        $engine = new AIEngine('your-api-key-here');
        
        // Check if configured
        if ($engine->isConfigured()) {
            $response = $engine->generateContent('Hello, how are you?');
            echo "Response: " . $response . "\n";
        }
    }

    /**
     * Example of enhanced usage with configuration.
     */
    public static function enhancedExample()
    {
        // Enhanced usage with configuration
        $config = [
            'model' => 'gemini-pro',
            'timeout' => 30,
            'enable_logging' => true
        ];
        
        $engine = new AIEngine('your-api-key-here', $config);
        
        // Set custom logger
        $engine->setLogger(function($message, $level) {
            echo "[{$level}] {$message}\n";
        });
        
        // Validate prompt before sending
        $prompt = "Explain quantum computing in simple terms";
        if ($engine->validatePrompt($prompt)) {
            $response = $engine->generateContent($prompt);
            
            if (is_array($response) && isset($response['error'])) {
                echo "Error: " . $response['error'] . "\n";
            } else {
                echo "Response: " . $response . "\n";
            }
        }
        
        // Get provider information
        echo "Current provider: " . $engine->getProviderName() . "\n";
        echo "Available providers: " . implode(', ', $engine->getAvailableProviders()) . "\n";
    }

    /**
     * Example of configuration validation.
     */
    public static function configValidationExample()
    {
        $config = [
            'api_key' => 'your-api-key-here',
            'timeout' => 45,
            'model' => 'gemini-pro'
        ];
        
        // Validate configuration
        $errors = ConfigValidator::validateProviderConfig($config);
        
        if (empty($errors)) {
            echo "Configuration is valid!\n";
            
            // Sanitize configuration
            $sanitized = ConfigValidator::sanitizeConfig($config);
            
            $engine = new AIEngine($sanitized['api_key'], $sanitized);
            
            // Use the engine...
            if ($engine->isConfigured()) {
                echo "Engine is ready to use!\n";
            }
        } else {
            echo "Configuration errors:\n";
            foreach ($errors as $error) {
                echo "- {$error}\n";
            }
        }
    }

    /**
     * Example of provider management.
     */
    public static function providerManagementExample()
    {
        $engine = new AIEngine('your-api-key-here');
        
        // Get current provider
        $provider = $engine->getProvider();
        echo "Current provider: " . $provider->getName() . "\n";
        
        // Check if provider is configured
        if ($provider->isConfigured()) {
            echo "Provider is configured\n";
        }
        
        // If provider supports model changes (like Gemini)
        if (method_exists($provider, 'setModel')) {
            $provider->setModel('gemini-pro');
            echo "Model set to: " . $provider->getModel() . "\n";
        }
        
        // If provider supports timeout changes
        if (method_exists($provider, 'setTimeout')) {
            $provider->setTimeout(90);
            echo "Timeout set to 90 seconds\n";
        }
    }
} 