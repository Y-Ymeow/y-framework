<?php

declare(strict_types=1);

namespace Framework\Component\Live;

use Framework\Component\Live\Concerns\HasParentInjection;

/**
 * Base class for Live components that can be embedded (nested)
 * inside other Live components.
 *
 * Provides automatic parent injection and specialized rendering 
 * that identifies its hierarchical relationship.
 */
abstract class EmbeddedLiveComponent extends AbstractLiveComponent
{
    use HasParentInjection;

    public static function isLiveComponent(object $component): bool
    {
        if (!($component instanceof EmbeddedLiveComponent)) {
            return false;
        }

        $ref = new \ReflectionClass($component);
        foreach ($ref->getProperties() as $prop) {
            if (!empty($prop->getAttributes(Attribute\State::class))) {
                return true;
            }
        }
        foreach ($ref->getMethods(\ReflectionMethod::IS_PUBLIC) as $method) {
            if (!empty($method->getAttributes(Attribute\LiveAction::class))) {
                return true;
            }
        }
        return false;
    }

    public function toHtml(): string
    {
        $metadata = $this->getLiveMetadata();

        $liveStateAttr = htmlspecialchars(
            json_encode($metadata, JSON_UNESCAPED_UNICODE),
            ENT_QUOTES, 'UTF-8'
        );

        $parentAttr = $this->parent ? sprintf(' data-live-parent-id="%s"', $this->parent->getComponentId()) : '';

        return sprintf(
            '<div %s data-live="%s" data-live-id="%s" data-live-state="%s"%s%s>%s</div>',
            $this->loading ? 'data-loading' : '',
            static::class,
            $this->componentId,
            $liveStateAttr,
            $parentAttr,
            $this->getListenersAttribute(),
            $this->render()
        );
    }

    protected function getListenersAttribute(): string
    {
        $events = $this->getListenerEvents();
        if (empty($events)) return '';
        return sprintf(' data-live-listeners="%s"', htmlspecialchars(implode(',', $events), ENT_QUOTES, 'UTF-8'));
    }
}
