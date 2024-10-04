<?php

namespace AIEngine\Providers;

class Gemini
{
    protected $apiKey;

    public function __construct($apiKey)
    {
        $this->apiKey = $apiKey;
    }

    public function answer($prompt)
    {
        // Example API call logic (mocked for now)
        // This can be replaced with actual logic to integrate with Gemini API.
        return "Gemini's response to: " . $prompt;
    }
}