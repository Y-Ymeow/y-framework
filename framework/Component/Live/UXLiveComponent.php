<?php

declare(strict_types=1);

namespace Framework\Component\Live;

/**
 * Base class for UX-focused Live components.
 *
 * UXLiveComponents are embedded child components that provide
 * reusable UI patterns (modals, toasts, dropdowns, etc.) with
 * full Live capabilities — they can have their own #[LiveAction]
 * methods, state, and event listeners while also being able to
 * interact with their parent Live component via dispatchToParent().
 *
 * Inherits parent injection from EmbeddedLiveComponent.
 */
abstract class UXLiveComponent extends EmbeddedLiveComponent
{
    /**
     * UX components are typically used as child elements.
     * Override in subclasses to customize behavior.
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
    public function onParentUpdate(): void
    {
        // Default: no-op. Subclasses can override to react.
    }
}
