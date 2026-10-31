<?php

declare(strict_types=1);

namespace AIEngine\Providers;

/**
 * Meta Llama API Provider
 *
 * Uses Meta's official Llama API (OpenAI-compatible format)
 * Get your API key from: https://llama.meta.com
 */
class MetaLlama extends OpenAICompatibleProvider
{
    public const DEFAULT_MODEL = 'Llama-4-Maverick-17B-128E-Instruct-FP8';

    protected string $api_url = 'https://api.llama.meta.com/v1/chat/completions';

    public function getName(): string
    {
        return 'MetaLlama';
    }

    public static function getAvailableModels(): array
    {
        return [
            'Llama-4-Maverick-17B-128E-Instruct-FP8',
            'Llama-4-Scout-17B-16E-Instruct',
            'Llama-3.3-70B-Instruct',
            'Llama-3.2-3B-Instruct',
            'Llama-3.2-1B-Instruct',
        ];
    }
}
