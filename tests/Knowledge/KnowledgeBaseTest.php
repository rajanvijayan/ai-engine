<?php

declare(strict_types=1);

namespace AIEngine\Tests\Knowledge;

use AIEngine\Knowledge\KnowledgeBase;
use AIEngine\Knowledge\UrlFetcher;
use PHPUnit\Framework\TestCase;

class KnowledgeBaseTest extends TestCase
{
    private KnowledgeBase $kb;

    protected function setUp(): void
    {
        $this->kb = new KnowledgeBase();
    }

    public function testStartsEmpty(): void
    {
        $this->assertTrue($this->kb->isEmpty());
        $this->assertEquals(0, $this->kb->count());
    }

    public function testAddText(): void
    {
        $result = $this->kb->addText('Test content', 'test-source', 'Test Title');

        $this->assertTrue($result);
        $this->assertEquals(1, $this->kb->count());
        $this->assertFalse($this->kb->isEmpty());
    }

    public function testAddEmptyTextFails(): void
    {
        $result = $this->kb->addText('', 'source');

        $this->assertFalse($result);
        $this->assertEquals(0, $this->kb->count());
    }

    public function testAddWhitespaceTextFails(): void
    {
        $result = $this->kb->addText('   ', 'source');

        $this->assertFalse($result);
    }

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

    public function testClear(): void
    {
        $this->kb->addText('Content', 'source');
        $this->assertEquals(1, $this->kb->count());

        $this->kb->clear();

        $this->assertEquals(0, $this->kb->count());
        $this->assertTrue($this->kb->isEmpty());
    }

    public function testRemove(): void
    {
        $this->kb->addText('Content 1', 'source1');
        $this->kb->addText('Content 2', 'source2');
        $this->kb->addText('Content 3', 'source3');

        $result = $this->kb->remove(1);

        $this->assertTrue($result);
        $this->assertEquals(2, $this->kb->count());

        $docs = $this->kb->getDocuments();
        $this->assertEquals('source1', $docs[0]['source']);
        $this->assertEquals('source3', $docs[1]['source']);
    }

    public function testRemoveInvalidIndex(): void
    {
        $this->kb->addText('Content', 'source');

        $result = $this->kb->remove(999);

        $this->assertFalse($result);
        $this->assertEquals(1, $this->kb->count());
    }

    public function testBuildContext(): void
    {
        $this->kb->addText('This is test content.', 'test-source', 'Test Title');

        $context = $this->kb->buildContext();

        $this->assertStringContainsString('KNOWLEDGE BASE', $context);
        $this->assertStringContainsString('Test Title', $context);
        $this->assertStringContainsString('test-source', $context);
        $this->assertStringContainsString('This is test content.', $context);
    }

    public function testBuildContextEmpty(): void
    {
        $context = $this->kb->buildContext();

        $this->assertEquals('', $context);
    }

    public function testBuildContextWithMaxChars(): void
    {
        $this->kb->addText(str_repeat('A', 1000), 'source1');
        $this->kb->addText(str_repeat('B', 1000), 'source2');

        $context = $this->kb->buildContext(500);

        $this->assertLessThanOrEqual(600, strlen($context));
    }

    public function testGetSummary(): void
    {
        $this->kb->addText('Content one', 'source1', 'Title 1');
        $this->kb->addText('Content two', 'source2', 'Title 2');

        $summary = $this->kb->getSummary();

        $this->assertEquals(2, $summary['count']);
        $this->assertCount(2, $summary['sources']);
        $this->assertGreaterThan(0, $summary['totalChars']);
    }

    public function testSearch(): void
    {
        $this->kb->addText('PHP is a programming language', 'doc1', 'PHP Guide');
        $this->kb->addText('Python is also a language', 'doc2', 'Python Guide');
        $this->kb->addText('JavaScript for web', 'doc3', 'JS Guide');

        $results = $this->kb->search('PHP');

        $this->assertCount(1, $results);
        $this->assertEquals('doc1', $results[0]['source']);
    }

    public function testSearchInTitle(): void
    {
        $this->kb->addText('Some content', 'doc1', 'Laravel Framework');
        $this->kb->addText('Other content', 'doc2', 'React Library');

        $results = $this->kb->search('Laravel');

        $this->assertCount(1, $results);
        $this->assertEquals('Laravel Framework', $results[0]['title']);
    }

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

    public function testSaveAndLoad(): void
    {
        $tempFile = sys_get_temp_dir() . '/kb_test_' . uniqid() . '.json';

        try {
            $this->kb->addText('Test content', 'test-source', 'Test Title');

            $saved = $this->kb->save($tempFile);
            $this->assertTrue($saved);

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

    public function testLoadNonExistentFile(): void
    {
        $result = $this->kb->load('/non/existent/file.json');

        $this->assertFalse($result);
    }

    public function testGetFetcher(): void
    {
        $fetcher = $this->kb->getFetcher();

        $this->assertInstanceOf(UrlFetcher::class, $fetcher);
    }

    public function testCustomFetcher(): void
    {
        $customFetcher = new UrlFetcher(10, 'CustomBot/1.0');
        $kb = new KnowledgeBase($customFetcher);

        $this->assertSame($customFetcher, $kb->getFetcher());
    }

    public function testAddInvalidUrl(): void
    {
        $result = $this->kb->addUrl('not-a-valid-url');

        $this->assertFalse($result['success']);
        $this->assertArrayHasKey('error', $result);
    }

    public function testAddMultipleInvalidUrls(): void
    {
        $result = $this->kb->addUrls(['invalid1', 'invalid2']);

        $this->assertEquals(0, $result['success']);
        $this->assertEquals(2, $result['failed']);
    }

    public function testLoadInvalidJsonFile(): void
    {
        $tempFile = sys_get_temp_dir() . '/kb_invalid_' . uniqid() . '.json';

        try {
            file_put_contents($tempFile, 'not valid json');

            $result = $this->kb->load($tempFile);

            $this->assertFalse($result);
        } finally {
            if (file_exists($tempFile)) {
                unlink($tempFile);
            }
        }
    }

    public function testLoadJsonWithoutDocumentsKey(): void
    {
        $tempFile = sys_get_temp_dir() . '/kb_nodocs_' . uniqid() . '.json';

        try {
            file_put_contents($tempFile, json_encode(['version' => '1.0']));

            $result = $this->kb->load($tempFile);

            $this->assertFalse($result);
        } finally {
            if (file_exists($tempFile)) {
                unlink($tempFile);
            }
        }
    }

    public function testSearchWithNoResults(): void
    {
        $this->kb->addText('PHP content', 'doc1');

        $results = $this->kb->search('nonexistent');

        $this->assertEmpty($results);
    }

    public function testSearchOnEmptyKnowledgeBase(): void
    {
        $results = $this->kb->search('anything');

        $this->assertEmpty($results);
    }
}
