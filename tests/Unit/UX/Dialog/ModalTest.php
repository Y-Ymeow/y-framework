<?php

declare(strict_types=1);

namespace Tests\Unit\UX\Dialog;

use Tests\TestCase;
use Framework\View\Base\Element;
use Framework\UX\Dialog\Modal;

class ModalTest extends TestCase
{
    public function test_basic_modal_structure(): void
    {
        $modal = Modal::make()->title('Test Title')->content('Modal body content');

        $html = (string) $modal;
        $this->assertStringContainsString('ux-modal', $html);
        $this->assertStringContainsString('Test Title', $html);
        $this->assertStringContainsString('Modal body content', $html);
    }

    public function test_modal_size_small(): void
    {
        $modal = Modal::make()->title('T')->content('C')->sm();

        $this->assertStringContainsString('ux-modal-sm', (string) $modal);
    }

    public function test_modal_size_large(): void
    {
        $modal = Modal::make()->title('T')->content('C')->lg();

        $this->assertStringContainsString('ux-modal-lg', (string) $modal);
    }

    public function test_modal_centered(): void
    {
        $modal = Modal::make()->title('T')->content('C')->centered();

        $this->assertStringContainsString('ux-modal-centered', (string) $modal);
    }

    public function test_modal_has_header_and_body_sections(): void
    {
        $modal = Modal::make()->title('Header')->content('Body');

        $html = (string) $modal;
        $this->assertStringContainsString('ux-modal-header', $html);
        $this->assertStringContainsString('ux-modal-body', $html);
    }

    public function test_modal_footer(): void
    {
        $modal = Modal::make()
            ->title('Confirm')
            ->content('Are you sure?')
            ->footer(
                Element::make('div')->class('ux-modal-footer')->text('Footer buttons here')
            );

        $html = (string) $modal;
        $this->assertStringContainsString('ux-modal-footer', $html);
    }

    public function test_modal_open_state(): void
    {
        $modal = Modal::make()->open();

        $this->assertStringContainsString('ux-modal-open', (string) $modal);
    }

    public function test_modal_closeable(): void
    {
        $modal = Modal::make()->closeable(true);

        $html = (string) $modal;
        $this->assertStringContainsString('ux-modal-close', $html);
    }
}
