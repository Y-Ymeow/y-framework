<?php

use PhpParser\Node\Expr\Cast\Void_;

return [
    'format' => [
        'bold' => '加粗',
        'italic' => '斜体',
        'underline' => '下划线',
        'strikethrough' => '删除线',
        'code' => '行内代码',
        'link' => '链接',
        'mention' => '提及',
    ],
    'blocks' => [
        'paragraph' => '段落',
        'heading' => '标题',
        'image' => '图片',
        'quote' => '引用',
        'code' => '代码块',
        'list' => '列表',
        'divider' => '分割线',
        'callout' => '提示框',
        'table' => '表格',
        'video' => '视频',
        'columns' => '列数',
    ],
    'placeholder' => [
        'paragraph' => '输入段落文字...',
        'heading' => '输入标题...',
        'quote' => '输入引用内容...',
        'code' => '输入代码...',
        'image_src' => '图片地址',
        'image_alt' => '替代文字',
        'image_caption' => '图片说明',
        'quote_cite' => '引用来源',
        'code_language' => '语言（如 php, js）',
        'list_add' => '+ 添加列表项',
    ],
    'inserter' => [
        'title' => '添加 Block',
    ],
    'slash' => [
        'placeholder' => '搜索 Block...',
    ],
    'link' => [
        'prompt' => '输入链接地址:',
    ],
    'category' => [
        'text' => '文本',
        'media' => '媒体',
        'common' => '常用',
    ],
];
