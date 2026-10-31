<?php

declare(strict_types=1);

namespace AIEngine\Providers;

/**
 * DeepSeek API Provider
 *
 * Uses the DeepSeek API (OpenAI-compatible format)
 * Get your API key from: https://platform.deepseek.com/api_keys
 */
class DeepSeek extends OpenAICompatibleProvider
{
    public const DEFAULT_MODEL = 'deepseek-flash';

    protected string $api_url = 'https://api.deepseek.com/chat/completions';

    public function getName(): string
    {
        return 'DeepSeek';
    }

    public static function getAvailableModels(): array
    {
        return [
            'deepseek-flash',
            'deepseek-v4-pro',
        ];
    }
}
