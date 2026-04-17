# PHP Framework Plan

## 1. Goals

- Build a high-performance PHP framework with clear internal boundaries.
- Follow PSR standards where they improve interoperability and maintainability.
- Keep the runtime core class-based and stable.
- Keep application development concise with functions, multi-file composition, and low ceremony.
- Favor compile-time and cache-time work over request-time work.
- Design features around measurable performance, predictable behavior, and debuggability.

## 2. Core Principles

- Performance first: reduce reflection, parsing, dynamic lookup, and container overhead in hot paths.
- Compile ahead when possible: routes, templates, config, metadata, and service maps should be cached.
- Native PHP first: emit plain PHP code for templates and cached artifacts.
- SQL first ORM: keep generated SQL transparent, controllable, and close to raw SQL.
- Minimal magic: runtime behavior should be inspectable from generated cache files.
- Safety by default: escaping, parameter binding, CSRF, cookie flags, header normalization, and input handling should be built in.

## 3. Architecture Direction

### 3.1 Runtime core

- `Kernel`: bootstraps environment, config, providers, cache, router, and request lifecycle.
- `Application`: container for global state and service access.
- `Context`: per-request runtime context.
- `Config`: immutable compiled config repository.
- `Cache`: filesystem-first cache for generated metadata and compiled artifacts.
- `ErrorHandler`: exception, error, shutdown, and debug rendering.

### 3.2 PSR scope

Recommended initial PSR targets:

- PSR-1 / PSR-12: code style and basic coding conventions.
- PSR-4: autoloading.
- PSR-7: HTTP messages, if implementation cost stays acceptable.
- PSR-11: container interface compatibility.
- PSR-15: middleware handling.
- PSR-17: HTTP factories, optional adapter layer.
- PSR-3: logging interface.

Design note:

- Internal implementation can stay optimized and only expose PSR-compatible adapters if direct PSR abstractions add avoidable overhead.

### 3.3 Developer-facing style

- Framework internals use classes and interfaces.
- Userland can use helper functions for common tasks:
  - `config()`
  - `env()`
  - `db()`
  - `view()`
  - `route()`
  - `request()`
  - `response()`
- App code is split across many small files with explicit loading and compilation, not giant config arrays.

## 4. Directory Proposal

```text
src/
  Core/
  Http/
  Routing/
  Container/
  Config/
  Cache/
  View/
  Database/
  ORM/
  Security/
  Support/
  Debug/

functions/
  helpers.php
  http.php
  db.php
  view.php

bootstrap/
  app.php
  cache.php

config/
  app.php
  database.php
  view.php
  cache.php

routes/
  web.php
  api.php

resources/
  views/

storage/
  cache/
  logs/

public/
  index.php

tests/
```

## 5. Module Breakdown

### 5.1 HTTP layer

Scope:

- Request object
- Response object
- Header and cookie management
- Uploaded file abstraction
- Middleware pipeline
- Emit response

Optimization direction:

- Avoid deep object graphs on every request.
- Reuse normalized header/value logic.
- Prefer immutable public API with optimized internal mutation during bootstrap.

### 5.2 Routing

Requirements:

- Attribute-based controller route registration.
- Optional file-based route definitions for functional style apps.
- Static route map compilation to PHP cache files.
- Fast path for literal routes.
- Separate handling for dynamic params and regex segments.
- Middleware and name metadata compiled with route entries.

Caching strategy:

- Scan classes once in dev or build step.
- Extract attributes.
- Compile route table into plain PHP array or generated matcher class.
- In production, no reflection during request handling.

Key decisions to settle:

- Whether route scanning runs at boot in dev only.
- Whether dynamic matching uses regex trees or segmented lookup tables.

### 5.3 Dependency container

Requirements:

- Lightweight DI container with PSR-11 compatibility.
- Constructor injection for framework classes.
- Compiled service definitions for production.
- Optional attribute-based injection only if measurable cost is acceptable.

Optimization direction:

- Avoid full reflection on every resolution.
- Compile resolvers to PHP closures or generated factory code.

### 5.4 Config system

Requirements:

- Multi-file config loading.
- Env overlay.
- Config compilation to one cached PHP file.
- Read-only config at runtime.

### 5.5 View / template DSL

Goal:

- Replace heavy general-purpose template engines with a DSL that compiles directly to efficient PHP.

Requirements:

- Simple HTML-oriented syntax.
- Layouts, includes, slots, loops, conditions.
- Escaped output by default.
- Raw output only with explicit syntax.
- Compile templates to PHP files.
- Template dependency tracking for invalidation in dev.

Design direction:

- DSL should map closely to HTML structure and common control flow.
- Parser should be deterministic and cheap.
- Compiler output must be readable PHP for debugging.

Questions to answer during design:

- Is the DSL indentation-based, tag-based, or hybrid?
- How much logic is allowed inside templates?
- Will components compile to functions, classes, or include files?

Performance requirement:

- Hot-path rendering should be near native PHP include performance after compilation.

### 5.6 Database layer

Requirements:

- PDO-based core.
- Thin SQL builder for common cases.
- First-class raw SQL support.
- Safe parameter binding.
- Read/write connection support later.
- Query logging and profiling hooks.

Optimization direction:

- Do not hide SQL.
- Avoid oversized abstraction layers.
- Keep statement preparation and binding explicit and inspectable.

### 5.7 ORM

Target philosophy:

- ORM is a convenience layer over SQL, not a replacement for SQL.

Requirements:

- Model metadata
- Table mapping
- Basic CRUD
- Relations
- Query builder integration
- Explicit eager loading
- Transactions
- Mass assignment rules
- Attribute casting kept minimal

Rules:

- Raw expressions and native SQL must remain easy to use.
- Prevent N+1 issues with explicit APIs and query debug tooling.
- No hidden query execution in magic accessors unless very clearly bounded.

Possible structure:

- `Connection`
- `QueryBuilder`
- `Model`
- `Repository` optional
- `Relation` abstractions
- `Hydrator`

### 5.8 Security

Must-have:

- Output escaping defaults in templates.
- SQL injection protection through bound parameters.
- CSRF protection.
- Cookie flags: `HttpOnly`, `Secure`, `SameSite`.
- Trusted proxy and IP handling.
- Request size / upload constraints.
- Safe session handling.
- Password hashing helpers.
- Signed URLs or tokens where useful.

Later:

- Rate limiting
- CSP helpers
- Audit hooks

### 5.9 Error handling and debugging

Requirements:

- Clean production error pages.
- Detailed dev exception page.
- Stack trace with source context.
- SQL query trace in debug mode.
- Route resolution trace in debug mode.

### 5.10 CLI tooling

Need a CLI early because caching and code generation are central:

- `cache:build`
- `cache:clear`
- `route:cache`
- `route:list`
- `config:cache`
- `view:cache`
- `make:controller`
- `make:model`

## 6. Cache Strategy

Everything expensive should have a compiled form:

- Config cache
- Route cache
- Template compilation cache
- Container/service map cache
- Model metadata cache

Cache output format:

- Prefer generated PHP files returning arrays or classes.
- Avoid JSON for hot-path metadata if PHP include/opcache is faster.

Environment policy:

- Dev: auto-rebuild with file timestamps or change detection.
- Prod: explicit cache build step, zero scanning/reflection on request.

## 7. Recommended Development Order

### Phase 1: skeleton

- Initialize `composer.json`
- Define namespace and PSR-4
- Create base directory structure
- Add coding standards and static analysis setup
- Create minimal `Kernel`, `Application`, `Request`, `Response`
- Add helper function loading

### Phase 2: HTTP and routing

- Implement request lifecycle
- Implement middleware pipeline
- Implement route definition model
- Implement attribute scanner
- Implement route compiler and cache
- Add controller dispatch

### Phase 3: config and container

- Config loader and compiler
- Basic container
- Service provider mechanism
- Production container compilation

### Phase 4: view DSL

- Define DSL grammar
- Write lexer/parser
- Write compiler to PHP
- Add layout/include/component support
- Add cache invalidation rules
- Benchmark against raw PHP, Twig, and Blade

### Phase 5: database and ORM

- PDO connection manager
- Query builder
- Transaction API
- Model base class
- Hydration
- Relations
- Debug query collector

### Phase 6: security and DX

- CSRF
- Sessions
- Validation baseline
- CLI generator commands
- Error pages and debug tools

### Phase 7: stabilization

- Benchmarks
- Integration tests
- API freeze for core contracts
- Example app
- Documentation site

## 8. Non-Functional Requirements

- PHP version target should be modern, preferably PHP 8.2+ or 8.3+.
- Strict types across framework code.
- Low memory footprint.
- Opcache-friendly generated artifacts.
- Clear extension points without sacrificing hot-path speed.
- Testability of all compiled outputs.

## 9. Benchmark Targets

You should define hard targets early. Suggested benchmarks:

- Hello world route latency
- Static route match time
- Dynamic route match time
- Template compile time
- Template render time after compile
- Query builder overhead versus raw PDO
- ORM hydration cost
- Memory usage per request

Compare against:

- Raw PHP baseline
- Laravel route + Blade baseline
- Slim / Symfony component baseline where relevant

## 10. Risks

- Overusing PSR abstractions may cost too much in hot paths.
- Template DSL complexity can grow into another slow template engine if syntax is too dynamic.
- Attribute scanning can become expensive without aggressive caching.
- ORM scope creep can turn a thin SQL-first design into a heavy abstraction.
- Too many helper functions can reduce clarity if not tightly scoped.

## 11. Immediate Task List

### A. Foundation

- Write `composer.json`
- Set PHP version target
- Set namespace
- Add autoload and helper autoload
- Add `.editorconfig`
- Add `phpcs` / `php-cs-fixer` decision
- Add `phpstan` or `psalm`
- Add test framework

### B. Core

- Implement `Application`
- Implement `Kernel`
- Implement `Context`
- Implement config repository
- Implement cache repository

### C. HTTP

- Implement request abstraction
- Implement response abstraction
- Implement middleware dispatcher
- Implement controller resolver

### D. Routing

- Design route attributes
- Build class scanner
- Build route metadata model
- Build route compiler
- Build cached matcher
- Add `route:list` command

### E. View DSL

- Write syntax proposal
- Build parser prototype
- Define escaping rules
- Compile to PHP
- Add layouts / includes / components
- Add benchmark suite

### F. Database / ORM

- Build connection manager
- Build query builder
- Add safe parameter API
- Build model mapper
- Add relation system
- Add transaction helper

### G. Security

- Add escaping helpers
- Add CSRF layer
- Add secure cookies
- Add session wrapper
- Add input filtering helpers

### H. Tooling

- Build CLI entry
- Add cache commands
- Add generators
- Add profiling hooks

### I. Quality

- Unit tests for compilers and matchers
- Integration tests for request lifecycle
- Benchmarks for hot paths
- Coding standards
- Static analysis baseline

## 12. Suggested First Deliverables

If the goal is to start implementation now, the most sensible first batch is:

1. Project skeleton with Composer, PSR-4, tests, and coding standards.
2. Minimal HTTP kernel with request -> route -> response flow.
3. Attribute route definition and route cache compiler.
4. Template DSL syntax draft and compiler spike.
5. PDO wrapper and SQL-first query builder.

## 13. Decisions That Should Be Written Next

These deserve separate design docs before heavy coding:

- Route attribute specification
- Template DSL grammar
- Container compilation format
- Query builder API surface
- ORM relation behavior
- Cache invalidation strategy
- Dev mode versus prod mode behavior

## 14. Recommended Documentation Tree

```text
docs/
  architecture.md
  routing.md
  template-dsl.md
  container.md
  database.md
  orm.md
  security.md
  cache.md
  benchmarks.md
```

## 15. Execution Summary

This framework should be built as a compiled, cache-heavy, SQL-first, class-based core with concise userland ergonomics. The main constraint is discipline: every abstraction must justify its runtime cost, and every expensive discovery mechanism should move into build time or cache time.
