<?php

declare(strict_types=1);

namespace Tests\Unit\UX\Overlay;

use Tests\TestCase;
use Framework\UX\Overlay\Popover;

class PopoverTest extends TestCase
{
    public function test_basic_structure(): void
    {
        $popover = Popover::make('Title', 'Content');

        $this->assertStringContainsString('ux-popover-wrapper', (string) $popover);
    }

    public function test_placement_top(): void
    {
        $popover = Popover::make('T', 'C')->placement('top');

        $html = (string) $popover;
        $this->assertStringContainsString('data-popover-placement="top"', $html);
    }

    public function test_placement_bottom(): void
    {
        $popover = Popover::make('T', 'C')->placement('bottom');

        $html = (string) $popover;
        $this->assertStringContainsString('data-popover-placement="bottom"', $html);
    }

    public function test_placement_left(): void
    {
        $popover = Popover::make('T', 'C')->placement('left');

        $html = (string) $popover;
        $this->assertStringContainsString('data-popover-placement="left"', $html);
    }

    public function test_placement_right(): void
    {
        $popover = Popover::make('T', 'C')->placement('right');

        $html = (string) $popover;
        $this->assertStringContainsString('data-popover-placement="right"', $html);
    }

    public function test_trigger_hover(): void
    {
        $popover = Popover::make('T', 'C')->trigger('hover');

        $html = (string) $popover;
        $this->assertStringContainsString('data-popover-trigger="hover"', $html);
    }

    public function test_trigger_click(): void
    {
        $popover = Popover::make('T', 'C')->trigger('click');

        $html = (string) $popover;
        $this->assertStringContainsString('data-popover-trigger="click"', $html);
    }

    public function test_title_and_content_as_data_attributes(): void
    {
        $popover = Popover::make('My Title', 'Body content here');

        $html = (string) $popover;
        $this->assertStringContainsString('data-popover-title', $html);
        $this->assertStringContainsString('data-popover-content', $html);
    }
}
