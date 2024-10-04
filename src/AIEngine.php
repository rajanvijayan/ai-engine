<?php

namespace AIEngine;

use AIEngine\Providers\Gemini;

class AIEngine
{
    protected $provider;

    public function __construct( $apiKey )
    {
        // For now, let's configure Gemini; later this can handle multiple AI services.
        $this->provider = new Gemini( $apiKey );
    }

    public function generateContent($prompt)
    {
        return $this->provider->generateContent($prompt);
    }
}