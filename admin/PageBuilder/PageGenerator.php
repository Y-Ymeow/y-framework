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
        $filePath = $this->pagesPath . '/' . $className . '.php';

        if (file_exists($filePath)) {
            return ['success' => false, 'error' => '页面文件已存在: ' . $filePath];
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

        if (!file_exists($filePath)) {
            return ['success' => false, 'error' => '页面文件不存在'];
        }

        unlink($filePath);

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
            $hasRoute = !empty($route);

            $pages[] = [
                'name' => $className,
                'file' => $file,
                'route' => $route,
                'has_route' => $hasRoute,
                'size' => filesize($file),
                'modified' => date('Y-m-d H:i:s', filemtime($file)),
            ];
        }

        return $pages;
    }

    public function getPageContent(string $name): ?string
    {
        $filePath = $this->pagesPath . '/' . $this->sanitizeClassName($name) . '.php';
        if (!file_exists($filePath)) {
            return null;
        }
        return file_get_contents($filePath);
    }

    public function updatePageContent(string $name, string $content): array
    {
        $filePath = $this->pagesPath . '/' . $this->sanitizeClassName($name) . '.php';
        if (!file_exists($filePath)) {
            return ['success' => false, 'error' => '页面文件不存在'];
        }

        file_put_contents($filePath, $content);

        return ['success' => true];
    }

    protected function renderTemplate(string $className, string $route, string $template): string
    {
        return match ($template) {
            'list' => $this->getListTemplate($className, $route),
            'detail' => $this->getDetailTemplate($className, $route),
            'form' => $this->getFormTemplate($className, $route),
            'landing' => $this->getLandingTemplate($className, $route),
            default => $this->getBlankTemplate($className, $route),
        };
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
            ->class('p-8', 'max-w-4xl', 'mx-auto')
            ->child(Element::make('h1')->class('text-3xl', 'font-bold', 'mb-4')->text('{$className}'))
            ->child(Element::make('p')->class('text-gray-600')->text('This is a new page.'));

        return Response::html(\$page);
    }
}
PHP;
    }

    protected function getListTemplate(string $className, string $route): string
    {
        return <<<PHP
<?php

namespace App\Pages;

use Framework\Routing\Attribute\Route;
use Framework\Http\Response\Response;
use Framework\View\Base\Element;
use Framework\UX\Data\DataTable;
use Framework\UX\UI\Button;

class {$className}
{
    #[Route('{$route}', methods: ['GET'])]
    public function __invoke(): Response
    {
        \$table = DataTable::make()
            ->column('id', 'ID')
            ->column('name', 'Name')
            ->column('created_at', 'Created At');

        \$wrapper = Element::make('div')
            ->class('p-8', 'max-w-6xl', 'mx-auto')
            ->child(Element::make('h1')->class('text-2xl', 'font-bold', 'mb-6')->text('{$className}'))
            ->child(\$table);

        return Response::html(\$wrapper);
    }
}
PHP;
    }

    protected function getDetailTemplate(string $className, string $route): string
    {
        return <<<PHP
<?php

namespace App\Pages;

use Framework\Routing\Attribute\Route;
use Framework\Http\Response\Response;
use Framework\View\Base\Element;
use Framework\UX\Data\DescriptionList;

class {$className}
{
    #[Route('{$route}', methods: ['GET'])]
    public function __invoke(): Response
    {
        \$list = DescriptionList::make()
            ->item('ID', '1')
            ->item('Name', 'Example')
            ->item('Created', date('Y-m-d'));

        \$wrapper = Element::make('div')
            ->class('p-8', 'max-w-4xl', 'mx-auto')
            ->child(Element::make('h1')->class('text-2xl', 'font-bold', 'mb-6')->text('{$className}'))
            ->child(\$list);

        return Response::html(\$wrapper);
    }
}
PHP;
    }

    protected function getFormTemplate(string $className, string $route): string
    {
        return <<<PHP
<?php

namespace App\Pages;

use Framework\Routing\Attribute\Route;
use Framework\Http\Response\Response;
use Framework\View\Base\Element;
use Framework\UX\Form\FormBuilder;

class {$className}
{
    #[Route('{$route}', methods: ['GET', 'POST'])]
    public function __invoke(): Response
    {
        \$form = FormBuilder::make()
            ->post()
            ->action('{$route}')
            ->text('name', 'Name', ['required' => true])
            ->email('email', 'Email', ['required' => true])
            ->textarea('message', 'Message', [])
            ->submitLabel('Submit');

        \$wrapper = Element::make('div')
            ->class('p-8', 'max-w-4xl', 'mx-auto')
            ->child(Element::make('h1')->class('text-2xl', 'font-bold', 'mb-6')->text('{$className}'))
            ->child(\$form);

        return Response::html(\$wrapper);
    }
}
PHP;
    }

    protected function getLandingTemplate(string $className, string $route): string
    {
        return <<<PHP
<?php

namespace App\Pages;

use Framework\Routing\Attribute\Route;
use Framework\Http\Response\Response;
use Framework\View\Base\Element;
use Framework\UX\UI\Button;

class {$className}
{
    #[Route('{$route}', methods: ['GET'])]
    public function __invoke(): Response
    {
        \$hero = Element::make('div')
            ->class('min-h-screen', 'flex', 'items-center', 'justify-center', 'bg-gradient-to-r', 'from-blue-500', 'to-purple-600')
            ->child(
                Element::make('div')->class('text-center', 'text-white')
                    ->child(Element::make('h1')->class('text-5xl', 'font-bold', 'mb-4')->text('{$className}'))
                    ->child(Element::make('p')->class('text-xl', 'mb-8')->text('Welcome to our website'))
                    ->child(
                        Button::make()->label('Get Started')->primary()->lg()
                            ->on('click', 'window.location.href="/about"')
                    )
            );

        return Response::html(\$hero);
    }
}
PHP;
    }

    protected function sanitizeClassName(string $name): string
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
