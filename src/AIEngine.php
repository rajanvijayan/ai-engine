<?php

namespace AIEngine;

use AIEngine\Providers\Gemini;

class AIEngine
{
    protected $provider;

    public function config($apiKey)
    {
        // For now, let's configure Gemini; later this can handle multiple AI services.
        $this->provider = new Gemini($apiKey);
    }

    public function answer($prompt)
    {
        return $this->provider->answer($prompt);
    }
}