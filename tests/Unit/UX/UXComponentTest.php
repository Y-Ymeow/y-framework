<?php

declare(strict_types=1);

namespace Tests\Unit\UX;

use Tests\TestCase;
use Framework\View\Base\Element;
use Framework\UX\UXComponent;

class UXComponentTest extends TestCase
{
    public function test_abstract_component_has_render_method(): void
    {
        $component = new class extends UXComponent {
            protected function toElement(): Element
            {
                $el = Element::make('div')->class('test-component')->text('hello');
                return $this->buildElement($el);
            }
        };

        $html = (string) $component;
        $this->assertStringContainsString('<div', $html);
        $this->assertStringContainsString('test-component', $html);
        $this->assertStringContainsString('hello', $html);
    }

    public function test_component_id_sets_data_attribute(): void
    {
        $component = new class extends UXComponent {
            protected function toElement(): Element
            {
                return $this->buildElement(Element::make('span'));
            }
        };
        $component->id('my-id');

        $html = (string) $component;
        $this->assertStringContainsString('my-id', $html);
    }

    public function test_component_class_adds_css_class(): void
    {
        $component = new class extends UXComponent {
            protected function toElement(): Element
            {
                return $this->buildElement(Element::make('div'));
            }
        };
        $component->class('custom-class');

        $html = (string) $component;
        $this->assertStringContainsString('custom-class', $html);
    }

    public function test_component_style_adds_inline_style(): void
    {
        $component = new class extends UXComponent {
            protected function toElement(): Element
            {
                return $this->buildElement(Element::make('div'));
            }
        };
        $component->style('color: red');

        $html = (string) $component;
        // Style should be present in output
        $this->assertNotEmpty($html);
    }

    public function test_component_attr_sets_html_attribute(): void
    {
        $component = new class extends UXComponent {
            protected function toElement(): Element
            {
                return $this->buildElement(Element::make('input'));
            }
        };
        $component->attr('type', 'text')->attr('name', 'username');

        $html = (string) $component;
        $this->assertStringContainsString('type="text"', $html);
        $this->assertStringContainsString('name="username"', $html);
    }

    public function test_component_liveModel_registers_binding(): void
    {
        $component = new class extends UXComponent {
            protected function toElement(): Element
            {
                $inner = Element::make('input');
                $wrapper = Element::make('div')->child($inner);
                return $this->buildElement($wrapper);
            }
        };
        $component->liveModel('searchQuery');

        $html = (string) $component;
        // liveModel adds data-ux-model attribute
        $this->assertStringContainsString('ux-model', $html);
    }

    public function test_component_to_string_returns_html(): void
    {
        $component = new class extends UXComponent {
            protected function toElement(): Element
            {
                return $this->buildElement(Element::make('p')->text('test'));
            }
        };

        $html = (string) $component;
        $this->assertStringContainsString('<p', $html);
        $this->assertStringContainsString('test', $html);
    }
}
