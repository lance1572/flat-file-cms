<?php
declare(strict_types=1);

namespace FlatfileCms;

use RuntimeException;

final class ContentRepository
{
    public function __construct(private readonly string $pagesPath)
    {
    }

    /** @return array<string, mixed>|null */
    public function find(string $slug): ?array
    {
        if (!preg_match('/^[a-z0-9][a-z0-9-]*$/', $slug)) {
            return null;
        }

        $path = $this->pagesPath . DIRECTORY_SEPARATOR . $slug . '.md';
        if (!is_file($path)) {
            return null;
        }

        $raw = file_get_contents($path);
        if ($raw === false) {
            throw new RuntimeException("Unable to read page: {$slug}");
        }

        return $this->parse($raw, $slug);
    }

    /** @return array<string, mixed> */
    private function parse(string $raw, string $fallbackSlug): array
    {
        $metadata = [];
        $body = $raw;

        if (str_starts_with($raw, "---\n")) {
            $end = strpos($raw, "\n---\n", 4);
            if ($end === false) {
                throw new RuntimeException("Unclosed front matter in {$fallbackSlug}.md");
            }

            $metadata = $this->parseFrontMatter(substr($raw, 4, $end - 4));
            $body = substr($raw, $end + 5);
        }

        return [
            'title' => (string) ($metadata['title'] ?? $fallbackSlug),
            'slug' => (string) ($metadata['slug'] ?? $fallbackSlug),
            'template' => (string) ($metadata['template'] ?? 'default'),
            'description' => (string) ($metadata['description'] ?? ''),
            'visible' => filter_var($metadata['visible'] ?? true, FILTER_VALIDATE_BOOL),
            'body' => trim($body),
        ];
    }

    /** @return array<string, string> */
    private function parseFrontMatter(string $frontMatter): array
    {
        $data = [];
        foreach (preg_split('/\r?\n/', $frontMatter) ?: [] as $line) {
            if ($line === '' || str_starts_with(ltrim($line), '#')) {
                continue;
            }
            if (!preg_match('/^([a-z_]+):\s*(.*)$/i', $line, $matches)) {
                throw new RuntimeException('Invalid front matter entry: ' . $line);
            }
            $data[$matches[1]] = trim($matches[2], " \t\"'");
        }
        return $data;
    }
}
