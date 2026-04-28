<?php

declare(strict_types=1);

namespace Framework\UX\RichEditor;

class DocumentParser
{
    protected array $processors = [];
    protected array $filters = [];

    public static function htmlToText(string $html): string
    {
        $text = strip_tags($html);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/\s+/', ' ', $text);
        return trim($text);
    }

    public static function htmlToMarkdown(string $html): string
    {
        $markdown = $html;

        $replacements = [
            '/<h1[^>]*>(.*?)<\/h1>/i' => "# $1\n\n",
            '/<h2[^>]*>(.*?)<\/h2>/i' => "## $1\n\n",
            '/<h3[^>]*>(.*?)<\/h3>/i' => "### $1\n\n",
            '/<h4[^>]*>(.*?)<\/h4>/i' => "#### $1\n\n",
            '/<h5[^>]*>(.*?)<\/h5>/i' => "##### $1\n\n",
            '/<h6[^>]*>(.*?)<\/h6>/i' => "###### $1\n\n",
            '/<p[^>]*>(.*?)<\/p>/is' => "$1\n\n",
            '/<br\s*\/?>/i' => "  \n",
            '/<strong[^>]*>(.*?)<\/strong>/i' => '**$1**',
            '/<b[^>]*>(.*?)<\/b>/i' => '**$1**',
            '/<em[^>]*>(.*?)<\/em>/i' => '*$1*',
            '/<i[^>]*>(.*?)<\/i>/i' => '*$1*',
            '/<u[^>]*>(.*?)<\/u>/i' => '_$1_',
            '/<s[^>]*>(.*?)<\/s>/i' => '~~$1~~',
            '/<strike[^>]*>(.*?)<\/strike>/i' => '~~$1~~',
            '/<del[^>]*>(.*?)<\/del>/i' => '~~$1~~',
            '/<code[^>]*>(.*?)<\/code>/i' => '`$1`',
            '/<pre[^>]*>(.*?)<\/pre>/is' => "```\n$1\n```\n\n",
            '/<blockquote[^>]*>(.*?)<\/blockquote>/is' => "> $1\n\n",
            '/<ul[^>]*>(.*?)<\/ul>/is' => "\n$1\n",
            '/<ol[^>]*>(.*?)<\/ol>/is' => "\n$1\n",
            '/<li[^>]*>(.*?)<\/li>/i' => "- $1\n",
            '/<a[^>]*href="([^"]*)"[^>]*>(.*?)<\/a>/i' => '[$2]($1)',
            '/<img[^>]*src="([^"]*)"[^>]*alt="([^"]*)"[^>]*>/i' => '![$2]($1)',
            '/<img[^>]*src="([^"]*)"[^>]*>/i' => '![]($1)',
        ];

        foreach ($replacements as $pattern => $replacement) {
            $markdown = preg_replace($pattern, $replacement, $markdown);
        }

        $markdown = html_entity_decode($markdown, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $markdown = preg_replace('/\n{3,}/', "\n\n", $markdown);

        return trim($markdown);
    }

    public static function markdownToHtml(string $markdown): string
    {
        $html = $markdown;

        $html = preg_replace('/^###### (.*$)/im', '<h6>$1</h6>', $html);
        $html = preg_replace('/^##### (.*$)/im', '<h5>$1</h5>', $html);
        $html = preg_replace('/^#### (.*$)/im', '<h4>$1</h4>', $html);
        $html = preg_replace('/^### (.*$)/im', '<h3>$1</h3>', $html);
        $html = preg_replace('/^## (.*$)/im', '<h2>$1</h2>', $html);
        $html = preg_replace('/^# (.*$)/im', '<h1>$1</h1>', $html);

        $html = preg_replace('/\*\*\*(.*?)\*\*\*/s', '<strong><em>$1</em></strong>', $html);
        $html = preg_replace('/\*\*(.*?)\*\*/s', '<strong>$1</strong>', $html);
        $html = preg_replace('/\*(.*?)\*/s', '<em>$1</em>', $html);
        $html = preg_replace('/_(.*?)_/s', '<u>$1</u>', $html);
        $html = preg_replace('/~~(.*?)~~/s', '<del>$1</del>', $html);

        $html = preg_replace('/`([^`]+)`/', '<code>$1</code>', $html);
        $html = preg_replace('/```(.*?)```/s', '<pre><code>$1</code></pre>', $html);

        $html = preg_replace('/^> (.*$)/im', '<blockquote>$1</blockquote>', $html);

        $html = preg_replace_callback('/^\- (.*$)/im', function($matches) {
            return '<ul><li>' . $matches[1] . '</li></ul>';
        }, $html);
        $html = preg_replace('/<\/ul>\s*<ul>/', '', $html);

        $html = preg_replace('/\[([^\]]+)\]\(([^)]+)\)/', '<a href="$2">$1</a>', $html);
        $html = preg_replace('/!\[([^\]]*)\]\(([^)]+)\)/', '<img src="$2" alt="$1">', $html);

        $html = preg_replace('/^(?!<[a-z])(.*$)/im', '<p>$1</p>', $html);
        $html = preg_replace('/\n{2,}/', "\n", $html);

        return trim($html);
    }

    public static function extractPlainText(string $html, int $maxLength = 0): string
    {
        $text = self::htmlToText($html);

        if ($maxLength > 0 && mb_strlen($text) > $maxLength) {
            $text = mb_substr($text, 0, $maxLength);
            $text = rtrim($text);
            $text .= '...';
        }

        return $text;
    }

    public static function truncateHtml(string $html, int $maxLength, string $suffix = '...'): string
    {
        $text = self::htmlToText($html);

        if (mb_strlen($text) <= $maxLength) {
            return $html;
        }

        $truncated = mb_substr($text, 0, $maxLength);
        $truncated = rtrim($truncated);

        return htmlspecialchars($truncated) . $suffix;
    }

    public static function sanitize(string $html, array|string|null $allowedTags = null): string
    {
        if ($allowedTags === null) {
            $allowedTags = '<p><br><strong><b><em><i><u><s><strike><del><h1><h2><h3><h4><h5><h6><blockquote><pre><code><ul><ol><li><a><img><div><span>';
        }

        $html = strip_tags($html, $allowedTags);

        $html = preg_replace_callback('/<([a-z][a-z0-9]*)[^>]*?\s+(?:on|javascript:)[^>]*>/i', function ($m) {
            return '<' . $m[1] . '>';
        }, $html);

        $html = preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', '', $html);
        $html = preg_replace('/<style\b[^>]*>(.*?)<\/style>/is', '', $html);

        return $html;
    }

    public static function wordCount(string $content): int
    {
        $text = self::htmlToText($content);
        $text = preg_replace('/[^\p{L}\p{N}]+/u', ' ', $text);
        $words = preg_split('/\s+/', trim($text), -1, PREG_SPLIT_NO_EMPTY);
        return count($words);
    }

    public static function characterCount(string $content, bool $includeSpaces = true): int
    {
        $text = self::htmlToText($content);

        if (!$includeSpaces) {
            $text = preg_replace('/\s+/', '', $text);
        }

        return mb_strlen($text);
    }

    public function registerProcessor(string $name, callable $processor): static
    {
        $this->processors[$name] = $processor;
        return $this;
    }

    public function registerFilter(string $name, callable $filter): static
    {
        $this->filters[$name] = $filter;
        return $this;
    }

    public function process(string $content, array $processorNames = []): string
    {
        foreach ($processorNames as $name) {
            if (isset($this->processors[$name])) {
                $content = ($this->processors[$name])($content);
            }
        }

        return $content;
    }

    public function filter(string $content, array $filterNames = []): string
    {
        foreach ($filterNames as $name) {
            if (isset($this->filters[$name])) {
                $content = ($this->filters[$name])($content);
            }
        }

        return $content;
    }
}
