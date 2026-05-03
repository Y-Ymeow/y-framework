<?php

declare(strict_types=1);

namespace Framework\Console\Commands;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Framework\Foundation\Application;

#[AsCommand(
    name: 'make:admin-resource',
    description: 'Create a new Admin Resource',
)]
class MakeAdminResourceCommand extends Command
{
    private Application $app;

    public function __construct(Application $app)
    {
        parent::__construct();
        $this->app = $app;
    }

    protected function configure(): void
    {
        $this->addArgument('name', InputArgument::REQUIRED, 'The name of the resource (e.g. User)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $name = $input->getArgument('name');
        
        $className = $this->getClassName($name) . 'Resource';
        $resourceName = strtolower($this->getClassName($name)) . 's';
        $namespace = $this->getNamespace($name);
        $filePath = $this->getFilePath($name);

        if (file_exists($filePath)) {
            $io->error("Resource [{$className}] already exists!");
            return Command::FAILURE;
        }

        $dir = dirname($filePath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $content = $this->getStub($className, $resourceName, $namespace, $this->getClassName($name));
        file_put_contents($filePath, $content);

        $io->success("Admin Resource [{$className}] created successfully.");
        $io->note("Path: {$filePath}");

        return Command::SUCCESS;
    }

    private function getClassName(string $name): string
    {
        $parts = explode('/', str_replace('\\', '/', $name));
        return end($parts);
    }

    private function getNamespace(string $name): string
    {
        $parts = explode('/', str_replace('\\', '/', $name));
        array_pop($parts);
        $subNamespace = empty($parts) ? '' : '\\' . implode('\\', $parts);
        return "Admin\\Resources" . $subNamespace;
    }

    private function getFilePath(string $name): string
    {
        $name = str_replace('\\', '/', $name);
        return $this->app->basePath("admin/Resources/{$name}Resource.php");
    }

    private function getStub(string $className, string $resourceName, string $namespace, string $modelName): string
    {
        return <<<PHP
<?php

declare(strict_types=1);

namespace {$namespace};

use Framework\Admin\Attribute\AdminResource;
use Framework\Admin\Resource\BaseResource;
use Framework\UX\Form\FormBuilder;
use Framework\UX\Data\DataTable;
use Framework\UX\Data\DataTableColumn;
use Framework\UX\UI\Button;
use Framework\UX\UI\Navigate;
use Framework\Database\Model;

#[AdminResource(
    name: '{$resourceName}',
    model: {$modelName}::class,
    title: '{$modelName}管理',
)]
class {$className} extends BaseResource
{
    public static function getName(): string
    {
        return '{$resourceName}';
    }

    public static function getModel(): string
    {
        return {$modelName}::class;
    }

    public static function getTitle(): string
    {
        return '{$modelName}管理';
    }

    public function configureForm(FormBuilder \$form): void
    {
        \$form->text('name', '名称', ['required' => true]);
    }

    public function configureTable(DataTable \$table): void
    {
        \$table->column('id', 'ID')
            ->column('name', '名称')
            ->column('created_at', '创建时间')
            ->rowActions(function (\$row, \$rowKey, \$index) {
                return [
                    Navigate::make()
                        ->href(recordEditUrl('{$resourceName}', \$rowKey))
                        ->text('编辑')
                        ->bi('pencil')
                        ->secondary()
                        ->sm(),
                    Button::make()
                        ->label('删除')
                        ->danger()
                        ->sm()
                        ->liveAction('deleteRow')
                        ->data('action-params', json_encode(['rowKey' => \$rowKey]))
                        ->data('confirm', '确定删除此记录？'),
                ];
            });
    }
}

class {$modelName} extends Model
{
    protected string \$table = '{$resourceName}';
    protected array \$fillable = ['name'];
}
PHP;
    }
}
