<?php
declare(strict_types=1);

namespace FlatfileCms;

use RuntimeException;
use InvalidArgumentException;

final class ContentRepository
{
    public function __construct(private readonly string $pagesPath)
    {
    }

    /** @return array<string, mixed>|null */
    public function find(string $slug): ?array
    {
        if (!$this->validSlug($slug)) {
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

    public function create(string $slug, string $title, string $description, string $body): bool
    {
        if (!preg_match('/\A[a-z0-9][a-z0-9-]{0,79}\z/', $slug) || $slug === 'admin') {
            throw new InvalidArgumentException('Use 1–80 lowercase letters, numbers, or hyphens for the slug. Start with a letter or number and choose a slug other than admin.');
        }
        $this->validateMetadata($title, $description);

        $markdown = "---\ntitle: \"{$title}\"\nslug: {$slug}\ntemplate: default\ndescription: \"{$description}\"\nvisible: true\n---\n\n"
            . trim($body) . "\n";
        return $this->write($slug, $markdown, false);
    }

    /** @return list<string> */
    public function slugs(): array
    {
        $files = glob($this->pagesPath . DIRECTORY_SEPARATOR . '*.md');
        if ($files === false) {
            throw new RuntimeException('Unable to list pages.');
        }

        $slugs = [];
        foreach ($files as $file) {
            $slug = basename($file, '.md');
            if (is_file($file) && $this->validSlug($slug)) {
                $slugs[] = $slug;
            }
        }
        return $slugs;
    }

    public function update(string $slug, string $title, string $description, string $body): bool
    {
        $this->validateMetadata($title, $description);
        if (!$this->validSlug($slug) || !is_file($this->path($slug))) {
            return false;
        }
        $raw = file_get_contents($this->path($slug));
        if ($raw === false) {
            throw new RuntimeException("Unable to read page: {$slug}");
        }
        [$frontMatter] = $this->splitPage($raw, $slug);
        $frontMatter = preg_replace('/^(?:title|description):[^\n]*(?:\n|$)/m', '', $frontMatter);
        $metadata = "title: \"{$title}\"\ndescription: \"{$description}\"\n";
        if ($frontMatter !== '') {
            $metadata .= $frontMatter . "\n";
        }
        return $this->write($slug, "---\n{$metadata}---\n\n" . $body . "\n", true);
    }

    public function delete(string $slug): bool
    {
        if (!$this->validSlug($slug) || !is_file($this->path($slug))) {
            return false;
        }
        if (!@unlink($this->path($slug))) {
            throw new RuntimeException('Unable to delete the page file.');
        }
        return true;
    }

    private function validSlug(string $slug): bool
    {
        return preg_match('/\A[a-z0-9][a-z0-9-]*\z/', $slug) === 1;
    }

    private function path(string $slug): string
    {
        return $this->pagesPath . DIRECTORY_SEPARATOR . $slug . '.md';
    }

    private function validateMetadata(string $title, string $description): void
    {
        if (trim($title) === '' || strpbrk($title . $description, "\r\n\0") !== false) {
            throw new InvalidArgumentException('Enter a title and keep the title and description on one line each.');
        }
    }

    private function write(string $slug, string $markdown, bool $replace): bool
    {
        $path = $this->path($slug);
        $temporary = @tempnam($this->pagesPath, '.page-');
        if ($temporary === false) {
            throw new RuntimeException('Unable to prepare the page file.');
        }
        try {
            if (@file_put_contents($temporary, $markdown) !== strlen($markdown)) {
                throw new RuntimeException('Unable to write the page file.');
            }
            if ($replace && !is_file($path)) {
                return false;
            }
            if (!($replace ? @rename($temporary, $path) : @link($temporary, $path))) {
                clearstatcache(true, $path);
                if (!$replace && file_exists($path)) {
                    return false;
                }
                throw new RuntimeException('Unable to publish the page file.');
            }
            return true;
        } finally {
            if (is_file($temporary)) {
                unlink($temporary);
            }
        }
    }

    /** @return array<string, mixed> */
    private function parse(string $raw, string $fallbackSlug): array
    {
        [$frontMatter, $body] = $this->splitPage($raw, $fallbackSlug);
        $metadata = $this->parseFrontMatter($frontMatter);

        return [
            'title' => (string) ($metadata['title'] ?? $fallbackSlug),
            'slug' => (string) ($metadata['slug'] ?? $fallbackSlug),
            'template' => (string) ($metadata['template'] ?? 'default'),
            'description' => (string) ($metadata['description'] ?? ''),
            'visible' => filter_var($metadata['visible'] ?? true, FILTER_VALIDATE_BOOL),
            'body' => trim($body),
        ];
    }

    /** @return array{string, string} */
    private function splitPage(string $raw, string $slug): array
    {
        $raw = str_replace("\r\n", "\n", $raw);
        if (!str_starts_with($raw, "---\n")) {
            return ['', $raw];
        }
        if (!preg_match('/^---\n(.*?)^---(?:\n|\z)/ms', $raw, $matches)) {
            throw new RuntimeException("Unclosed front matter in {$slug}.md");
        }
        return [rtrim($matches[1], "\n"), substr($raw, strlen($matches[0]))];
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
            $value = trim($matches[2]);
            $quote = $value[0] ?? '';
            if (strlen($value) >= 2 && ($quote === '"' || $quote === "'") && str_ends_with($value, $quote)) {
                $value = substr($value, 1, -1);
            }
            $data[$matches[1]] = $value;
        }
        return $data;
    }
}
