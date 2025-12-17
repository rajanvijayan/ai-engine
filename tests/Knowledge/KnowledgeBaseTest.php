<?php

namespace AIEngine\Tests\Knowledge;

use PHPUnit\Framework\TestCase;
use AIEngine\Knowledge\KnowledgeBase;
use AIEngine\Knowledge\UrlFetcher;

class KnowledgeBaseTest extends TestCase
{
    private KnowledgeBase $kb;

    protected function setUp(): void
    {
        $this->kb = new KnowledgeBase();
    }

    /**
     * Test knowledge base starts empty
     */
    public function testStartsEmpty(): void
    {
        $this->assertTrue($this->kb->isEmpty());
        $this->assertEquals(0, $this->kb->count());
    }

    /**
     * Test adding text
     */
    public function testAddText(): void
    {
        $result = $this->kb->addText('Test content', 'test-source', 'Test Title');
        
        $this->assertTrue($result);
        $this->assertEquals(1, $this->kb->count());
        $this->assertFalse($this->kb->isEmpty());
    }

    /**
     * Test adding empty text fails
     */
    public function testAddEmptyTextFails(): void
    {
        $result = $this->kb->addText('', 'source');
        
        $this->assertFalse($result);
        $this->assertEquals(0, $this->kb->count());
    }

    /**
     * Test adding whitespace-only text fails
     */
    public function testAddWhitespaceTextFails(): void
    {
        $result = $this->kb->addText('   ', 'source');
        
        $this->assertFalse($result);
    }

    /**
     * Test get documents
     */
    public function testGetDocuments(): void
    {
        $this->kb->addText('Content 1', 'source1', 'Title 1');
        $this->kb->addText('Content 2', 'source2', 'Title 2');
        
        $docs = $this->kb->getDocuments();
        
        $this->assertCount(2, $docs);
        $this->assertEquals('source1', $docs[0]['source']);
        $this->assertEquals('Title 1', $docs[0]['title']);
        $this->assertEquals('Content 1', $docs[0]['content']);
    }

    /**
     * Test clear
     */
    public function testClear(): void
    {
        $this->kb->addText('Content', 'source');
        $this->assertEquals(1, $this->kb->count());
        
        $this->kb->clear();
        
        $this->assertEquals(0, $this->kb->count());
        $this->assertTrue($this->kb->isEmpty());
    }

    /**
     * Test remove by index
     */
    public function testRemove(): void
    {
        $this->kb->addText('Content 1', 'source1');
        $this->kb->addText('Content 2', 'source2');
        $this->kb->addText('Content 3', 'source3');
        
        $result = $this->kb->remove(1); // Remove middle
        
        $this->assertTrue($result);
        $this->assertEquals(2, $this->kb->count());
        
        $docs = $this->kb->getDocuments();
        $this->assertEquals('source1', $docs[0]['source']);
        $this->assertEquals('source3', $docs[1]['source']);
    }

    /**
     * Test remove invalid index
     */
    public function testRemoveInvalidIndex(): void
    {
        $this->kb->addText('Content', 'source');
        
        $result = $this->kb->remove(999);
        
        $this->assertFalse($result);
        $this->assertEquals(1, $this->kb->count());
    }

    /**
     * Test build context
     */
    public function testBuildContext(): void
    {
        $this->kb->addText('This is test content.', 'test-source', 'Test Title');
        
        $context = $this->kb->buildContext();
        
        $this->assertStringContainsString('KNOWLEDGE BASE', $context);
        $this->assertStringContainsString('Test Title', $context);
        $this->assertStringContainsString('test-source', $context);
        $this->assertStringContainsString('This is test content.', $context);
    }

    /**
     * Test build context when empty
     */
    public function testBuildContextEmpty(): void
    {
        $context = $this->kb->buildContext();
        
        $this->assertEquals('', $context);
    }

    /**
     * Test build context with max chars
     */
    public function testBuildContextWithMaxChars(): void
    {
        $this->kb->addText(str_repeat('A', 1000), 'source1');
        $this->kb->addText(str_repeat('B', 1000), 'source2');
        
        $context = $this->kb->buildContext(500);
        
        $this->assertLessThanOrEqual(600, strlen($context)); // Allow some overhead
    }

    /**
     * Test get summary
     */
    public function testGetSummary(): void
    {
        $this->kb->addText('Content one', 'source1', 'Title 1');
        $this->kb->addText('Content two', 'source2', 'Title 2');
        
        $summary = $this->kb->getSummary();
        
        $this->assertEquals(2, $summary['count']);
        $this->assertCount(2, $summary['sources']);
        $this->assertGreaterThan(0, $summary['totalChars']);
    }

    /**
     * Test search
     */
    public function testSearch(): void
    {
        $this->kb->addText('PHP is a programming language', 'doc1', 'PHP Guide');
        $this->kb->addText('Python is also a language', 'doc2', 'Python Guide');
        $this->kb->addText('JavaScript for web', 'doc3', 'JS Guide');
        
        $results = $this->kb->search('PHP');
        
        $this->assertCount(1, $results);
        $this->assertEquals('doc1', $results[0]['source']);
    }

    /**
     * Test search in title
     */
    public function testSearchInTitle(): void
    {
        $this->kb->addText('Some content', 'doc1', 'Laravel Framework');
        $this->kb->addText('Other content', 'doc2', 'React Library');
        
        $results = $this->kb->search('Laravel');
        
        $this->assertCount(1, $results);
        $this->assertEquals('Laravel Framework', $results[0]['title']);
    }

    /**
     * Test search case insensitive
     */
    public function testSearchCaseInsensitive(): void
    {
        $this->kb->addText('PHP programming', 'doc1');
        
        $results1 = $this->kb->search('php');
        $results2 = $this->kb->search('PHP');
        $results3 = $this->kb->search('Php');
        
        $this->assertCount(1, $results1);
        $this->assertCount(1, $results2);
        $this->assertCount(1, $results3);
    }

    /**
     * Test save and load
     */
    public function testSaveAndLoad(): void
    {
        $tempFile = sys_get_temp_dir() . '/kb_test_' . uniqid() . '.json';
        
        try {
            $this->kb->addText('Test content', 'test-source', 'Test Title');
            
            $saved = $this->kb->save($tempFile);
            $this->assertTrue($saved);
            
            // Create new instance and load
            $kb2 = new KnowledgeBase();
            $loaded = $kb2->load($tempFile);
            
            $this->assertTrue($loaded);
            $this->assertEquals(1, $kb2->count());
            
            $docs = $kb2->getDocuments();
            $this->assertEquals('Test content', $docs[0]['content']);
            $this->assertEquals('Test Title', $docs[0]['title']);
        } finally {
            if (file_exists($tempFile)) {
                unlink($tempFile);
            }
        }
    }

    /**
     * Test load non-existent file
     */
    public function testLoadNonExistentFile(): void
    {
        $result = $this->kb->load('/non/existent/file.json');
        
        $this->assertFalse($result);
    }

    /**
     * Test get fetcher
     */
    public function testGetFetcher(): void
    {
        $fetcher = $this->kb->getFetcher();
        
        $this->assertInstanceOf(UrlFetcher::class, $fetcher);
    }

    /**
     * Test custom fetcher
     */
    public function testCustomFetcher(): void
    {
        $customFetcher = new UrlFetcher(10, 'CustomBot/1.0');
        $kb = new KnowledgeBase($customFetcher);
        
        $this->assertSame($customFetcher, $kb->getFetcher());
    }

    /**
     * Test add URL with invalid URL
     */
    public function testAddInvalidUrl(): void
    {
        $result = $this->kb->addUrl('not-a-valid-url');
        
        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);
    }

    /**
     * Test add multiple URLs with invalid URLs
     */
    public function testAddMultipleInvalidUrls(): void
    {
        $result = $this->kb->addUrls(['invalid1', 'invalid2']);
        
        $this->assertEquals(0, $result['success']);
        $this->assertEquals(2, $result['failed']);
    }
}

