<?php

declare(strict_types=1);

namespace Framework\Component\Live;

use Framework\Http\Request\Request;
use Framework\Http\Response\Response;

/**
 * LiveComponentResolver bridges the routing system to LiveRequestHandler
 * for both HTTP requests and programmatic test invocations.
 *
 * In production, the router dispatches directly to LiveRequestHandler
 * methods via #[Route] attributes. This resolver exists as a convenience
 * wrapper for tests and console commands that need to simulate live
 * requests without going through the full HTTP stack.
 */
class LiveComponentResolver
{
    /**
     * Resolve a live request through the handler.
     *
     * Routes to handleAction() or handleStateUpdate() based on the
     * presence of an _action parameter.
     */
    public function handle(Request $request): Response
    {
        $handler = new LiveRequestHandler();

        // If no _action is specified, treat as state update
        $action = $request->input('_action');
        if (empty($action)) {
            return $handler->handleStateUpdate($request);
        }

        // For backward compatibility, check if this is a pure property update
        if ($action === '__updateProperty') {
            return $handler->handleStateUpdate($request);
        }

        return $handler->handleAction($request);
    }
}
