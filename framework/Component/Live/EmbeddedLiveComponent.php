<?php

declare(strict_types=1);

namespace Framework\Component\Live;

use Framework\Component\Live\Concerns\HasParentInjection;

/**
 * Base class for Live components that can be embedded (nested)
 * inside other Live components.
 *
 * Provides automatic parent injection so child components can
 * interact with their parent's action methods and state.
 */
abstract class EmbeddedLiveComponent extends LiveComponent
{
    use HasParentInjection;
}
