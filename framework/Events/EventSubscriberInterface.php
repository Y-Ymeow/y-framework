<?php

declare(strict_types=1);

namespace Framework\Events;

interface EventSubscriberInterface
{
    public static function getSubscribedEvents(): array;
}
