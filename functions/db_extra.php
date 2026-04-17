<?php

declare(strict_types=1);

namespace Framework\Database;

/**
 * 极简分页对象
 */
final class Paginator
{
    public function __construct(
        public readonly array $items,
        public readonly int $total,
        public readonly int $perPage,
        public readonly int $currentPage
    ) {
    }

    public function lastPage(): int
    {
        return (int) ceil($this->total / $this->perPage);
    }

    /**
     * 简单的 HTML 分页链接渲染
     */
    public function links(): string
    {
        use function Framework\UI\{div, a};
        
        $links = [];
        for ($i = 1; $i <= $this->lastPage(); $i++) {
            $links[] = a(
                [
                    'href' => "?page={$i}", 
                    'style' => $i === $this->currentPage ? 'font-weight:bold;margin:5px;' : 'margin:5px;'
                ], 
                (string)$i
            );
        }
        return div(['class' => 'pagination'], ...$links);
    }
}

/**
 * 分页助手函数
 */
function paginate(string $sql, array $bindings = [], int $perPage = 15): Paginator
{
    $request = app(\Framework\Http\Request::class);
    $page = (int) $request->query('page', 1);
    $offset = ($page - 1) * $perPage;

    $db = connection();
    
    // 获取总数 (简单包装)
    $countSql = "SELECT COUNT(*) as total FROM ({$sql}) as t";
    $total = (int) $db->first($countSql, $bindings)['total'];

    // 获取数据
    $dataSql = "{$sql} LIMIT {$perPage} OFFSET {$offset}";
    $items = $db->select($dataSql, $bindings);

    return new Paginator($items, $total, $perPage, $page);
}
