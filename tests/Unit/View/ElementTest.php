<?php

declare(strict_types=1);

namespace Tests\Unit\View;

use Tests\TestCase;
use Tests\InteractsWithElements;
use Framework\View\Base\Element;

class ElementTest extends TestCase
{
    use InteractsWithElements;

    public function test_make_creates_element(): void
    {
        $el = Element::make('div');

        $this->assertStringContainsString('<div', $this->elementHtml($el));
        $this->assertStringContainsString('</div>', $this->elementHtml($el));
    }

    public function test_id_sets_attribute(): void
    {
        $el = Element::make('div')->id('my-container');

        $this->assertElementHasAttribute($el, 'id', 'my-container');
    }

    public function test_class_adds_css_classes(): void
    {
        $el = Element::make('div')->class('foo', 'bar', 'baz');

        $this->assertElementHasClass($el, 'foo');
        $this->assertElementHasClass($el, 'bar');
        $this->assertElementHasClass($el, 'baz');
    }

    public function test_style_sets_inline_style(): void
    {
        $el = Element::make('div')->style('color: red; font-size: 14px');

        $html = $this->elementHtml($el);
        $this->assertStringContainsString('style="', $html);
        $this->assertStringContainsString('color: red', $html);
    }

    public function test_attr_sets_html_attributes(): void
    {
        $el = Element::make('input')->attr('type', 'text')->attr('name', 'email');

        $html = $this->elementHtml($el);
        $this->assertStringContainsString('type="text"', $html);
        $this->assertStringContainsString('name="email"', $html);
    }

    public function test_data_sets_data_attributes(): void
    {
        $el = Element::make('div')->data('test-id', '123')->data('role', 'main');

        $this->assertElementDataAttributes($el, ['test-id' => '123', 'role' => 'main']);
    }

    public function test_text_escapes_content(): void
    {
        $el = Element::make('p')->text('<script>alert("xss")</script>');

        $html = $this->elementHtml($el);
        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
    }

    public function test_text_contains_raw_text(): void
    {
        $el = Element::make('span')->text('Hello World');

        $this->assertElementTextContains($el, 'Hello World');
    }

    public function test_child_appends_single_child(): void
    {
        $el = Element::make('ul')->child(Element::make('li')->text('Item 1'));

        $html = $this->elementHtml($el);
        $this->assertStringContainsString('<li', $html);
        $this->assertStringContainsString('Item 1', $html);
    }

    public function test_children_appends_multiple_children(): void
    {
        $el = Element::make('ul')->children(
            Element::make('li')->text('A'),
            Element::make('li')->text('B'),
            Element::make('li')->text('C'),
        );

        $html = $this->elementHtml($el);
        $this->assertEquals(3, substr_count($html, '<li'));
    }

    public function test_nested_elements(): void
    {
        $el = Element::make('div')
            ->id('outer')
            ->child(
                Element::make('div')
                    ->id('inner')
                    ->child(Element::make('span')->text('deep'))
            );

        $html = $this->elementHtml($el);
        $this->assertStringContainsString('id="outer"', $html);
        $this->assertStringContainsString('id="inner"', $html);
        $this->assertStringContainsString('deep', $html);
    }

    public function test_liveModel_adds_data_model(): void
    {
        $el = Element::make('input')->liveModel('username');

        $html = $this->elementHtml($el);
        $this->assertStringContainsString('data-model', $html);
        $this->assertStringContainsString('username', $html);
    }

    public function test_liveAction_adds_data_action(): void
    {
        $el = Element::make('button')->liveAction('saveForm');

        $html = $this->elementHtml($el);
        $this->assertStringContainsString('data-action="saveForm"', $html);
    }

    public function test_to_string_conversion(): void
    {
        $el = Element::make('h1')->text('Title');
        $html = (string) $el;

        $this->assertStringContainsString('<h1', $html);
        $this->assertStringContainsString('Title', $html);
    }
}
