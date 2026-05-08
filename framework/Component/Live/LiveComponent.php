<?php

declare(strict_types=1);

namespace Framework\Component\Live;

/**
 * Standard Live component (Standalone).
 * 
 * Provides the default full-wrapper HTML output with data-live-state.
 * Use this for top-level or isolated reactive components.
 */
class LiveComponent extends AbstractLiveComponent
{
    public function toHtml(): string
    {
        $metadata = $this->getLiveMetadata();

        $liveStateAttr = htmlspecialchars(
            json_encode($metadata, JSON_UNESCAPED_UNICODE),
            ENT_QUOTES, 'UTF-8'
        );

        $listenersAttr = '';
        $listenerEvents = $this->getListenerEvents();
        if (!empty($listenerEvents)) {
            $listenersAttr = sprintf(' data-live-listeners="%s"', htmlspecialchars(implode(',', $listenerEvents), ENT_QUOTES, 'UTF-8'));
        }

        return sprintf(
            '<div %s data-live="%s" data-live-id="%s" data-live-state="%s"%s>%s</div>',
            $this->loading ? 'data-loading' : '',
            static::class,
            $this->componentId,
            $liveStateAttr,
            $listenersAttr,
            $this->render()
        );
    }
}
