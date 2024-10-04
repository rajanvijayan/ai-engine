<?php

use PHPUnit\Framework\TestCase;
use AIEngine\AIEngine;

class AIEngineTest extends TestCase
{
    public function testAnswer()
    {
        $engine = new AIEngine();
        $engine->config('dummy-api-key');

        $response = $engine->answer('What is AI?');
        $this->assertEquals('Gemini\'s response to: What is AI?', $response);
    }
}