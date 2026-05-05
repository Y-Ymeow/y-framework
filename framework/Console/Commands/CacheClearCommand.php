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
    name: 'cache:clear',
    description: 'Clear application cache (route, config, view, live, or all)',
)]
class CacheClearCommand extends Command
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
            ->addArgument('type', InputArgument::OPTIONAL, 'Cache type to clear: route, config, view, all', 'all');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $type = $input->getArgument('type');
        $cachePath = paths()->cache();

        $cleared = 0;

        $targets = match ($type) {
            'route' => ['routes.php'],
            'config' => ['config.php'],
            'view' => ['views'],
            'all' => null,
            default => null,
        };

        if ($type === 'all') {
            $cleared = $this->clearDirectory($cachePath, $io);
        } elseif ($targets !== null) {
            foreach ($targets as $target) {
                $path = $cachePath . '/' . $target;
                if (is_file($path)) {
                    unlink($path);
                    $io->text("Deleted: {$target}");
                    $cleared++;
                } elseif (is_dir($path)) {
                    $cleared += $this->clearDirectory($path, $io);
                    $io->text("Cleared directory: {$target}");
                } else {
                    $io->text("Not found: {$target} (skipped)");
                }
            }
        } else {
            $io->error("Unknown cache type: {$type}. Valid types: route, config, view, all");
            return Command::FAILURE;
        }

        if ($cleared > 0) {
            $io->success("Cache cleared ({$cleared} item(s) removed).");
        } else {
            $io->info('No cache files found to clear.');
        }

        return Command::SUCCESS;
    }

    private function clearDirectory(string $dir, SymfonyStyle $io): int
    {
        if (!is_dir($dir)) {
            return 0;
        }

        $count = 0;
        $items = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($items as $item) {
            if ($item->isFile()) {
                unlink($item->getRealPath());
                $count++;
            } elseif ($item->isDir()) {
                @rmdir($item->getRealPath());
            }
        }

        return $count;
    }
}
