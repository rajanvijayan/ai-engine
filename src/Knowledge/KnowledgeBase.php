<?php

declare(strict_types=1);

namespace AIEngine\Knowledge;

/**
 * Knowledge Base
 *
 * Stores and manages documents for RAG (Retrieval Augmented Generation)
 */
class KnowledgeBase
{
    /**
     * @var array<int, array{source: string, title: ?string, content: string, addedAt: string}>
     */
    protected array $documents = [];

    protected UrlFetcher $fetcher;

    public function __construct(?UrlFetcher $fetcher = null)
    {
        $this->fetcher = $fetcher ?? new UrlFetcher();
    }

    /**
     * Add knowledge from a URL
     *
     * @return array{success: bool, error?: string, title?: string}
     */
    public function addUrl(string $url): array
    {
        $result = $this->fetcher->fetch($url);

        if (!$result['success']) {
            return [
                'success' => false,
                'error' => $result['error'] ?? 'Failed to fetch URL'
            ];
        }

        $this->documents[] = [
            'source' => $url,
            'title' => $result['title'] ?? null,
            'content' => $result['content'],
            'addedAt' => date('Y-m-d H:i:s')
        ];

        return [
            'success' => true,
            'title' => $result['title'] ?? null
        ];
    }

    /**
     * Add knowledge from multiple URLs
     *
     * @return array{success: int, failed: int, results: array}
     */
    public function addUrls(array $urls): array
    {
        $results = [];
        $success = 0;
        $failed = 0;

        foreach ($urls as $url) {
            $result = $this->addUrl($url);
            $results[] = array_merge(['url' => $url], $result);

            if ($result['success']) {
                $success++;
            } else {
                $failed++;
            }
        }

        return [
            'success' => $success,
            'failed' => $failed,
            'results' => $results
        ];
    }

    /**
     * Add raw text as knowledge
     */
    public function addText(string $text, string $source, ?string $title = null): bool
    {
        if (empty(trim($text))) {
            return false;
        }

        $this->documents[] = [
            'source' => $source,
            'title' => $title,
            'content' => trim($text),
            'addedAt' => date('Y-m-d H:i:s')
        ];

        return true;
    }

    public function getDocuments(): array
    {
        return $this->documents;
    }

    public function count(): int
    {
        return count($this->documents);
    }

    public function isEmpty(): bool
    {
        return empty($this->documents);
    }

    public function clear(): void
    {
        $this->documents = [];
    }

    public function remove(int $index): bool
    {
        if (!isset($this->documents[$index])) {
            return false;
        }

        unset($this->documents[$index]);
        $this->documents = array_values($this->documents);
        return true;
    }

    /**
     * Build context string for AI prompt
     */
    public function buildContext(?int $maxChars = null): string
    {
        if (empty($this->documents)) {
            return '';
        }

        $parts = [];
        $parts[] = "=== KNOWLEDGE BASE ===";
        $parts[] = "Use the following information to answer questions:\n";

        $totalChars = 0;
        $headerChars = strlen(implode("\n", $parts));

        foreach ($this->documents as $index => $doc) {
            $docParts = [];
            $docParts[] = "--- Document " . ($index + 1) . " ---";

            if ($doc['title']) {
                $docParts[] = "Title: " . $doc['title'];
            }
            $docParts[] = "Source: " . $doc['source'];
            $docParts[] = "";
            $docParts[] = $doc['content'];
            $docParts[] = "";

            $docText = implode("\n", $docParts);
            $docChars = strlen($docText);

            if ($maxChars !== null && ($headerChars + $totalChars + $docChars) > $maxChars) {
                $remaining = $maxChars - $headerChars - $totalChars - 100;
                if ($remaining > 200) {
                    $docParts[count($docParts) - 2] = substr($doc['content'], 0, $remaining) . '...';
                    $parts[] = implode("\n", $docParts);
                }
                break;
            }

            $parts[] = $docText;
            $totalChars += $docChars;
        }

        $parts[] = "=== END KNOWLEDGE BASE ===\n";

        return implode("\n", $parts);
    }

    /**
     * @return array{count: int, sources: array, totalChars: int}
     */
    public function getSummary(): array
    {
        $sources = [];
        $totalChars = 0;

        foreach ($this->documents as $doc) {
            $sources[] = [
                'source' => $doc['source'],
                'title' => $doc['title'],
                'chars' => strlen($doc['content'])
            ];
            $totalChars += strlen($doc['content']);
        }

        return [
            'count' => count($this->documents),
            'sources' => $sources,
            'totalChars' => $totalChars
        ];
    }

    public function save(string $path): bool
    {
        $data = json_encode([
            'version' => '1.0',
            'savedAt' => date('Y-m-d H:i:s'),
            'documents' => $this->documents
        ], JSON_PRETTY_PRINT);

        if ($data === false) {
            return false;
        }

        return file_put_contents($path, $data) !== false;
    }

    public function load(string $path): bool
    {
        if (!file_exists($path)) {
            return false;
        }

        $content = file_get_contents($path);
        if ($content === false) {
            return false;
        }

        $data = json_decode($content, true);
        if (!is_array($data) || !isset($data['documents'])) {
            return false;
        }

        $this->documents = $data['documents'];
        return true;
    }

    /**
     * Search documents for keyword (simple search)
     */
    public function search(string $keyword): array
    {
        $keyword = strtolower($keyword);
        $results = [];

        foreach ($this->documents as $index => $doc) {
            $content = strtolower($doc['content']);
            $title = strtolower($doc['title'] ?? '');

            if (str_contains($content, $keyword) || str_contains($title, $keyword)) {
                $results[] = array_merge(['index' => $index], $doc);
            }
        }

        return $results;
    }

    public function getFetcher(): UrlFetcher
    {
        return $this->fetcher;
    }
}
