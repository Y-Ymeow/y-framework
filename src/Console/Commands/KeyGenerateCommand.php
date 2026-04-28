<?php

declare(strict_types=1);

namespace Framework\Console\Commands;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Framework\Foundation\Application;

#[AsCommand(
    name: 'key:generate',
    description: 'Set the application key in .env',
)]
class KeyGenerateCommand extends Command
{
    private Application $app;

    public function __construct(Application $app)
    {
        parent::__construct();
        $this->app = $app;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $envPath = $this->app->basePath('.env');
        $key = bin2hex(random_bytes(32));

        if (!file_exists($envPath)) {
            file_put_contents($envPath, "APP_KEY={$key}\n");
        } else {
            $content = file_get_contents($envPath);
            if (preg_match('/^APP_KEY=/m', $content)) {
                $content = preg_replace('/^APP_KEY=.*$/m', "APP_KEY={$key}", $content);
            } else {
                $content .= "\nAPP_KEY={$key}\n";
            }
            file_put_contents($envPath, $content);
        }

        $io->success('Application key generated successfully.');
        return Command::SUCCESS;
    }
}
