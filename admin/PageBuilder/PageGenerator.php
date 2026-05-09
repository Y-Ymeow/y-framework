<?php

declare(strict_types=1);

namespace Admin\PageBuilder;

class PageGenerator
{
    protected string $pagesPath;
    protected string $namespace = 'App\\Pages';

    public function __construct()
    {
        $this->pagesPath = base_path('/app/Pages');
    }

    public function generate(string $name, string $route, string $template = 'blank', array $options = []): array
    {
        $className = $this->sanitizeClassName($name);
        $slug = $this->sanitizeSlug($options['slug'] ?? $name);
        $middleware = $this->normalizeMiddleware($options['middleware'] ?? []);
        $route = $this->normalizeRoute($route, $slug);

        $existing = $this->findDbRecord($className);
        if ($existing) {
            return ['success' => false, 'error' => '页面已存在'];
        }

        $this->upsertDbRecord($className, $route, [], [
            'slug' => $slug,
            'middleware' => $middleware,
        ]);

        $this->registerDynamicRoute($className, $route, $middleware);

        return [
            'success' => true,
            'class' => $this->namespace . '\\' . $className,
            'slug' => $slug,
            'route' => $route,
            'middleware' => $middleware,
        ];
    }

    public function delete(string $name): array
    {
        $className = $this->sanitizeClassName($name);

        try {
            PageBuilderPageModel::where('name', $className)->delete();
        } catch (\Throwable $e) {
            $this->logError('delete db record failed', ['class' => $className, 'error' => $e->getMessage()]);
        }

        $filePath = $this->pagesPath . '/' . $className . '.php';
        if (file_exists($filePath)) {
            unlink($filePath);
        }

        return ['success' => true];
    }

    public function listPages(): array
    {
        $rows = $this->syncFilesystemToDb();

        $pages = [];
        try {
            $items = [];
            foreach ($rows as $row) {
                $item = ($row instanceof \Framework\Support\Collection) ? $row->toArray() : (method_exists($row, 'toArray') ? $row->toArray() : (array) $row);
                $tree = $item['component_tree'] ?? null;
                if (is_string($tree)) {
                    $tree = json_decode($tree, true);
                }
                $item['component_tree'] = $tree;
                $items[] = $item;
            }
            usort($items, fn($a, $b) => strtotime($b['updated_at'] ?? '') <=> strtotime($a['updated_at'] ?? ''));
            foreach ($items as $row) {
                $pages[] = [
                    'name' => $row['name'],
                    'slug' => $row['slug'] ?? $this->sanitizeSlug($row['name'] ?? ''),
                    'file' => $this->pagesPath . '/' . $row['name'] . '.php',
                    'route' => $row['route'] ?? '/',
                    'middleware' => $this->normalizeMiddleware($row['middleware'] ?? []),
                    'has_route' => !empty($row['route']) && $row['route'] !== '/',
                    'has_builder' => !empty($row['component_tree']),
                    'modified' => $row['updated_at'] ?? $row['created_at'] ?? '',
                ];
            }
        } catch (\Throwable $e) {
            $this->logError('listPages failed', ['error' => $e->getMessage()]);
        }

        return $pages;
    }

    protected function syncFilesystemToDb(): \Framework\Support\Collection
    {
        $rows = PageBuilderPageModel::all();

        if (!is_dir($this->pagesPath)) {
            return $rows;
        }

        $existingNames = [];
        foreach ($rows as $row) {
            $item = ($row instanceof \Framework\Support\Collection) ? $row->toArray() : (method_exists($row, 'toArray') ? $row->toArray() : (array) $row);
            $existingNames[$item['name'] ?? ''] = true;
        }

        foreach (glob($this->pagesPath . '/*.php') as $file) {
            $className = basename($file, '.php');
            if (isset($existingNames[$className])) continue;

            $content = file_get_contents($file);
            $route = $this->extractRoute($content) ?? '/';

            $tree = [];
            $jsonPath = $this->pagesPath . '/' . $className . '.json';
            if (file_exists($jsonPath)) {
                $json = file_get_contents($jsonPath);
                $data = json_decode($json, true);
                if (is_array($data)) {
                    $tree = $data;
                }
            }

            try {
                $this->createRecordWithFallback([
                    'name' => $className,
                    'slug' => $this->sanitizeSlug($className),
                    'route' => $route,
                    'middleware' => [],
                    'component_tree' => $tree ?: null,
                ]);
            } catch (\Throwable $e) {
                $this->logError('syncFilesystemToDb create failed', ['class' => $className, 'error' => $e->getMessage()]);
            }
        }

        return $rows;
    }

    public function getComponentTree(string $name): array
    {
        $className = $this->sanitizeClassName($name);

        try {
            $row = $this->findDbRecord($className);
            if ($row) {
                $tree = $row['component_tree'] ?? null;
                if (is_string($tree)) {
                    $tree = json_decode($tree, true);
                }
                if (!empty($tree) && is_array($tree)) {
                    return $tree;
                }
            }
        } catch (\Throwable $e) {
            $this->logError('getComponentTree failed', ['class' => $className, 'error' => $e->getMessage()]);
        }

        $jsonPath = $this->pagesPath . '/' . $className . '.json';
        if (file_exists($jsonPath)) {
            $json = file_get_contents($jsonPath);
            $data = json_decode($json, true);
            if (is_array($data) && !empty($data)) {
                $this->upsertDbRecord($className, null, $data);
                return $data;
            }
        }

        return [];
    }

    public function saveComponentTree(string $name, array $tree): array
    {
        $className = $this->sanitizeClassName($name);

        $this->upsertDbRecord($className, null, $tree);

        $this->ensureDynamicRoute($className);

        return ['success' => true];
    }

    public function updatePage(string $name, array $options): array
    {
        $className = $this->sanitizeClassName($name);
        $existing = $this->findDbRecord($className);
        if (!$existing) {
            return ['success' => false, 'error' => '页面不存在'];
        }

        $newName = null;
        if (isset($options['name']) && $options['name'] !== $className) {
            $newName = $this->sanitizeClassName($options['name']);
            if ($this->findDbRecord($newName)) {
                return ['success' => false, 'error' => '目标名称已存在'];
            }
        }

        $targetName = $newName ?? $className;
        $slug = isset($options['slug']) ? $this->sanitizeSlug($options['slug']) : ($existing['slug'] ?? $this->sanitizeSlug($className));
        $middleware = isset($options['middleware']) ? $this->normalizeMiddleware($options['middleware']) : $this->normalizeMiddleware($existing['middleware'] ?? []);
        $route = $this->normalizeRoute($options['route'] ?? ($existing['route'] ?? ''), $slug);

        if ($newName) {
            $tree = $existing['component_tree'] ?? null;
            if (is_string($tree)) $tree = json_decode($tree, true);

            // Create new record with all data
            $data = ['name' => $newName, 'slug' => $slug, 'route' => $route, 'middleware' => $middleware];
            if ($tree) $data['component_tree'] = $tree;
            $this->createRecordWithFallback($data);

            // Delete old record
            PageBuilderPageModel::where('name', $className)->delete();

            // Rename PHP file
            $oldFile = $this->pagesPath . '/' . $className . '.php';
            $newFile = $this->pagesPath . '/' . $newName . '.php';
            if (file_exists($oldFile)) {
                $content = str_replace("class {$className}", "class {$newName}", file_get_contents($oldFile));
                file_put_contents($newFile, $content);
                unlink($oldFile);
            }
        } else {
            $this->upsertDbRecord($className, $route, null, [
                'slug' => $slug,
                'middleware' => $middleware,
            ]);
        }

        $this->registerDynamicRoute($targetName, $route, $middleware);

        return ['success' => true];
    }

    protected function upsertDbRecord(string $className, ?string $route = null, ?array $tree = null, array $meta = []): void
    {
        try {
            $row = $this->findDbRecord($className);

            if (!$row) {
                $data = ['name' => $className];
                $data['slug'] = $meta['slug'] ?? $this->sanitizeSlug($className);
                if ($route !== null) {
                    $data['route'] = $route;
                }
                if (array_key_exists('middleware', $meta)) {
                    $data['middleware'] = $this->normalizeMiddleware($meta['middleware']);
                }
                if ($tree !== null) {
                    $data['component_tree'] = $tree;
                }
                $this->createRecordWithFallback($data);
            } else {
                $updateData = [];
                if (array_key_exists('slug', $meta)) {
                    $updateData['slug'] = $meta['slug'];
                }
                if ($route !== null) {
                    $updateData['route'] = $route;
                }
                if (array_key_exists('middleware', $meta)) {
                    $updateData['middleware'] = json_encode($this->normalizeMiddleware($meta['middleware']), JSON_UNESCAPED_UNICODE);
                }
                if ($tree !== null) {
                    $updateData['component_tree'] = json_encode($tree, JSON_UNESCAPED_UNICODE);
                }
                if (!empty($updateData)) {
                    $this->updateRecordWithFallback($className, $updateData);
                }
            }
        } catch (\Throwable $e) {
            $this->logError('upsertDbRecord failed', ['class' => $className, 'error' => $e->getMessage()]);
        }
    }

    protected function findDbRecord(string $className): ?array
    {
        $row = PageBuilderPageModel::where('name', $className)->first();
        if ($row === null) {
            return null;
        }
        if ($row instanceof \Framework\Support\Collection) {
            return $row->toArray();
        }
        if (is_array($row)) {
            return $row;
        }
        if (method_exists($row, 'toArray')) {
            return $row->toArray();
        }
        return null;
    }

    protected function registerDynamicRoute(string $className, string $route, array $middleware = []): void
    {
        try {
            $app = \Framework\Foundation\Application::getInstance();
            $router = $app->make(\Framework\Routing\Router::class);
            $routeObj = $router->addRoute('GET', $route, function () use ($className) {
                $renderer = new \App\Service\PageRenderer();
                $response = $renderer->render($className);
                if ($response) {
                    return $response;
                }
                return \Framework\Http\Response\Response::html(
                    \Framework\View\Base\Element::make('div')->class('pb-page')->text('页面为空')
                );
            }, 'page.' . strtolower($className));
            if (!empty($middleware)) {
                $routeObj->middleware($middleware);
            }
        } catch (\Throwable $e) {
            $this->logError('registerDynamicRoute failed', ['class' => $className, 'route' => $route, 'error' => $e->getMessage()]);
        }
    }

    protected function ensureDynamicRoute(string $className): void
    {
        try {
            $row = $this->findDbRecord($className);
            if ($row && !empty($row['route'])) {
                $this->registerDynamicRoute($className, $row['route'], $this->normalizeMiddleware($row['middleware'] ?? []));
            }
        } catch (\Throwable $e) {
            $this->logError('ensureDynamicRoute failed', ['class' => $className, 'error' => $e->getMessage()]);
        }
    }

    protected function createRecordWithFallback(array $data): void
    {
        try {
            PageBuilderPageModel::create($data);
        } catch (\Throwable $e) {
            unset($data['slug'], $data['middleware']);
            PageBuilderPageModel::create($data);
        }
    }

    protected function updateRecordWithFallback(string $className, array $data): void
    {
        try {
            PageBuilderPageModel::where('name', $className)->update($data);
        } catch (\Throwable $e) {
            unset($data['slug'], $data['middleware']);
            if (!empty($data)) {
                PageBuilderPageModel::where('name', $className)->update($data);
            }
        }
    }

    protected function normalizeRoute(string $route, string $slug): string
    {
        $route = trim($route);
        if ($route === '') {
            $route = '/' . $slug;
        }
        return '/' . trim($route, '/');
    }

    protected function sanitizeSlug(string $slug): string
    {
        $slug = strtolower(trim($slug));
        $slug = preg_replace('/[^a-z0-9_-]+/', '-', $slug);
        $slug = trim((string) $slug, '-_');
        return $slug !== '' ? $slug : 'page';
    }

    protected function normalizeMiddleware(string|array $middleware): array
    {
        if (is_string($middleware)) {
            $middleware = preg_split('/[,\n]+/', $middleware) ?: [];
        }
        return array_values(array_filter(array_map(
            static fn($item) => trim((string) $item),
            $middleware
        )));
    }

    protected function logError(string $message, array $context = []): void
    {
        try {
            logger()->error('[PageGenerator] ' . $message, $context);
        } catch (\Throwable) {
            logger()->error('[PageGenerator] ' . $message);
        }
    }

    public function generatePhpFromTree(string $className, array $tree): void
    {
        $filePath = $this->pagesPath . '/' . $className . '.php';

        $route = '/';
        if (file_exists($filePath)) {
            $content = file_get_contents($filePath);
            $route = $this->extractRoute($content) ?? '/';
        }

        $renderCode = $this->buildRenderCode($tree);

        $php = <<<PHP
<?php

namespace App\Pages;

use Framework\Routing\Attribute\Route;
use Framework\Http\Response\Response;
use Framework\View\Base\Element;
use Admin\PageBuilder\Components\ComponentRegistry;

class {$className}
{
    #[Route('{$route}', methods: ['GET'])]
    public function __invoke(): Response
    {
        \$page = Element::make('div')->class('pb-page');
{$renderCode}
        return Response::html(\$page);
    }
}
PHP;

        file_put_contents($filePath, $php);
    }

    protected function buildRenderCode(array $tree, string $var = '$page', int $indent = 2): string
    {
        $code = '';
        $pad = str_repeat('    ', $indent);

        foreach ($tree as $index => $component) {
            $type = $component['type'] ?? '';
            $settings = $component['settings'] ?? [];
            $children = $component['children'] ?? [];
            $uid = $component['uid'] ?? $index;

            $settingsExport = var_export($settings, true);
            $compVar = "\$comp_{$uid}";

            $code .= "\n{$pad}\$type_{$uid} = ComponentRegistry::get('{$type}');";
            $code .= "\n{$pad}if (\$type_{$uid}) {";
            $code .= "\n{$pad}    {$compVar} = \$type_{$uid}->render({$settingsExport});";

            if (!empty($children)) {
                $childCode = $this->buildRenderCode($children, $compVar, $indent + 2);
                $code .= $childCode;
            }

            $code .= "\n{$pad}    {$var}->child({$compVar});";
            $code .= "\n{$pad}}";
        }

        return $code;
    }

    protected function renderTemplate(string $className, string $route, string $template): string
    {
        return $this->getBlankTemplate($className, $route);
    }

    protected function getBlankTemplate(string $className, string $route): string
    {
        return <<<PHP
<?php

namespace App\Pages;

use Framework\Routing\Attribute\Route;
use Framework\Http\Response\Response;
use Framework\View\Base\Element;

class {$className}
{
    #[Route('{$route}', methods: ['GET'])]
    public function __invoke(): Response
    {
        \$page = Element::make('div')
            ->class('pb-page', 'p-8', 'max-w-4xl', 'mx-auto')
            ->child(Element::make('h1')->class('text-3xl', 'font-bold', 'mb-4')->text('{$className}'))
            ->child(Element::make('p')->class('text-gray-600')->text('This is a new page.'));

        return Response::html(\$page);
    }
}
PHP;
    }

    public function sanitizeClassName(string $name): string
    {
        $name = preg_replace('/[^a-zA-Z0-9_]/', '', $name);
        if (!empty($name) && !preg_match('/^[A-Z]/', $name)) {
            $name = ucfirst($name);
        }
        return $name ?: 'Page';
    }

    protected function extractRoute(string $content): ?string
    {
        if (preg_match("/#\[Route\('([^']+)'/", $content, $matches)) {
            return $matches[1];
        }
        if (preg_match("/#\[Route\(path:\s*'([^']+)'/", $content, $matches)) {
            return $matches[1];
        }
        return null;
    }
}
