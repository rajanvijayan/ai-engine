<?php

declare(strict_types=1);

namespace AIEngine\Providers;

/**
 * Mistral AI API Provider
 *
 * Uses the Mistral AI API (OpenAI-compatible format)
 * Get your API key from: https://console.mistral.ai/api-keys
 */
class Mistral extends OpenAICompatibleProvider
{
    public const DEFAULT_MODEL = 'mistral-large-latest';

    protected string $api_url = 'https://api.mistral.ai/v1/chat/completions';

    public function getName(): string
    {
        return 'Mistral';
    }

    public static function getAvailableModels(): array
    {
        return [
            'mistral-large-latest',
            'mistral-medium-latest',
            'mistral-small-latest',
            'ministral-8b-latest',
            'codestral-latest',
        ];
    }
}
