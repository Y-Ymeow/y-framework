<?php

declare(strict_types=1);

namespace Framework\UX\RichEditor\Blocks;

use Framework\UX\RichEditor\BlockType;
use Framework\View\Base\Element;

class VideoBlock extends BlockType
{
    public function __construct()
    {
        parent::__construct('video');
        $this->title = t('ux:editor.blocks.video');
        $this->icon = '<svg viewBox="0 0 24 24" width="18" height="18"><path fill="currentColor" d="M17 10.5V7c0-.55-.45-1-1-1H4c-.55 0-1 .45-1 1v10c0 .55.45 1 1 1h12c.55 0 1-.45 1-1v-3.5l4 4v-11l-4 4z"/></svg>';
        $this->category = 'media';
        $this->attribute('src', ['type' => 'string', 'default' => '']);
        $this->attribute('type', ['type' => 'string', 'default' => 'embed']);
        $this->attribute('width', ['type' => 'string', 'default' => '100%']);
        $this->attribute('aspectRatio', ['type' => 'string', 'default' => '16/9']);
        $this->attribute('caption', ['type' => 'string', 'default' => '']);
    }

    public function renderElement(array $attributes, array $innerBlocks = []): Element
    {
        $src = $attributes['src'] ?? '';
        $type = $attributes['type'] ?? 'embed';
        $width = $attributes['width'] ?? '100%';
        $ratio = $attributes['aspectRatio'] ?? '16/9';
        $caption = $attributes['caption'] ?? '';

        $figure = Element::make('figure')
            ->style("width:{$width};margin:0.75rem auto");

        $wrapper = Element::make('div')
            ->style("position:relative;padding-bottom:calc(100% / ({$ratio}));height:0;overflow:hidden;border-radius:0.375rem");

        if ($type === 'embed' && $src) {
            $iframe = Element::make('iframe')
                ->attr('src', $this->normalizeEmbedUrl($src))
                ->attr('frameborder', '0')
                ->attr('allowfullscreen', 'true')
                ->style('position:absolute;top:0;left:0;width:100%;height:100%');
            $wrapper->child($iframe);
        } elseif ($type === 'native' && $src) {
            $video = Element::make('video')
                ->attr('src', $src)
                ->attr('controls', 'true')
                ->style('position:absolute;top:0;left:0;width:100%;height:100%');
            $wrapper->child($video);
        }

        $figure->child($wrapper);

        if ($caption) {
            $figure->child(
                Element::make('figcaption')
                    ->style('text-align:center;font-size:0.875rem;color:#6b7280;margin-top:0.5rem')
                    ->text($caption)
            );
        }

        return $figure;
    }

    private function normalizeEmbedUrl(string $url): string
    {
        if (preg_match('/(?:youtube\.com\/watch\?v=|youtu\.be\/)([a-zA-Z0-9_-]+)/', $url, $m)) {
            return 'https://www.youtube.com/embed/' . $m[1];
        }
        if (preg_match('/bilibili\.com\/video\/(BV[a-zA-Z0-9]+)/', $url, $m)) {
            return 'https://player.bilibili.com/player.html?bvid=' . $m[1];
        }
        if (preg_match('/vimeo\.com\/(\d+)/', $url, $m)) {
            return 'https://player.vimeo.com/video/' . $m[1];
        }
        return $url;
    }
}
