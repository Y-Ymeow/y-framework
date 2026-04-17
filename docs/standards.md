# Standards Notes

## Goal

This framework should stay interoperable without importing the usual runtime cost of heavy abstraction everywhere.

## Standards to target

- PSR-1 / PSR-12: code style
- PSR-4: autoloading
- PSR-3: logger contract later
- PSR-7: request/response adapter layer later
- PSR-11: container adapter layer later
- PSR-15: middleware contract later
- PSR-17: factory adapter layer later

## Important clarification

There is no official PSR for routing itself.

So routing should be designed around:

- compiled metadata
- attribute collection
- file-based functional route definitions
- zero reflection in production

## Current direction

- Internal runtime types stay lean and framework-native.
- Compatibility should be provided through adapters, not by forcing every hot-path object to be a PSR implementation first.
- Business code is allowed to stay function-oriented.
- Controller classes remain supported, but they are not the only path.

## Request/response plan

Current `Request` and `Response` are native runtime objects.

Later we should add:

- `PsrRequestAdapter`
- `PsrResponseAdapter`
- optional PSR factory bridge

That keeps the hot path simple while preserving interoperability.

## Database plan

There is no PSR-level ORM or query-builder standard.

So the database layer should optimize for:

- direct PDO access
- transparent SQL
- safe bindings
- minimal builder overhead
- no hidden lifecycle system
