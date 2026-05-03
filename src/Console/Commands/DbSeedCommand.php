<?php

declare(strict_types=1);

namespace Framework\Console\Commands;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Framework\Foundation\Application;
use Framework\Database\Connection;

#[AsCommand(
    name: 'db:seed',
    description: 'Run database seeders',
)]
class DbSeedCommand extends Command
{
    private Application $app;

    public function __construct(Application $app)
    {
        parent::__construct();
        $this->app = $app;
    }

    protected function configure(): void
    {
        $this
            ->addArgument('class', InputArgument::OPTIONAL, 'The seeder class to run (default: DatabaseSeeder)')
            ->addOption('database', null, InputOption::VALUE_OPTIONAL, 'The database connection to use');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Database Seeding');

        if (!$this->app->isBooted()) {
            $this->app->bootstrapProviders();
        }

        $class = $input->getArgument('class') ?: 'DatabaseSeeder';
        $seedersPath = $this->app->basePath('database/seeders');

        if (!is_dir($seedersPath)) {
            $io->warning('No seeders directory found. Run make:seed first.');
            return Command::FAILURE;
        }

        $connection = Connection::get($input->getOption('database'));

        if ($class === 'DatabaseSeeder') {
            $filePath = $seedersPath . '/DatabaseSeeder.php';
            if (!file_exists($filePath)) {
                $this->createDatabaseSeeder($seedersPath, $io);
            }
        }

        $classes = $this->resolveSeederClasses($class, $seedersPath, $io);
        if (empty($classes)) {
            $io->error("Seeder [{$class}] not found.");
            return Command::FAILURE;
        }

        foreach ($classes as $seederClass) {
            $io->text("Running: <info>{$seederClass}</info>");

            require_once $this->resolveSeederFile($seederClass, $seedersPath);

            $seeder = new $seederClass();
            $seeder->run($connection);

            $io->text("<info>Seeded:</info> {$seederClass}");
        }

        $io->newLine();
        $io->success(count($classes) . ' seeder(s) completed.');

        return Command::SUCCESS;
    }

    private function resolveSeederClasses(string $class, string $path, SymfonyStyle $io): array
    {
        if ($class === 'DatabaseSeeder') {
            $filePath = $path . '/DatabaseSeeder.php';
            if (file_exists($filePath)) {
                return ['Database\Seeders\DatabaseSeeder'];
            }
        }

        $fullClass = str_starts_with($class, 'Database\\Seeders\\') ? $class : 'Database\\Seeders\\' . $class;
        $shortName = str_replace('Database\\Seeders\\', '', $fullClass);
        $filePath = $path . '/' . str_replace('\\', '/', $shortName) . '.php';

        if (file_exists($filePath)) {
            return [$fullClass];
        }

        $io->warning("Seeder class [{$fullClass}] file not found at: {$filePath}");
        return [];
    }

    private function resolveSeederFile(string $class, string $path): string
    {
        $shortName = str_replace('Database\\Seeders\\', '', $class);
        return $path . '/' . str_replace('\\', '/', $shortName) . '.php';
    }

    private function createDatabaseSeeder(string $path, SymfonyStyle $io): void
    {
        $content = <<<'PHP'
<?php

declare(strict_types=1);

namespace Database\Seeders;

use Framework\Database\Connection;

class DatabaseSeeder
{
    public function run(Connection $db): void
    {
        // 在此注册需要运行的 Seeder
        // (new UserSeeder())->run($db);
    }
}
PHP;

        file_put_contents($path . '/DatabaseSeeder.php', $content);
        $io->note('Created default DatabaseSeeder.');
    }
}
