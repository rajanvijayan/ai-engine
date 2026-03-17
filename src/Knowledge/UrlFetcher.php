<?php

declare(strict_types=1);

namespace AIEngine\Knowledge;

/**
 * URL Content Fetcher
 *
 * Fetches and extracts text content from URLs
 */
class UrlFetcher
{
    protected int $timeout;
    protected string $userAgent;

    public function __construct(int $timeout = 30, ?string $userAgent = null)
    {
        $this->timeout = $timeout;
        $this->userAgent = $userAgent ?? 'AIEngine/1.0 (Knowledge Fetcher)';
    }

    /**
     * Fetch content from a URL
     *
     * @return array{success: bool, content?: string, title?: string, error?: string, url: string}
     */
    public function fetch(string $url): array
    {
        if (!$this->isValidUrl($url)) {
            return [
                'success' => false,
                'error' => 'Invalid URL format',
                'url' => $url
            ];
        }

        $html = $this->fetchHtml($url);

        if ($html === null) {
            return [
                'success' => false,
                'error' => 'Failed to fetch URL content',
                'url' => $url
            ];
        }

        $title = $this->extractTitle($html);
        $content = $this->extractText($html);

        if (empty(trim($content))) {
            return [
                'success' => false,
                'error' => 'No text content found in URL',
                'url' => $url
            ];
        }

        return [
            'success' => true,
            'url' => $url,
            'title' => $title,
            'content' => $content
        ];
    }

    /**
     * Fetch multiple URLs
     */
    public function fetchMultiple(array $urls): array
    {
        $results = [];
        foreach ($urls as $url) {
            $results[] = $this->fetch($url);
        }
        return $results;
    }

    protected function isValidUrl(string $url): bool
    {
        return filter_var($url, FILTER_VALIDATE_URL) !== false
            && preg_match('/^https?:\/\//i', $url) === 1;
    }

    protected function fetchHtml(string $url): ?string
    {
        $options = [
            'http' => [
                'header' => "User-Agent: {$this->userAgent}\r\n" .
                           "Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8\r\n" .
                           "Accept-Language: en-US,en;q=0.5\r\n",
                'method' => 'GET',
                'timeout' => $this->timeout,
                'follow_location' => true,
                'max_redirects' => 5,
                'ignore_errors' => true
            ],
            'ssl' => [
                'verify_peer' => true,
                'verify_peer_name' => true
            ]
        ];

        $context = stream_context_create($options);

        set_error_handler(function (int $errno, string $errstr): bool {
            return true;
        });
        $html = file_get_contents($url, false, $context);
        restore_error_handler();

        if ($html === false) {
            return null;
        }

        return $html;
    }

    protected function extractTitle(string $html): ?string
    {
        if (preg_match('/<title[^>]*>(.*?)<\/title>/is', $html, $matches)) {
            return html_entity_decode(trim($matches[1]), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }
        return null;
    }

    public function extractText(string $html): string
    {
        $html = preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', '', $html) ?? $html;
        $html = preg_replace('/<style\b[^>]*>(.*?)<\/style>/is', '', $html) ?? $html;
        $html = preg_replace('/<!--.*?-->/s', '', $html) ?? $html;
        $html = preg_replace('/<(header|footer|nav|aside)\b[^>]*>(.*?)<\/\1>/is', '', $html) ?? $html;

        $mainContent = $this->extractMainContent($html);
        if (!empty($mainContent)) {
            $html = $mainContent;
        }

        $html = preg_replace('/<\/(p|div|h[1-6]|li|tr|br)[^>]*>/i', "\n", $html) ?? $html;
        $html = preg_replace('/<br\s*\/?>/i', "\n", $html) ?? $html;

        $text = strip_tags($html);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/[ \t]+/', ' ', $text) ?? $text;
        $text = preg_replace('/\n{3,}/', "\n\n", $text) ?? $text;
        $text = trim($text);

        return $text;
    }

    protected function extractMainContent(string $html): ?string
    {
        $patterns = [
            '/<main\b[^>]*>(.*?)<\/main>/is',
            '/<article\b[^>]*>(.*?)<\/article>/is',
            '/<div[^>]*\bclass=["\'][^"\']*\b(content|main|post|article|entry)[^"\']*["\'][^>]*>(.*?)<\/div>/is',
            '/<div[^>]*\bid=["\'][^"\']*\b(content|main|post|article|entry)[^"\']*["\'][^>]*>(.*?)<\/div>/is',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $html, $matches)) {
                $content = end($matches);
                if (strlen(strip_tags($content)) > 100) {
                    return $content;
                }
            }
        }

        return null;
    }

    /**
     * @return array{title?: string, description?: string, keywords?: string}
     */
    public function extractMetadata(string $html): array
    {
        $metadata = [];

        $metadata['title'] = $this->extractTitle($html);

        if (preg_match('/<meta[^>]*\bname=["\']description["\'][^>]*\bcontent=["\']([^"\']*)["\'][^>]*>/i', $html, $matches) ||
            preg_match('/<meta[^>]*\bcontent=["\']([^"\']*)["\'][^>]*\bname=["\']description["\'][^>]*>/i', $html, $matches)) {
            $metadata['description'] = html_entity_decode(trim($matches[1]), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }

        if (preg_match('/<meta[^>]*\bname=["\']keywords["\'][^>]*\bcontent=["\']([^"\']*)["\'][^>]*>/i', $html, $matches) ||
            preg_match('/<meta[^>]*\bcontent=["\']([^"\']*)["\'][^>]*\bname=["\']keywords["\'][^>]*>/i', $html, $matches)) {
            $metadata['keywords'] = html_entity_decode(trim($matches[1]), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }

        return array_filter($metadata);
    }

    public function setTimeout(int $timeout): void
    {
        $this->timeout = $timeout;
    }

    public function setUserAgent(string $userAgent): void
    {
        $this->userAgent = $userAgent;
    }
}
