<?php

declare(strict_types=1);

namespace Admin\PageBuilder;

class ComponentGenerator
{
    protected string $componentsPath;
    protected string $namespace = 'App\\Components';

    public function __construct()
    {
        $this->componentsPath = base_path('/app/Components');
    }

    public function generate(string $name, string $description = ''): array
    {
        $className = $this->sanitizeClassName($name);
        $filePath = $this->componentsPath . '/' . $className . '.php';

        if (file_exists($filePath)) {
            return ['success' => false, 'error' => '组件文件已存在'];
        }

        $content = $this->renderComponentTemplate($className, $description);

        if (!is_dir($this->componentsPath)) {
            mkdir($this->componentsPath, 0755, true);
        }

        file_put_contents($filePath, $content);

        return [
            'success' => true,
            'file' => $filePath,
            'class' => $this->namespace . '\\' . $className,
        ];
    }

    public function listComponents(): array
    {
        if (!is_dir($this->componentsPath)) {
            return [];
        }

        $components = [];
        foreach (glob($this->componentsPath . '/*.php') as $file) {
            $className = basename($file, '.php');
            $components[] = [
                'name' => $className,
                'file' => $file,
                'size' => filesize($file),
                'modified' => date('Y-m-d H:i:s', filemtime($file)),
            ];
        }

        return $components;
    }

    protected function renderComponentTemplate(string $className, string $description): string
    {
        $desc = $description ?: $className;
        return <<<PHP
<?php

namespace App\Components;

use Framework\Component\Live\LiveComponent;
use Framework\View\Base\Element;

class {$className} extends LiveComponent
{
    public string \$title = '';

    public function mount(): void
    {
    }

    public function render(): Element
    {
        return Element::make('div')
            ->class('{$this->toKebab($className)}')
            ->child(Element::make('h2')->class('text-xl', 'font-bold')->text(\$title ?: '{$desc}'));
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
        return $name ?: 'Component';
    }

    protected function toKebab(string $name): string
    {
        return strtolower(preg_replace('/(?<!^)[A-Z]/', '-$0', $name));
    }
}
