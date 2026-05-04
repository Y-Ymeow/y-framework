<?php

declare(strict_types=1);

namespace Tests\Unit\UX\Form;

use Tests\TestCase;
use Framework\UX\Form\TreeSelect;

class TreeSelectTest extends TestCase
{
    private function sampleData(): array
    {
        return [
            ['label' => '技术部', 'children' => [
                ['label' => '前端组', 'value' => 'frontend'],
                ['label' => '后端组', 'value' => 'backend'],
            ]],
            ['label' => '产品部', 'children' => [
                ['label' => '产品设计', 'value' => 'design'],
            ]],
        ];
    }

    public function test_basic_structure(): void
    {
        $select = TreeSelect::make()->treeData($this->sampleData());

        $html = (string) $select;
        $this->assertStringContainsString('ux-tree-select', $html);
        $this->assertStringContainsString('ux-tree-select-selector', $html);
        $this->assertStringContainsString('ux-tree-select-dropdown', $html);
        $this->assertStringContainsString('ux-tree-select-tree', $html);
    }

    public function test_falls_back_to_label_as_value_when_value_missing(): void
    {
        $select = TreeSelect::make()->treeData([
            ['label' => '技术部', 'children' => [
                ['label' => '前端组'],
            ]],
        ]);

        $html = (string) $select;
        $this->assertStringContainsString('data-node-value="技术部"', $html);
        $this->assertStringContainsString('data-node-value="前端组"', $html);
    }

    public function test_explicit_value_is_used(): void
    {
        $select = TreeSelect::make()->treeData([
            ['label' => '部门', 'value' => 'dept-001'],
        ]);

        $html = (string) $select;
        $this->assertStringContainsString('data-node-value="dept-001"', $html);
    }

    public function test_multiple_mode(): void
    {
        $select = TreeSelect::make()
            ->treeData([['label' => 'A']])
            ->multiple();

        $this->assertStringContainsString('ux-tree-select-multiple', (string) $select);
    }

    public function test_search_mode_renders_input(): void
    {
        $select = TreeSelect::make()
            ->treeData([['label' => 'A', 'value' => 'a']])
            ->showSearch();

        $html = (string) $select;
        $this->assertStringContainsString('ux-tree-select-search', $html);
    }

    public function test_search_mode_with_value_shows_input_value(): void
    {
        $select = TreeSelect::make()
            ->treeData([['label' => '技术部', 'value' => 'tech']])
            ->showSearch()
            ->value('tech');

        $html = (string) $select;
        $this->assertStringContainsString('value="技术部"', $html);
    }

    public function test_placeholder_attribute(): void
    {
        $select = TreeSelect::make()
            ->treeData([])
            ->placeholder('选择部门');

        $html = (string) $select;
        $this->assertStringContainsString('选择部门', $html);
    }

    public function test_disabled_state(): void
    {
        $select = TreeSelect::make()
            ->treeData([['label' => 'A']])
            ->disabled();

        $this->assertStringContainsString('ux-tree-select-disabled', (string) $select);
    }

    public function test_tree_structure_renders_all_nodes(): void
    {
        $data = [
            ['label' => 'Root1', 'children' => [['label' => 'Child1'], ['label' => 'Child2']]],
            ['label' => 'Root2'],
        ];
        $select = TreeSelect::make()->treeData($data);

        $html = (string) $select;
        $this->assertStringContainsString('Root1', $html);
        $this->assertStringContainsString('Child1', $html);
        $this->assertStringContainsString('Child2', $html);
        $this->assertStringContainsString('Root2', $html);
    }

    public function test_leaf_nodes_have_leaf_class(): void
    {
        $select = TreeSelect::make()->treeData([
            ['label' => 'Parent', 'children' => [
                ['label' => 'Leaf'],
            ]],
        ]);

        $html = (string) $select;
        $this->assertStringContainsString('leaf', $html);
    }
}
