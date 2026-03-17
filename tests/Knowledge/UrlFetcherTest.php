<?php

declare(strict_types=1);

namespace AIEngine\Tests\Knowledge;

use AIEngine\Knowledge\UrlFetcher;
use PHPUnit\Framework\TestCase;

class UrlFetcherTest extends TestCase
{
    private UrlFetcher $fetcher;

    protected function setUp(): void
    {
        $this->fetcher = new UrlFetcher();
    }

    public function testValidUrlValidation(): void
    {
        $result = $this->fetcher->fetch('https://example.com');

        $this->assertArrayHasKey('url', $result);
        $this->assertEquals('https://example.com', $result['url']);
    }

    public function testInvalidUrlReturnsError(): void
    {
        $result = $this->fetcher->fetch('not-a-url');

        $this->assertFalse($result['success']);
        $this->assertEquals('Invalid URL format', $result['error']);
    }

    public function testNonHttpUrlReturnsError(): void
    {
        $result = $this->fetcher->fetch('ftp://example.com/file.txt');

        $this->assertFalse($result['success']);
        $this->assertEquals('Invalid URL format', $result['error']);
    }

    public function testExtractText(): void
    {
        $html = '<html><body><p>Hello World</p><script>alert("test")</script></body></html>';

        $text = $this->fetcher->extractText($html);

        $this->assertStringContainsString('Hello World', $text);
        $this->assertStringNotContainsString('alert', $text);
    }

    public function testScriptRemoval(): void
    {
        $html = '<html><body><p>Content</p><script>var x = 1;</script><p>More</p></body></html>';

        $text = $this->fetcher->extractText($html);

        $this->assertStringContainsString('Content', $text);
        $this->assertStringContainsString('More', $text);
        $this->assertStringNotContainsString('var x', $text);
    }

    public function testStyleRemoval(): void
    {
        $html = '<html><head><style>body { color: red; }</style></head><body><p>Text</p></body></html>';

        $text = $this->fetcher->extractText($html);

        $this->assertStringContainsString('Text', $text);
        $this->assertStringNotContainsString('color', $text);
    }

    public function testExtractMetadata(): void
    {
        $html = '<html><head><title>Test Page</title><meta name="description" content="Test description"></head><body></body></html>';

        $metadata = $this->fetcher->extractMetadata($html);

        $this->assertEquals('Test Page', $metadata['title']);
        $this->assertEquals('Test description', $metadata['description']);
    }

    public function testSetTimeout(): void
    {
        $this->fetcher->setTimeout(10);

        $this->assertTrue(true);
    }

    public function testSetUserAgent(): void
    {
        $this->fetcher->setUserAgent('CustomBot/1.0');

        $this->assertTrue(true);
    }

    public function testFetchMultiple(): void
    {
        $urls = ['not-valid-1', 'not-valid-2'];

        $results = $this->fetcher->fetchMultiple($urls);

        $this->assertCount(2, $results);
        $this->assertFalse($results[0]['success']);
        $this->assertFalse($results[1]['success']);
    }

    public function testHtmlEntityDecoding(): void
    {
        $html = '<html><body><p>&amp; &lt; &gt; &quot;</p></body></html>';

        $text = $this->fetcher->extractText($html);

        $this->assertStringContainsString('&', $text);
        $this->assertStringContainsString('<', $text);
        $this->assertStringContainsString('>', $text);
    }

    public function testLineBreakHandling(): void
    {
        $html = '<html><body><p>Line 1</p><p>Line 2</p></body></html>';

        $text = $this->fetcher->extractText($html);

        $this->assertStringContainsString('Line 1', $text);
        $this->assertStringContainsString('Line 2', $text);
    }

    public function testExtractMetadataWithKeywords(): void
    {
        $html = '<html><head><title>Page</title><meta name="keywords" content="php, ai, engine"></head><body></body></html>';

        $metadata = $this->fetcher->extractMetadata($html);

        $this->assertEquals('php, ai, engine', $metadata['keywords']);
    }

    public function testExtractTextRemovesNavFooterHeader(): void
    {
        $html = '<html><body><nav>Navigation</nav><main><p>Main content</p></main><footer>Footer</footer></body></html>';

        $text = $this->fetcher->extractText($html);

        $this->assertStringContainsString('Main content', $text);
        $this->assertStringNotContainsString('Navigation', $text);
        $this->assertStringNotContainsString('Footer', $text);
    }

    public function testExtractTextFromEmptyHtml(): void
    {
        $text = $this->fetcher->extractText('');

        $this->assertEquals('', $text);
    }

    public function testCommentRemoval(): void
    {
        $html = '<html><body><!-- This is a comment --><p>Visible</p></body></html>';

        $text = $this->fetcher->extractText($html);

        $this->assertStringContainsString('Visible', $text);
        $this->assertStringNotContainsString('comment', $text);
    }

    public function testCustomTimeout(): void
    {
        $fetcher = new UrlFetcher(5);

        // Just verify it creates without error
        $this->assertInstanceOf(UrlFetcher::class, $fetcher);
    }

    public function testCustomUserAgent(): void
    {
        $fetcher = new UrlFetcher(30, 'MyBot/2.0');

        $this->assertInstanceOf(UrlFetcher::class, $fetcher);
    }
}
