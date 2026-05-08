<?php

declare(strict_types=1);

namespace Framework\Component\Live\Concerns;

use Framework\Component\Live\LiveComponent;

/**
 * Provides parent component injection and dispatch-to-parent capability.
 *
 * Embed this trait in any class that needs to know its parent LiveComponent
 * (e.g. UXLiveComponent, EmbeddedLiveComponent). The parent is set by
 * LiveRequestHandler during request processing when a _parent_id is present.
 */
trait HasParentInjection
{
    protected ?LiveComponent $parent = null;

    public function setParent(LiveComponent $parent): void
    {
        $this->parent = $parent;
    }

    public function getParent(): ?LiveComponent
    {
        return $this->parent;
    }

    public function hasParent(): bool
    {
        return $this->parent !== null;
    }

    public function dispatchToParent(string $action, array $params = []): mixed
    {
        if ($this->parent === null) {
            throw new \RuntimeException('Cannot dispatch to parent: no parent component set on ' . static::class);
        }

        return $this->parent->callAction($action, $params);
    }
}