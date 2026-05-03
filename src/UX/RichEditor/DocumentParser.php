<?php

declare(strict_types=1);

namespace Framework\UX\RichEditor;

/**
 * 文档解析器
 *
 * 静态工具类，用于 HTML、Markdown、文本之间的相互转换、清洗、截断、字数统计。
 *
 * @ux-category RichEditor
 * @ux-since 1.0.0
 * @ux-example DocumentParser::htmlToMarkdown('<p>Hello <strong>World</strong></p>')
 * @ux-example DocumentParser::markdownToHtml('# Title\n\n**Bold** text')
 * @ux-example DocumentParser::sanitize($html, '<p><strong><em>')
 * @ux-example DocumentParser::wordCount($content)
 */
class DocumentParser
{
    protected array $processors = [];
    protected array $filters = [];

    /**
     * HTML 转纯文本（移除所有标签）
     * @param string $html HTML 内容
     * @return string 纯文本
     * @ux-example DocumentParser::htmlToText('<p>Hello <strong>World</strong></p>')
     */
    public static function htmlToText(string $html): string
    {
        $text = strip_tags($html);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/\s+/', ' ', $text);
        return trim($text);
    }

    /**
     * HTML 转 Markdown
     * @param string $html HTML 内容
     * @return string Markdown 文本
     * @ux-example DocumentParser::htmlToMarkdown('<h1>Title</h1><p><strong>Bold</strong> text</p>')
     */
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

    /**
     * Markdown 转 HTML
     * @param string $markdown Markdown 文本
     * @return string HTML 内容
     * @ux-example DocumentParser::markdownToHtml('# Title\n\n**Bold** text')
     */
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

    /**
     * 提取纯文本（可选截断）
     * @param string $html HTML 内容
     * @param int $maxLength 最大长度（0 表示不截断）
     * @return string 纯文本
     * @ux-example DocumentParser::extractPlainText($html, 100)
     */
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

    /**
     * 截断 HTML 内容（基于纯文本长度）
     * @param string $html HTML 内容
     * @param int $maxLength 最大长度
     * @param string $suffix 后缀
     * @return string 截断后的 HTML
     * @ux-example DocumentParser::truncateHtml($html, 50)
     */
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

    /**
     * 清洗 HTML 内容（移除危险标签和脚本）
     * @param string $html HTML 内容
     * @param array|string|null $allowedTags 允许标签（null 使用默认白名单）
     * @return string 清洗后的 HTML
     * @ux-example DocumentParser::sanitize($html, '<p><strong><em>')
     * @ux-default allowedTags='<p><br><strong><b><em><i><u><s><strike><del><h1>...<span>'
     */
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

    /**
     * 统计字数（去除标点和空格）
     * @param string $content 内容
     * @return int 字数
     * @ux-example DocumentParser::wordCount($content)
     */
    public static function wordCount(string $content): int
    {
        $text = self::htmlToText($content);
        $text = preg_replace('/[^\p{L}\p{N}]+/u', ' ', $text);
        $words = preg_split('/\s+/', trim($text), -1, PREG_SPLIT_NO_EMPTY);
        return count($words);
    }

    /**
     * 统计字符数
     * @param string $content 内容
     * @param bool $includeSpaces 是否包含空格
     * @return int 字符数
     * @ux-example DocumentParser::characterCount($content, false)
     * @ux-default includeSpaces=true
     */
    public static function characterCount(string $content, bool $includeSpaces = true): int
    {
        $text = self::htmlToText($content);

        if (!$includeSpaces) {
            $text = preg_replace('/\s+/', '', $text);
        }

        return mb_strlen($text);
    }

    /**
     * 注册内容处理器
     * @param string $name 处理器名称
     * @param callable $processor 处理器回调
     * @return static
     */
    public function registerProcessor(string $name, callable $processor): static
    {
        $this->processors[$name] = $processor;
        return $this;
    }

    /**
     * 注册内容过滤器
     * @param string $name 过滤器名称
     * @param callable $filter 过滤器回调
     * @return static
     */
    public function registerFilter(string $name, callable $filter): static
    {
        $this->filters[$name] = $filter;
        return $this;
    }

    /**
     * 按顺序执行处理器
     * @param string $content 内容
     * @param array $processorNames 处理器名称列表
     * @return string 处理后的内容
     */
    public function process(string $content, array $processorNames = []): string
    {
        foreach ($processorNames as $name) {
            if (isset($this->processors[$name])) {
                $content = ($this->processors[$name])($content);
            }
        }

        return $content;
    }

    /**
     * 按顺序执行过滤器
     * @param string $content 内容
     * @param array $filterNames 过滤器名称列表
     * @return string 过滤后的内容
     */
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
