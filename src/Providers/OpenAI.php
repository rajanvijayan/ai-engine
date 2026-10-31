<?php

declare(strict_types=1);

namespace AIEngine\Providers;

/**
 * OpenAI API Provider
 *
 * Uses the OpenAI Chat Completions API (GPT models)
 * Get your API key from: https://platform.openai.com/api-keys
 */
class OpenAI extends OpenAICompatibleProvider
{
    public const DEFAULT_MODEL = 'gpt-6.1-sol';

    protected string $api_url = 'https://api.openai.com/v1/chat/completions';

    public function getName(): string
    {
        return 'OpenAI';
    }

    public static function getAvailableModels(): array
    {
        return [
            'gpt-6.1-sol',
            'gpt-6-astra',
            'gpt-6.1-terra',
            'gpt-6-luna',
        ];
    }
}
