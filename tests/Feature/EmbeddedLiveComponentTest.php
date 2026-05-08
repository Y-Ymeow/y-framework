<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;
use Tests\InteractsWithLiveComponents;
use App\Components\LiveFormDemo;
use Framework\UX\Form\Components\LiveTextInput;
use Framework\Component\Live\AbstractLiveComponent;
use Framework\Component\Live\EmbeddedLiveComponent;
use Framework\Component\Live\LiveComponent;

class EmbeddedLiveComponentTest extends TestCase
{
    use InteractsWithLiveComponents;

    protected function setUp(): void
    {
        parent::setUp();
        ob_start();
    }

    protected function tearDown(): void
    {
        ob_end_clean();
        parent::tearDown();
    }

    public function test_live_text_input_renders_with_live_attributes(): void
    {
        $input = LiveTextInput::make('title');
        $input->label('标题');

        $html = $input->toHtml();

        $this->assertStringContainsString('data-live=', $html);
        $this->assertStringContainsString('data-live-id=', $html);
        $this->assertStringContainsString('data-live-state=', $html);
        $this->assertStringContainsString('data-live-model', $html);
    }

    public function test_live_text_input_inherits_embedded_live_component(): void
    {
        $input = LiveTextInput::make('test');

        $this->assertInstanceOf(EmbeddedLiveComponent::class, $input);
        $this->assertInstanceOf(AbstractLiveComponent::class, $input);
    }

    public function test_live_text_input_can_set_parent(): void
    {
        $parent = new LiveFormDemo();
        $input = LiveTextInput::make('title');
        $input->setParent($parent);

        $this->assertTrue($input->hasParent());
        $this->assertSame($parent, $input->getParent());

        $html = $input->toHtml();
        $this->assertStringContainsString('data-live-parent-id=', $html);
    }

    public function test_live_text_input_without_parent_has_no_parent_attr(): void
    {
        $input = LiveTextInput::make('title');

        $this->assertFalse($input->hasParent());

        $html = $input->toHtml();
        $this->assertStringNotContainsString('data-live-parent-id=', $html);
    }

    public function test_live_text_input_dispatches_to_parent(): void
    {
        $parent = new LiveFormDemo();
        $input = LiveTextInput::make('title');
        $input->setParent($parent);

        $input->inputValue = 'Hello World';
        $input->updateValue(['value' => 'Hello World']);

        $this->assertEquals('Hello World', $input->inputValue);
        $this->assertEquals('title', $parent->lastChangedField);
        $this->assertEquals('Hello World', $parent->lastChangedValue);
        $this->assertEquals(1, $parent->changeCount);
    }

    public function test_live_text_input_dispatch_generates_slug(): void
    {
        $parent = new LiveFormDemo();
        $input = LiveTextInput::make('title');
        $input->setParent($parent);

        $input->updateValue(['value' => 'Hello World PHP']);

        $this->assertEquals('Hello World PHP', $parent->title);
        $this->assertEquals('hello-world-php', $parent->slug);
    }

    public function test_parent_live_component_renders_with_children(): void
    {
        $demo = new LiveFormDemo();
        $html = $demo->toHtml();

        $this->assertStringContainsString('data-live=', $html);
        $this->assertStringContainsString('LiveFormDemo', $html);
        $this->assertStringContainsString('Live Form Demo', $html);
    }

    public function test_parent_renders_embedded_children_with_live_wrapper(): void
    {
        $demo = new LiveFormDemo();
        $html = $demo->toHtml();

        $this->assertStringContainsString('data-live="App\Components\LiveFormDemo"', $html);

        $this->assertMatchesRegularExpression('/data-live="[^"]*LiveTextInput[^"]*"/', $html);

        $this->assertStringContainsString('data-live-parent-id=', $html);

        $this->assertStringContainsString('data-live-model', $html);
    }

    public function test_parent_state_updates_via_live_action(): void
    {
        $response = $this->liveCall(LiveFormDemo::class, 'onFieldChange', [
            'title' => '',
            'slug' => '',
            'changeCount' => 0,
            'lastChangedField' => '',
            'lastChangedValue' => '',
        ], [
            'field' => 'title',
            'value' => 'Test Title',
        ]);

        $this->assertTrue($response['success']);
        $this->assertEquals('Test Title', $response['patches']['title'] ?? null);
        $this->assertEquals('test-title', $response['patches']['slug'] ?? null);
        $this->assertEquals(1, $response['patches']['changeCount'] ?? null);
        $this->assertEquals('title', $response['patches']['lastChangedField'] ?? null);
    }

    public function test_parent_reset_action(): void
    {
        $response = $this->liveCall(LiveFormDemo::class, 'resetForm', [
            'title' => 'Some Title',
            'slug' => 'some-title',
            'changeCount' => 5,
            'lastChangedField' => 'title',
            'lastChangedValue' => 'Some Title',
        ]);

        $this->assertTrue($response['success']);
        $this->assertEquals('', $response['patches']['title'] ?? null);
        $this->assertEquals('', $response['patches']['slug'] ?? null);
        $this->assertEquals(0, $response['patches']['changeCount'] ?? null);
    }

    public function test_embedded_component_to_html_includes_parent_id(): void
    {
        $parent = new LiveFormDemo();
        $input = LiveTextInput::make('email');
        $input->setParent($parent);

        $html = $input->toHtml();

        $this->assertStringContainsString('data-live-parent-id="' . $parent->getComponentId() . '"', $html);
    }

    public function test_live_text_input_update_value_action(): void
    {
        $input = LiveTextInput::make('title');
        $input->_invoke();

        $input->updateValue(['value' => 'New Value']);

        $this->assertEquals('New Value', $input->inputValue);
    }
}
