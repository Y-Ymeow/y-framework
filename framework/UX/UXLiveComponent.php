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

    public function __construct()
    {
        parent::__construct();

        AssetRegistry::getInstance()->ui();
        AssetRegistry::getInstance()->ux();

        $this->boot();
    }

    /**
     * UX component initialization — register JS/CSS, set up defaults.
     * Called automatically from the constructor.
     * Subclasses override this instead of the constructor.
     */
    protected function boot(): void {}

    /**
     * Called when the parent's state has been updated.
     * Override in subclasses to react to parent changes.
     */
    public function onParentUpdate(): void {}

    /**
     * Render the component as an HTML Element.
     *
     * Subclasses MUST implement toElement(). If a subclass defines
     * its own render() method, it takes precedence over this default
     * which delegates to toElement().
     */
    public function render(): Element
    {
        return $this->toElement();
    }

    /**
     * Define the component's DOM structure.
     * Subclasses override this OR render() directly.
     */
    protected function toElement(): Element
    {
        return Element::make('div');
    }
}
