<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;
use Tests\InteractsWithLiveComponents;
use App\Components\LiveFormDemo;
use App\Actions\SlugGenerator;
use App\Actions\UnsafeAction;
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

    public function test_live_text_input_renders_with_default_model(): void
    {
        $input = LiveTextInput::make('title');
        $input->label('标题');

        $html = $input->toHtml();

        // 默认使用 data-model（本地状态），不是 data-live-model
        $this->assertStringContainsString('data-live=', $html);
        $this->assertStringContainsString('data-live-id=', $html);
        $this->assertStringContainsString('data-live-state=', $html);
        $this->assertStringContainsString('data-model="inputValue"', $html);
        $this->assertStringNotContainsString('data-live-model', $html);
    }

    public function test_live_text_input_renders_with_live_model_when_enabled(): void
    {
        $input = LiveTextInput::make('title')->live();
        $input->label('标题');

        $html = $input->toHtml();

        // 调用 live() 后才使用 data-live-model.live
        $this->assertStringContainsString('data-live-model.live', $html);
        $this->assertStringNotContainsString('data-model="inputValue"', $html);
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

    public function test_live_event_endpoint_triggers_listener(): void
    {
        $demo = new LiveFormDemo();
        $demo->_invoke();
        $serializedState = $demo->serializeState();

        $request = \Framework\Http\Request\Request::create('/live/event', 'POST', [
            '_component' => LiveFormDemo::class,
            '_state' => $serializedState,
            '_data' => [
                'title' => '',
                'slug' => '',
                'changeCount' => 0,
                'lastChangedField' => '',
                'lastChangedValue' => '',
            ],
            '_event' => 'fieldChanged',
            '_params' => [
                'field' => 'title',
                'value' => 'Test Title',
            ],
            '_token' => 'test-token',
        ]);

        $session = $this->app->make(\Framework\Http\Session\Session::class);
        $session->set('_token', 'test-token');

        $resolver = $this->app->make(\Framework\Component\Live\LiveComponentResolver::class);
        $response = $resolver->handle($request);

        $decoded = json_decode($response->getContent(), true);

        echo "\nResponse: " . json_encode($decoded, JSON_PRETTY_PRINT) . "\n";

        $this->assertTrue($decoded['success']);
        $this->assertEquals('Test Title', $decoded['patches']['title'] ?? null);
        $this->assertEquals('test-title', $decoded['patches']['slug'] ?? null);
        $this->assertEquals(1, $decoded['patches']['changeCount'] ?? null);
        $this->assertEquals('title', $decoded['patches']['lastChangedField'] ?? null);
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

    public function test_live_text_input_renders_action_button(): void
    {
        $input = LiveTextInput::make('slug')
            ->action('generateSlug', [SlugGenerator::class, 'generate'], '生成');

        $html = $input->toHtml();

        $this->assertStringContainsString('data-action:click="generateSlug"', $html);
        $this->assertStringContainsString('生成', $html);
        $this->assertStringContainsString('ux-form-action-btn', $html);
    }

    public function test_external_class_action_is_registered(): void
    {
        $input = LiveTextInput::make('slug')
            ->action('generateSlug', [SlugGenerator::class, 'generate'], '生成');
        $input->_invoke();

        $actions = $input->getLiveActions();

        $this->assertArrayHasKey('generateSlug', $actions);
        $this->assertIsArray($actions['generateSlug']);
        $this->assertEquals(SlugGenerator::class, $actions['generateSlug'][0]);
        $this->assertEquals('generate', $actions['generateSlug'][1]);
    }

    public function test_external_class_action_executes_correctly(): void
    {
        $parent = new LiveFormDemo();
        $parent->title = 'Hello World Test';
        $parent->_invoke();

        $input = LiveTextInput::make('slug')
            ->action('generateSlug', [SlugGenerator::class, 'generate'], '生成');
        $input->_invoke();
        $input->setParent($parent);

        $input->callAction('generateSlug', []);

        $this->assertEquals('hello-world-test', $input->inputValue);

        $events = $input->getEmittedEvents();
        $this->assertCount(1, $events);
        $this->assertEquals('slugGenerated', $events[0]['event']);
        $this->assertEquals('hello-world-test', $events[0]['params']['slug']);
    }

    public function test_external_class_action_without_live_action_attribute_fails(): void
    {
        $input = LiveTextInput::make('slug')
            ->action('unsafe', [UnsafeAction::class, 'doSomething'], 'Unsafe');
        $input->_invoke();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('not marked with #[LiveAction]');

        $input->callAction('unsafe', []);
    }

    public function test_emit_with_target_id_sends_to_specific_component(): void
    {
        // 创建两个组件，一个发送定向事件，一个接收
        $sender = new LiveFormDemo();
        $sender->named('sender-demo');
        $sender->_invoke();

        $receiver = new LiveFormDemo();
        $receiver->named('receiver-demo');
        $receiver->_invoke();

        // 发送定向事件到 receiver
        $sender->emit('fieldChanged', ['field' => 'title', 'value' => 'Targeted'], 'receiver-demo');

        $events = $sender->getEmittedEvents();
        $this->assertCount(1, $events);
        $this->assertEquals('fieldChanged', $events[0]['event']);
        $this->assertEquals('receiver-demo', $events[0]['targetId']);
        $this->assertEquals('Targeted', $events[0]['params']['value']);
    }

    public function test_emit_without_target_id_is_broadcast(): void
    {
        $sender = new LiveFormDemo();
        $sender->named('sender-demo');
        $sender->_invoke();

        // 发送广播事件（无 targetId）
        $sender->emit('fieldChanged', ['field' => 'title', 'value' => 'Broadcast']);

        $events = $sender->getEmittedEvents();
        $this->assertCount(1, $events);
        $this->assertEquals('fieldChanged', $events[0]['event']);
        $this->assertNull($events[0]['targetId'] ?? null);
    }

    public function test_component_id_can_be_set_via_named(): void
    {
        $component = new LiveFormDemo();
        $component->named('my-custom-id');
        $component->_invoke();

        $this->assertEquals('my-custom-id', $component->getComponentId());
        $this->assertEquals('my-custom-id', $component->id());
    }

    public function test_component_auto_generates_id(): void
    {
        $component1 = new LiveFormDemo();
        $component1->_invoke();

        $component2 = new LiveFormDemo();
        $component2->_invoke();

        // 自动生成的 ID 应该是唯一的
        $this->assertNotEquals($component1->getComponentId(), $component2->getComponentId());
        $this->assertStringStartsWith('live-form-demo-', $component1->getComponentId());
    }
}
