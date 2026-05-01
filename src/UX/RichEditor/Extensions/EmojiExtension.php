<?php

declare(strict_types=1);

namespace Framework\UX\RichEditor\Extensions;

use Framework\UX\RichEditor\RichEditorExtension;

class EmojiExtension extends RichEditorExtension
{
    protected array $emojiMap = [];

    protected function getDefaultConfig(): array
    {
        return [
            'shortcodes' => true,
            'unicode' => true,
        ];
    }

    protected function initialize(): void
    {
        $this->icon = '<svg viewBox="0 0 24 24" width="16" height="16"><path fill="currentColor" d="M11.99 2C6.47 2 2 6.48 2 12s4.47 10 9.99 10C17.52 22 22 17.52 22 12S17.52 2 11.99 2zM12 20c-4.42 0-8-3.58-8-8s3.58-8 8-8 8 3.58 8 8-3.58 8-8 8zm3.5-9c.83 0 1.5-.67 1.5-1.5S16.33 8 15.5 8 14 8.67 14 9.5s.67 1.5 1.5 1.5zm-7 0c.83 0 1.5-.67 1.5-1.5S9.33 8 8.5 8 7 8.67 7 9.5 7.67 11 8.5 11zm3.5 6.5c2.33 0 4.31-1.46 5.11-3.5H6.89c.8 2.04 2.78 3.5 5.11 3.5z"/></svg>';
        $this->title = t('editor.emoji');

        $this->emojiMap = [
            ':smile:' => '😊',
            ':happy:' => '😃',
            ':sad:' => '😢',
            ':love:' => '❤️',
            ':like:' => '👍',
            ':dislike:' => '👎',
            ':fire:' => '🔥',
            ':star:' => '⭐',
            ':check:' => '✅',
            ':cross:' => '❌',
            ':warning:' => '⚠️',
            ':idea:' => '💡',
            ':rocket:' => '🚀',
            ':party:' => '🎉',
            ':coffee:' => '☕',
            ':code:' => '💻',
            ':bug:' => '🐛',
            ':docs:' => '📚',
        ];
    }

    public function getName(): string
    {
        return 'emoji';
    }

    public function setEmojiMap(array $map): static
    {
        $this->emojiMap = array_merge($this->emojiMap, $map);
        return $this;
    }

    public function execute(string $content, array $params = []): string
    {
        $emoji = $params['emoji'] ?? '';
        return $content . $emoji;
    }

    public function parse(string $content): string
    {
        if (!$this->config['shortcodes']) {
            return $content;
        }

        return strtr($content, $this->emojiMap);
    }

    public function renderPreview(string $content): string
    {
        return $this->parse($content);
    }

    public function getAvailableEmojis(): array
    {
        return $this->emojiMap;
    }

    public function getToolbarButton(string $editorId): ?\Framework\View\Base\Element
    {
        $btn = parent::getToolbarButton($editorId);
        if ($btn) {
            $btn->data('emoji-map', json_encode($this->emojiMap));
        }
        return $btn;
    }
}
