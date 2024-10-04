<?php

use PHPUnit\Framework\TestCase;
use AIEngine\AIEngine;

class AIEngineTest extends TestCase
{
    public function testGenerateContent()
    {
        $engine = new AIEngine( 'dummy-api-key' );
        $response = $engine->generateContent('What is AI?');
        $this->assertEquals('Gemini\'s response to: What is AI?', $response);
    }
}