<?php

declare(strict_types=1);

use Framework\Routing\Attribute\Route;
use Framework\Http\Request;
use Framework\Http\Response;
use function Framework\UI\{Document, div, h1, form, input, p, cache_ui};
use function Framework\Database\paginate;

/**
 * 演示：留言板列表 (含分页)
 */
#[Route(path: '/guestbook', methods: ['GET'])]
function ShowGuestbook(Request $request) {
    // 1. 分页获取数据 (原生 SQL 增强)
    $paginator = paginate("SELECT * FROM messages ORDER BY id DESC", [], 5);

    // 2. 局部缓存静态标题
    $header = cache_ui('gb_header', fn() => h1([], "留言板"));

    return Document("留言板", [], [
        $header,
        
        // 留言表单 (自动处理 CSRF)
        form(['action' => '/guestbook', 'method' => 'POST', 'style' => 'margin-bottom:20px;'],
            div([], "你的名字: ", input(['name' => 'author', 'required' => true])),
            div([], "留言内容: ", input(['name' => 'content', 'required' => true])),
            input(['type' => 'submit', 'value' => '提交留言'])
        ),

        // 留言列表
        ...array_map(fn($msg) => 
            div(['style' => 'border-bottom:1px solid #eee; padding:10px;'],
                p(['style' => 'font-weight:bold;'], htmlspecialchars($msg['author'])),
                p([], htmlspecialchars($msg['content']))
            ), 
            $paginator->items
        ),

        // 分页组件
        $paginator->links()
    ]);
}

/**
 * 演示：提交留言 (含验证)
 */
#[Route(path: '/guestbook', methods: ['POST'])]
function StoreGuestbook(Request $request) {
    // 1. 验证
    $data = $request->validate([
        'author' => 'required',
        'content' => 'required'
    ]);

    // 2. 插入数据 (原生 SQL)
    db()->execute(
        "INSERT INTO messages (author, content) VALUES (?, ?)", 
        [$data['author'], $data['content']]
    );

    // 3. 返回并闪存消息 (待完善 session 闪存 UI)
    header("Location: /guestbook");
    exit;
}
