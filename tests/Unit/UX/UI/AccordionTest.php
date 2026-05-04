<?php

declare(strict_types=1);

namespace Tests\Unit\UX\UI;

use Tests\TestCase;
use Framework\UX\UI\Accordion;

class AccordionTest extends TestCase
{
    public function test_basic_structure(): void
    {
        $accordion = Accordion::make()
            ->item('Section 1', 'Content of section 1')
            ->item('Section 2', 'Content of section 2');

        $html = (string) $accordion;
        $this->assertStringContainsString('ux-accordion', $html);
        $this->assertStringContainsString('Section 1', $html);
        $this->assertStringContainsString('Content of section 1', $html);
    }

    public function test_item_renders_header_and_body(): void
    {
        $accordion = Accordion::make()
            ->item('Title', 'Body text');

        $html = (string) $accordion;
        $this->assertStringContainsString('ux-accordion-header', $html);
        $this->assertStringContainsString('ux-accordion-body', $html);
    }

    public function test_dark_mode(): void
    {
        $accordion = Accordion::make()->dark()->item('A', 'B');

        $this->assertStringContainsString('ux-accordion-dark', (string) $accordion);
    }

    public function test_multiple_mode(): void
    {
        $accordion = Accordion::make()->multiple()->item('A', 'B')->item('C', 'D');

        $html = (string) $accordion;
        // multiple() sets data attribute
        $this->assertStringContainsString('data-accordion-multiple', $html);
    }

    public function test_variant_method(): void
    {
        $accordion = Accordion::make()->variant('card')->item('A', 'B');

        $this->assertStringContainsString('ux-accordion-card', (string) $accordion);
    }

    public function test_single_item_renders(): void
    {
        $accordion = Accordion::make()->item('Only Section', 'Only content');

        $html = (string) $accordion;
        $this->assertStringContainsString('Only Section', $html);
        $this->assertStringContainsString('Only content', $html);
    }

    public function test_multiple_items_render_all(): void
    {
        $accordion = Accordion::make()
            ->item('One', '1')
            ->item('Two', '2')
            ->item('Three', '3');

        $html = (string) $accordion;
        $this->assertStringContainsString('One', $html);
        $this->assertStringContainsString('Two', $html);
        $this->assertStringContainsString('Three', $html);
    }
}
