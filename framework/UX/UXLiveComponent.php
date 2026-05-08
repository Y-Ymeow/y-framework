<?php

declare(strict_types=1);

namespace Framework\UX;

use Framework\Component\Live\Concerns\HasParentInjection;
use Framework\View\Base\Element;
use Framework\View\Document\AssetRegistry;

/**
 * Base class for UX components that participate in the Live parent-child
 * hierarchy without carrying full LiveComponent state management.
 *
 * UXLiveComponent provides:
 *  - Parent injection via HasParentInjection trait (setParent, dispatchToParent)
 *  - UX lifecycle (boot-then-render with AssetRegistry registration)
 *  - render() → toElement() bridge for HTML fragment output
 *
 * Unlike EmbeddedLiveComponent (which extends LiveComponent), this class
 * does NOT carry checksum, #[Locked], serializeState, mount/hydrate/dehydrate,
 * or the full Live action lifecycle. It is intentionally lightweight —
 * suitable for form fields, UI widgets, and other components that need
 * parent awareness but not independent state management.
 *
 * Subclasses implement toElement() to define their DOM structure, or
 * override render() directly for full control.
 */
abstract class UXLiveComponent
{
    use HasParentInjection;

    protected bool $isUxComponent = true;
    protected bool $autoRefreshOnParentUpdate = false;

    public function __construct()
    {
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

    /**
     * Render as an HTML string (fragment, no Live wrapper).
     */
    public function __toString(): string
    {
        return $this->render()->render();
    }
}
