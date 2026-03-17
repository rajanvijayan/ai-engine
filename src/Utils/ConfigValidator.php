<?php

declare(strict_types=1);

namespace AIEngine\Utils;

class ConfigValidator
{
    /**
     * Validate API key format.
     */
    public static function isValidApiKey(string $apiKey): bool
    {
        return !empty(trim($apiKey)) && strlen($apiKey) >= 10;
    }

    /**
     * Validate timeout value.
     *
     * @param int|float $timeout The timeout to validate
     */
    public static function isValidTimeout($timeout): bool
    {
        return is_numeric($timeout) && $timeout > 0 && $timeout <= 300;
    }

    /**
     * Validate model name.
     */
    public static function isValidModel(string $model): bool
    {
        return !empty(trim($model)) && preg_match('/^[a-zA-Z0-9\-_.]+$/', $model) === 1;
    }

    /**
     * Sanitize configuration array.
     */
    public static function sanitizeConfig(array $config): array
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
     * @return array<string> Validation error messages
     */
    public static function validateProviderConfig(array $config): array
    {
        $errors = [];

        if (!isset($config['api_key']) || !is_string($config['api_key']) || !self::isValidApiKey($config['api_key'])) {
            $errors[] = 'Invalid or missing API key';
        }

        if (isset($config['timeout']) && !self::isValidTimeout($config['timeout'])) {
            $errors[] = 'Invalid timeout value (must be between 1 and 300 seconds)';
        }

        if (isset($config['model']) && is_string($config['model']) && !self::isValidModel($config['model'])) {
            $errors[] = 'Invalid model name';
        }

        return $errors;
    }
}
