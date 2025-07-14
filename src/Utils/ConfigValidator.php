<?php

namespace AIEngine\Utils;

class ConfigValidator
{
    /**
     * Validate API key format.
     *
     * @param string $apiKey The API key to validate
     * @return bool True if valid
     */
    public static function isValidApiKey($apiKey)
    {
        return is_string($apiKey) && !empty(trim($apiKey)) && strlen($apiKey) >= 10;
    }

    /**
     * Validate timeout value.
     *
     * @param int $timeout The timeout to validate
     * @return bool True if valid
     */
    public static function isValidTimeout($timeout)
    {
        return is_numeric($timeout) && $timeout > 0 && $timeout <= 300;
    }

    /**
     * Validate model name.
     *
     * @param string $model The model name to validate
     * @return bool True if valid
     */
    public static function isValidModel($model)
    {
        return is_string($model) && !empty(trim($model)) && preg_match('/^[a-zA-Z0-9\-_]+$/', $model);
    }

    /**
     * Sanitize configuration array.
     *
     * @param array $config The configuration array
     * @return array Sanitized configuration
     */
    public static function sanitizeConfig($config)
    {
        $sanitized = [];
        
        foreach ($config as $key => $value) {
            if (is_string($value)) {
                $sanitized[$key] = trim($value);
            } elseif (is_numeric($value)) {
                $sanitized[$key] = $value;
            } elseif (is_bool($value)) {
                $sanitized[$key] = $value;
            } elseif (is_array($value)) {
                $sanitized[$key] = self::sanitizeConfig($value);
            }
        }
        
        return $sanitized;
    }

    /**
     * Validate provider configuration.
     *
     * @param array $config The provider configuration
     * @return array Validation results with errors
     */
    public static function validateProviderConfig($config)
    {
        $errors = [];
        
        if (!isset($config['api_key']) || !self::isValidApiKey($config['api_key'])) {
            $errors[] = 'Invalid or missing API key';
        }
        
        if (isset($config['timeout']) && !self::isValidTimeout($config['timeout'])) {
            $errors[] = 'Invalid timeout value (must be between 1 and 300 seconds)';
        }
        
        if (isset($config['model']) && !self::isValidModel($config['model'])) {
            $errors[] = 'Invalid model name';
        }
        
        return $errors;
    }
} 