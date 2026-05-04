<?php

declare(strict_types=1);

namespace Tests\Unit\View;

use Tests\TestCase;
use Framework\View\Document\AssetRegistry;

class AssetRegistryTest extends TestCase
{
    private AssetRegistry $registry;

    protected function setUp(): void
    {
        parent::setUp();
        $this->registry = AssetRegistry::getInstance();
    }

    public function test_register_script_stores_code(): void
    {
        $this->registry->registerScript('test:my-script', 'console.log("hello");');

        // registerScript stores code in namedScripts, renderJs outputs loader script
        $output = $this->registry->renderJs();
        // Should contain the script ID in the loader URL (URL-encoded)
        $this->assertStringContainsString('test%3Amy-script', $output);
    }

    public function test_require_script_marks_as_needed(): void
    {
        $this->registry->requireScript('test:a');
        $this->registry->requireScript('test:b');

        // requireScript marks scripts as requested - no exception = success
        $output = $this->registry->renderJs();
        // IDs are URL-encoded in the loader URL
        $this->assertStringContainsString('test%3Aa', $output);
        $this->assertStringContainsString('test%3Ab', $output);
    }

    public function test_inline_style_stores_css(): void
    {
        $this->registry->inlineStyle('.test { color: red; }');

        $output = $this->registry->renderCss();
        $this->assertStringContainsString('.test { color: red; }', $output);
        $this->assertStringContainsString('<style>', $output);
    }

    public function test_ui_method_registers_ui_js_and_css(): void
    {
        $before = $this->registry->renderJs();

        $this->registry->ui();

        $after = $this->registry->renderJs();
        // ui() should add ui.js script reference
        $this->assertGreaterThan(
            strlen($before),
            strlen($after),
            'ui() should add JS references'
        );
    }

    public function test_ux_method_registers_ux_js(): void
    {
        $before = $this->registry->renderJs();

        $this->registry->ux();

        $after = $this->registry->renderJs();
        // ux() should add ux.js script reference
        $this->assertGreaterThan(
            strlen($before),
            strlen($after),
            'ux() should add JS references'
        );
    }

    public function test_reset_clears_all_state(): void
    {
        $this->registry->registerScript('test:x', 'var x = 1;');
        $this->registry->inlineStyle('.x { }');
        $this->registry->requireScript('test:y');

        AssetRegistry::reset();

        $fresh = AssetRegistry::getInstance();
        $scripts = $fresh->renderJs();
        $styles = $fresh->renderCss();

        $this->assertStringNotContainsString('test%3Ax', $scripts);
        $this->assertStringNotContainsString('.x { }', $styles);
    }

    public function test_render_js_includes_registered_script_ids(): void
    {
        $this->registry->registerScript('test:comp-a', '// component A js');
        $this->registry->registerScript('test:comp-b', '// component B js');
        $this->registry->requireScript('test:comp-a');
        $this->registry->requireScript('test:comp-b');

        $output = $this->registry->renderJs();
        // Script IDs should appear in the loader URL (URL-encoded)
        $this->assertStringContainsString('test%3Acomp-a', $output);
        $this->assertStringContainsString('test%3Acomp-b', $output);
    }
}
