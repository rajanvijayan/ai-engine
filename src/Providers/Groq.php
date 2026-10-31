<?php

declare(strict_types=1);

namespace AIEngine\Providers;

/**
 * Groq API Provider
 *
 * Uses Groq's API to access Llama and other open models with ultra-fast inference
 * Get your FREE API key from: https://console.groq.com
 */
class Groq extends OpenAICompatibleProvider
{
    public const DEFAULT_MODEL = 'llama-3.3-70b-versatile';

    protected string $api_url = 'https://api.groq.com/openai/v1/chat/completions';

    public function getName(): string
    {
        return 'Groq';
    }

    public static function getAvailableModels(): array
    {
        return [
            'llama-3.3-70b-versatile',
            'llama-3.1-8b-instant',
            'openai/gpt-oss-120b',
            'openai/gpt-oss-20b',
            'qwen/qwen3.8-27b',
            'minimaxai/minimax-m2.7',
            'meta-models/muse-spark-open',
        ];
    }
}
