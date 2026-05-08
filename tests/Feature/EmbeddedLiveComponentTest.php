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
        $this->assertStringContainsString('data-live-model.live', $html);
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

    public function test_live_text_input_emit_events(): void
    {
        $input = LiveTextInput::make('title');
        $input->_invoke();

        $input->inputValue = 'Hello World';
        $input->onUpdate();

        $events = $input->getEmittedEvents();
        $this->assertCount(1, $events);
        $this->assertEquals('fieldChanged', $events[0]['event']);
        $this->assertEquals('title', $events[0]['params']['field']);
        $this->assertEquals('Hello World', $events[0]['params']['value']);
    }

    public function test_live_text_input_emit_empty_value_is_ignored(): void
    {
        $input = LiveTextInput::make('title');
        $input->_invoke();

        $input->inputValue = '';
        $input->onUpdate();

        $events = $input->getEmittedEvents();
        $this->assertCount(0, $events);
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

        $this->assertStringContainsString('data-live-model.live', $html);
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

    public function test_state_update_endpoint_returns_events(): void
    {
        $input = LiveTextInput::make('title');
        $input->_invoke();

        $input->inputValue = 'Test Value';
        $serializedState = $input->serializeState();

        $request = \Framework\Http\Request\Request::create('/live/state', 'POST', [
            '_component' => LiveTextInput::class,
            '_state' => $serializedState,
            '_data' => ['inputValue' => 'Test Value'],
            '_token' => 'test-token',
        ]);

        $session = $this->app->make(\Framework\Http\Session\Session::class);
        $session->set('_token', 'test-token');

        $resolver = $this->app->make(\Framework\Component\Live\LiveComponentResolver::class);
        $response = $resolver->handle($request);

        $decoded = json_decode($response->getContent(), true);

        $this->assertTrue($decoded['success']);
        $this->assertArrayHasKey('events', $decoded);
        $this->assertCount(1, $decoded['events']);
        $this->assertEquals('fieldChanged', $decoded['events'][0]['event']);
        $this->assertEquals('title', $decoded['events'][0]['params']['field']);
    }
}
