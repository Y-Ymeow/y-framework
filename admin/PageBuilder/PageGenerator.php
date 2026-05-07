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

    public function generate(string $name, string $route, string $template = 'blank'): array
    {
        $className = $this->sanitizeClassName($name);

        $existing = $this->findDbRecord($className);
        if ($existing) {
            return ['success' => false, 'error' => '页面已存在'];
        }

        $this->upsertDbRecord($className, $route, []);

        $this->registerDynamicRoute($className, $route);

        return [
            'success' => true,
            'class' => $this->namespace . '\\' . $className,
            'route' => $route,
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
                    'file' => $this->pagesPath . '/' . $row['name'] . '.php',
                    'route' => $row['route'] ?? '/',
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
                PageBuilderPageModel::create([
                    'name' => $className,
                    'route' => $route,
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

    protected function upsertDbRecord(string $className, ?string $route = null, ?array $tree = null): void
    {
        try {
            $row = $this->findDbRecord($className);

            if (!$row) {
                $data = ['name' => $className];
                if ($route !== null) {
                    $data['route'] = $route;
                }
                if ($tree !== null) {
                    $data['component_tree'] = $tree;
                }
                PageBuilderPageModel::create($data);
            } else {
                $updateData = [];
                if ($route !== null) {
                    $updateData['route'] = $route;
                }
                if ($tree !== null) {
                    $updateData['component_tree'] = json_encode($tree, JSON_UNESCAPED_UNICODE);
                }
                if (!empty($updateData)) {
                    PageBuilderPageModel::where('name', $className)->update($updateData);
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

    protected function registerDynamicRoute(string $className, string $route): void
    {
        try {
            $app = \Framework\Foundation\Application::getInstance();
            $router = $app->make(\Framework\Routing\Router::class);
            $router->addRoute('GET', $route, function () use ($className) {
                $renderer = new PageRenderer();
                $response = $renderer->render($className);
                if ($response) {
                    return $response;
                }
                return \Framework\Http\Response\Response::html(
                    \Framework\View\Base\Element::make('div')->class('pb-page')->text('页面为空')
                );
            }, 'page.' . strtolower($className));
        } catch (\Throwable $e) {
            $this->logError('registerDynamicRoute failed', ['class' => $className, 'route' => $route, 'error' => $e->getMessage()]);
        }
    }

    protected function ensureDynamicRoute(string $className): void
    {
        try {
            $row = $this->findDbRecord($className);
            if ($row && !empty($row['route'])) {
                $this->registerDynamicRoute($className, $row['route']);
            }
        } catch (\Throwable $e) {
            $this->logError('ensureDynamicRoute failed', ['class' => $className, 'error' => $e->getMessage()]);
        }
    }

    protected function logError(string $message, array $context = []): void
    {
        try {
            logger()->error('[PageGenerator] ' . $message, $context);
        } catch (\Throwable) {
            error_log('[PageGenerator] ' . $message . ' ' . json_encode($context, JSON_UNESCAPED_UNICODE));
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
