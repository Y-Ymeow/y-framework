<?php

declare(strict_types=1);

namespace Framework\Theme;

use Framework\View\Base\Element;

interface ThemeInterface
{
    public function name(): string;

    public function path(): string;

    public function boot(): void;

    public function getConfig(?string $key = null, mixed $default = null): mixed;

    public function getSetting(string $key, mixed $default = null): mixed;

    public function getSettings(): array;

    public function getStyles(): array;

    public function getScripts(): array;

    public function getNavLocations(): array;

    public function getWidgetAreas(): array;

    public function renderHeader(): Element;

    public function renderFooter(): Element;

    public function asset(string $path): string;

    public function renderCssVariables(): string;
}