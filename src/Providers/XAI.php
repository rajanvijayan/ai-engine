<?php

declare(strict_types=1);

namespace AIEngine\Providers;

/**
 * xAI API Provider
 *
 * Uses the xAI API to access Grok models (OpenAI-compatible format)
 * Get your API key from: https://console.x.ai
 */
class XAI extends OpenAICompatibleProvider
{
    public const DEFAULT_MODEL = 'grok-4.7';

    protected string $api_url = 'https://api.x.ai/v1/chat/completions';

    public function getName(): string
    {
        return 'xAI';
    }

    public static function getAvailableModels(): array
    {
        return [
            'grok-4.7',
            'grok-4.6',
            'grok-4.5',
            'grok-4.3',
            'grok-4.20-0309-reasoning',
            'grok-4.20-0309-non-reasoning',
        ];
    }
}
