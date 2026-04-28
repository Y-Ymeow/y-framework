<?php

declare(strict_types=1);

namespace Framework\View;

use Framework\View\Base\Element;

/**
 * Table — 表格
 *
 *   Table::make()
 *       ->headers(['姓名', '邮箱', '状态'])
 *       ->rows([
 *           ['张三', 'z@test.com', '启用'],
 *           ['李四', 'l@test.com', '禁用'],
 *       ])
 *       ->striped()
 *       ->hoverable()
 */
class Table extends Element
{
    private array $headers = [];
    private array $rows = [];
    private bool $striped = true;
    private bool $hoverable = true;
    private bool $bordered = false;
    private bool $compact = false;
    private string $emptyText = '暂无数据';

    public function __construct()
    {
        parent::__construct('table');
    }

    public function headers(array $headers): static
    {
        $this->headers = $headers;
        return $this;
    }

    public function rows(array $rows): static
    {
        $this->rows = $rows;
        return $this;
    }

    public function row(array $row): static
    {
        $this->rows[] = $row;
        return $this;
    }

    public function striped(bool $v = true): static { $this->striped = $v; return $this; }
    public function hoverable(bool $v = true): static { $this->hoverable = $v; return $this; }
    public function bordered(bool $v = true): static { $this->bordered = $v; return $this; }
    public function compact(bool $v = true): static { $this->compact = $v; return $this; }
    public function emptyText(string $text): static { $this->emptyText = $text; return $this; }

    public function render(): string
    {
        $this->class('min-w-full divide-y divide-gray-200');
        if ($this->bordered) $this->class('border');

        $thCls = $this->compact ? 'px-2 py-2 text-left text-xs font-medium text-gray-500 uppercase' : 'px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider';
        $tdCls = $this->compact ? 'px-2 py-2 whitespace-nowrap text-sm' : 'px-4 py-3 whitespace-nowrap text-sm';
        $trCls = '';
        if ($this->striped) $trCls .= ' odd:bg-white even:bg-gray-50';
        if ($this->hoverable) $trCls .= ' hover:bg-gray-100';

        // thead
        $thead = new Element('thead');
        $headTr = new Element('tr');
        foreach ($this->headers as $h) {
            $th = new Element('th');
            $th->class($thCls)->text((string)$h);
            $headTr->child($th);
        }
        $thead->child($headTr);
        $this->child($thead);

        // tbody
        $tbody = new Element('tbody');
        if (empty($this->rows)) {
            $tr = new Element('tr')->class($trCls);
            $td = new Element('td')->class("{$tdCls} text-center text-gray-500 py-8")
                ->attr('colspan', (string)count($this->headers))
                ->text($this->emptyText);
            $tr->child($td);
            $tbody->child($tr);
        } else {
            foreach ($this->rows as $row) {
                $tr = new Element('tr')->class($trCls);
                foreach ($row as $cell) {
                    $td = new Element('td')->class($tdCls);
                    $td->child($cell);
                    $tr->child($td);
                }
                $tbody->child($tr);
            }
        }
        $this->child($tbody);

        $wrapper = new Element('div');
        $wrapper->class('overflow-x-auto');
        $wrapper->child($this);

        return $wrapper->render();
    }
}
