<?php

declare(strict_types=1);

namespace AIEngine\Tests;

use AIEngine\Response;
use PHPUnit\Framework\TestCase;

class ResponseTest extends TestCase
{
    public function testGetText(): void
    {
        $response = new Response('Hello World', 'Gemini', 'gemini-2.0-flash');

        $this->assertEquals('Hello World', $response->getText());
    }

    public function testGetProvider(): void
    {
        $response = new Response('text', 'Groq', 'llama-3.3-70b-versatile');

        $this->assertEquals('Groq', $response->getProvider());
    }

    public function testGetModel(): void
    {
        $response = new Response('text', 'Gemini', 'gemini-2.5-pro');

        $this->assertEquals('gemini-2.5-pro', $response->getModel());
    }

    public function testGetRawResponse(): void
    {
        $raw = ['choices' => [['message' => ['content' => 'text']]]];
        $response = new Response('text', 'Groq', 'model', $raw);

        $this->assertEquals($raw, $response->getRawResponse());
    }

    public function testGetRawResponseReturnsNullWhenNotSet(): void
    {
        $response = new Response('text', 'Gemini', 'model');

        $this->assertNull($response->getRawResponse());
    }

    public function testToString(): void
    {
        $response = new Response('Hello AI!', 'Gemini', 'model');

        $this->assertEquals('Hello AI!', (string) $response);
    }

    public function testResponseCanBeUsedAsString(): void
    {
        $response = new Response('Concatenate me', 'Gemini', 'model');

        $result = 'Response: ' . $response;

        $this->assertEquals('Response: Concatenate me', $result);
    }
}
