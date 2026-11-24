<?php
declare(strict_types=1);

namespace FlatfileCms;

final class Markdown
{
    public static function render(string $markdown): string
    {
        $lines = preg_split('/\r?\n/', $markdown) ?: [];
        $html = [];
        $paragraph = [];

        $flush = static function () use (&$paragraph, &$html): void {
            if ($paragraph !== []) {
                $html[] = '<p class="mt-5 text-lg leading-8 text-slate-300">' . self::inline(implode(' ', $paragraph)) . '</p>';
                $paragraph = [];
            }
        };

        foreach ($lines as $line) {
            if (trim($line) === '') {
                $flush();
                continue;
            }
            if (preg_match('/^(#{1,3})\s+(.+)$/', $line, $matches)) {
                $flush();
                $level = strlen($matches[1]);
                $classes = match ($level) {
                    1 => 'mt-10 text-3xl font-bold tracking-tight text-white',
                    2 => 'mt-10 text-2xl font-bold tracking-tight text-white',
                    default => 'mt-8 text-xl font-semibold text-white',
                };
                $html[] = "<h{$level} class=\"{$classes}\">" . self::inline($matches[2]) . "</h{$level}>";
                continue;
            }
            $paragraph[] = trim($line);
        }
        $flush();

        return implode("\n", $html);
    }

    private static function inline(string $text): string
    {
        $escaped = htmlspecialchars($text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        return preg_replace_callback(
            '/\[([^\]]+)]\((https?:\/\/[^\s)]+|\/[A-Za-z0-9\/_-]*)\)/',
            static fn (array $match): string => '<a class="font-semibold text-cyan-300 underline decoration-cyan-300/40 underline-offset-4 hover:text-cyan-200" href="' . $match[2] . '">' . $match[1] . '</a>',
            $escaped
        ) ?? $escaped;
    }
}
