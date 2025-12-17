<?php

namespace AIEngine\Tests\Knowledge;

use PHPUnit\Framework\TestCase;
use AIEngine\Knowledge\UrlFetcher;

class UrlFetcherTest extends TestCase
{
    private UrlFetcher $fetcher;

    protected function setUp(): void
    {
        $this->fetcher = new UrlFetcher();
    }

    /**
     * Test URL validation with valid URLs
     */
    public function testValidUrlValidation(): void
    {
        $result = $this->fetcher->fetch('https://example.com');
        
        // May fail due to network, but should not fail URL validation
        $this->assertArrayHasKey('url', $result);
        $this->assertEquals('https://example.com', $result['url']);
    }

    /**
     * Test URL validation with invalid URLs
     */
    public function testInvalidUrlReturnsError(): void
    {
        $result = $this->fetcher->fetch('not-a-url');
        
        $this->assertFalse($result['success']);
        $this->assertEquals('Invalid URL format', $result['error']);
    }

    /**
     * Test URL validation with FTP (non-HTTP) URLs
     */
    public function testNonHttpUrlReturnsError(): void
    {
        $result = $this->fetcher->fetch('ftp://example.com/file.txt');
        
        $this->assertFalse($result['success']);
        $this->assertEquals('Invalid URL format', $result['error']);
    }

    /**
     * Test text extraction from HTML
     */
    public function testExtractText(): void
    {
        $html = '<html><body><p>Hello World</p><script>alert("test")</script></body></html>';
        
        $text = $this->fetcher->extractText($html);
        
        $this->assertStringContainsString('Hello World', $text);
        $this->assertStringNotContainsString('alert', $text);
    }

    /**
     * Test script removal from HTML
     */
    public function testScriptRemoval(): void
    {
        $html = '<html><body><p>Content</p><script>var x = 1;</script><p>More</p></body></html>';
        
        $text = $this->fetcher->extractText($html);
        
        $this->assertStringContainsString('Content', $text);
        $this->assertStringContainsString('More', $text);
        $this->assertStringNotContainsString('var x', $text);
    }

    /**
     * Test style removal from HTML
     */
    public function testStyleRemoval(): void
    {
        $html = '<html><head><style>body { color: red; }</style></head><body><p>Text</p></body></html>';
        
        $text = $this->fetcher->extractText($html);
        
        $this->assertStringContainsString('Text', $text);
        $this->assertStringNotContainsString('color', $text);
    }

    /**
     * Test metadata extraction
     */
    public function testExtractMetadata(): void
    {
        $html = '<html><head><title>Test Page</title><meta name="description" content="Test description"></head><body></body></html>';
        
        $metadata = $this->fetcher->extractMetadata($html);
        
        $this->assertEquals('Test Page', $metadata['title']);
        $this->assertEquals('Test description', $metadata['description']);
    }

    /**
     * Test timeout setting
     */
    public function testSetTimeout(): void
    {
        $this->fetcher->setTimeout(10);
        
        // No exception means success
        $this->assertTrue(true);
    }

    /**
     * Test user agent setting
     */
    public function testSetUserAgent(): void
    {
        $this->fetcher->setUserAgent('CustomBot/1.0');
        
        // No exception means success
        $this->assertTrue(true);
    }

    /**
     * Test fetch multiple URLs
     */
    public function testFetchMultiple(): void
    {
        $urls = ['not-valid-1', 'not-valid-2'];
        
        $results = $this->fetcher->fetchMultiple($urls);
        
        $this->assertCount(2, $results);
        $this->assertFalse($results[0]['success']);
        $this->assertFalse($results[1]['success']);
    }

    /**
     * Test HTML entity decoding
     */
    public function testHtmlEntityDecoding(): void
    {
        $html = '<html><body><p>&amp; &lt; &gt; &quot;</p></body></html>';
        
        $text = $this->fetcher->extractText($html);
        
        $this->assertStringContainsString('&', $text);
        $this->assertStringContainsString('<', $text);
        $this->assertStringContainsString('>', $text);
    }

    /**
     * Test line break handling
     */
    public function testLineBreakHandling(): void
    {
        $html = '<html><body><p>Line 1</p><p>Line 2</p></body></html>';
        
        $text = $this->fetcher->extractText($html);
        
        $this->assertStringContainsString('Line 1', $text);
        $this->assertStringContainsString('Line 2', $text);
    }
}

