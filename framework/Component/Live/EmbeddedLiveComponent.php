<?php

declare(strict_types=1);

namespace Framework\Component\Live;

/**
 * Base class for Live components that can be embedded (nested)
 * inside other Live components.
 *
 * Provides automatic parent injection so child components can
 * interact with their parent's action methods and state.
 */
abstract class EmbeddedLiveComponent extends LiveComponent
{
    /**
     * The parent Live component, set automatically by the framework
     * when the child is nested inside another Live component.
     */
    protected ?LiveComponent $parent = null;

    /**
     * Set the parent component after hydration.
     *
     * Called by LiveRequestHandler during request processing
     * when a _parent_id is present in the request payload.
     */
    public function setParent(LiveComponent $parent): void
    {
        $this->parent = $parent;
    }

    /**
     * Get the parent component, if any.
     */
    public function getParent(): ?LiveComponent
    {
        return $this->parent;
    }

    /**
     * Check whether this component has a parent.
     */
    public function hasParent(): bool
    {
        return $this->parent !== null;
    }

    /**
     * Dispatch an action on the parent component.
     *
     * Convenience method so child components can trigger
     * parent-side logic without manual wiring.
     */
    public function dispatchToParent(string $action, array $params = []): mixed
    {
        if ($this->parent === null) {
            throw new \RuntimeException('Cannot dispatch to parent: no parent component set on ' . static::class);
        }

        return $this->parent->callAction($action, $params);
    }
}
