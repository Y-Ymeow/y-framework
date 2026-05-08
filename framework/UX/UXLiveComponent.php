<?php

declare(strict_types=1);

namespace Framework\UX;

use Framework\Component\Live\EmbeddedLiveComponent;
use Framework\View\Base\Element;
use Framework\View\Document\AssetRegistry;

/**
 * Base class for UX components that are also Live components.
 *
 * Combines the UX component lifecycle (boot-then-render) with
 * Live component state management, parent injection, and #[LiveAction]
 * support. Subclasses implement toElement() to define their DOM structure
 * and optionally override mount(), hydrate(), dehydrate(), onUpdate(), etc.
 *
 * A UXLiveComponent renders as a plain HTML *fragment* (no data-live wrapper),
 * making it suitable for use as an embedded child inside a parent LiveComponent
 * or as a widget that participates in reactive patches.
 *
 * To get the full Live wrapper (data-live, data-live-state), override
 * __toString() to call parent::toHtml().
 */
abstract class UXLiveComponent extends EmbeddedLiveComponent
{
    /**
     * UX components are typically used as child elements.
     */
    protected bool $isUxComponent = true;

    /**
     * Whether this UX component should auto-refresh when
     * its parent component's state changes.
     */
    protected bool $autoRefreshOnParentUpdate = false;

    /**
     * Called when the parent's state has been updated.
     * Override in subclasses to react to parent changes.
     */
    public function onParentUpdate(): void {}

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

    public function init(): void
    {
        parent::init();
    }

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

    public function __toString(): string
    {
        return $this->render()->render();
    }
}
