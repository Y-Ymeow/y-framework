<?php

declare(strict_types=1);

namespace Framework\UX;

use Framework\Component\Live\EmbeddedLiveComponent;
use Framework\View\Base\Element;
use Framework\View\Document\AssetRegistry;

/**
 * Base class for UX components that participate in the Live parent-child
 * hierarchy. Inherits full state management from EmbeddedLiveComponent.
 */
abstract class UXLiveComponent extends EmbeddedLiveComponent
{
    protected bool $isUxComponent = true;
    protected bool $autoRefreshOnParentUpdate = false;

    protected ?string $uxModel = null;

    public function __construct()
    {
        parent::__construct();

        AssetRegistry::getInstance()->ui();
        AssetRegistry::getInstance()->ux();

        $this->boot();
    }

    public function liveModel(string $property): static
    {
        $this->uxModel = $property;
        return $this;
    }

    protected function boot(): void {}

    public function onParentUpdate(): void {}

    public function render(): Element
    {
        $el = $this->toElement();

        if ($this->uxModel) {
            $el->data('ux-model', $this->uxModel);
        }

        return $el;
    }

    protected function toElement(): Element
    {
        return Element::make('div');
    }
}
