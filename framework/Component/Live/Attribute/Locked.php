<?php

declare(strict_types=1);

namespace Framework\Component\Live\Attribute;

#[\Attribute(\Attribute::TARGET_PROPERTY)]
class Locked
{
    /**
     * Mark a public property as immutable from the frontend.
     *
     * Properties annotated with #[Locked] cannot be modified via
     * live-model updates or state payloads. They are still included
     * in the serialized state and can be read by the frontend.
     *
     * This is an explicit, separate attribute from #[State] so that
     * non-State properties (e.g. #[Prop]) can also be locked without
     * needing a State annotation.
     *
     * When both #[Locked] and #[State(frontendEditable:true)] are
     * present, #[Locked] takes precedence and the property remains
     * immutable from the frontend.
     */
    public function __construct(
        public string $reason = '',
    ) {}
}
