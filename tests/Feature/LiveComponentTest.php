<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;
use Tests\InteractsWithLiveComponents;
use App\Components\Counter;

class LiveComponentTest extends TestCase
{
    use InteractsWithLiveComponents;

    public function test_live_component_action_updates_state()
    {
        $response = $this->liveCall(Counter::class, 'increment', [
            'count' => 0
        ]);

        $this->assertTrue($response['success'], 'Live action should be successful');
        $this->assertEquals(1, $response['patches']['count'], 'Count should be incremented to 1');
    }

    public function test_live_component_with_initial_state()
    {
        $response = $this->liveCall(Counter::class, 'increment', [
            'count' => 5
        ]);

        $this->assertEquals(6, $response['patches']['count'], 'Count should be incremented to 6');
    }
}
