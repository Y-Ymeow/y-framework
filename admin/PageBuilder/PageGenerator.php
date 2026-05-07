<?php

declare(strict_types=1);

namespace Admin\PageBuilder;

use Admin\PageBuilder\Components\ComponentRegistry;

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
        $filePath = $this->pagesPath . '/' . $className . '.php';

        if (file_exists($filePath)) {
            return ['success' => false, 'error' => '页面文件已存在'];
        }

        $content = $this->renderTemplate($className, $route, $template);

        if (!is_dir($this->pagesPath)) {
            mkdir($this->pagesPath, 0755, true);
        }

        file_put_contents($filePath, $content);

        return [
            'success' => true,
            'file' => $filePath,
            'class' => $this->namespace . '\\' . $className,
            'route' => $route,
        ];
    }

    public function delete(string $name): array
    {
        $className = $this->sanitizeClassName($name);
        $filePath = $this->pagesPath . '/' . $className . '.php';
        $jsonPath = $this->pagesPath . '/' . $className . '.json';

        if (!file_exists($filePath)) {
            return ['success' => false, 'error' => '页面文件不存在'];
        }

        unlink($filePath);
        if (file_exists($jsonPath)) {
            unlink($jsonPath);
        }

        return ['success' => true, 'file' => $filePath];
    }

    public function listPages(): array
    {
        if (!is_dir($this->pagesPath)) {
            return [];
        }

        $pages = [];
        foreach (glob($this->pagesPath . '/*.php') as $file) {
            $className = basename($file, '.php');
            $content = file_get_contents($file);
            $route = $this->extractRoute($content);
            $hasBuilder = file_exists($this->pagesPath . '/' . $className . '.json');

            $pages[] = [
                'name' => $className,
                'file' => $file,
                'route' => $route,
                'has_route' => !empty($route),
                'has_builder' => $hasBuilder,
                'modified' => date('Y-m-d H:i:s', filemtime($file)),
            ];
        }

        return $pages;
    }

    public function getComponentTree(string $name): array
    {
        $jsonPath = $this->pagesPath . '/' . $this->sanitizeClassName($name) . '.json';
        if (!file_exists($jsonPath)) {
            return [];
        }

        $json = file_get_contents($jsonPath);
        $data = json_decode($json, true);
        return is_array($data) ? $data : [];
    }

    public function saveComponentTree(string $name, array $tree): array
    {
        $className = $this->sanitizeClassName($name);
        $jsonPath = $this->pagesPath . '/' . $className . '.json';

        if (!is_dir($this->pagesPath)) {
            mkdir($this->pagesPath, 0755, true);
        }

        file_put_contents($jsonPath, json_encode($tree, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        $this->generatePhpFromTree($className, $tree);

        return ['success' => true];
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

            $settingsJson = json_encode($settings, JSON_UNESCAPED_UNICODE);
            $compVar = "\$comp_{$uid}";

            $code .= "\n{$pad}\$type_{$uid} = ComponentRegistry::get('{$type}');";
            $code .= "\n{$pad}if (\$type_{$uid}) {";
            $code .= "\n{$pad}    {$compVar} = \$type_{$uid}->render({$settingsJson});";

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
