<?php

declare(strict_types=1);

namespace Framework\Console\Commands;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Framework\Foundation\Application;
use Framework\Queue\QueueManager;

#[AsCommand(
    name: 'queue:work',
    description: 'Start processing jobs on the queue',
)]
class QueueWorkCommand extends Command
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
            ->addOption('queue', null, InputOption::VALUE_OPTIONAL, 'The queue to listen on', 'default')
            ->addOption('tries', null, InputOption::VALUE_OPTIONAL, 'Max attempts per job', 3)
            ->addOption('delay', null, InputOption::VALUE_OPTIONAL, 'Seconds to wait between polls', 1);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        if (!$this->app->isBooted()) {
            $this->app->bootstrapProviders();
        }

        $queue = $input->getOption('queue');
        $delay = (int) $input->getOption('delay');

        $io->title('Queue Worker');
        $io->text("Queue: <info>{$queue}</info>");
        $io->text('Press Ctrl+C to stop.');
        $io->newLine();

        while (true) {
            try {
                $job = QueueManager::driver()->pop($queue);

                if ($job === null) {
                    sleep($delay);
                    continue;
                }

                $io->text("[" . date('H:i:s') . "] Processing: {$job->id} ({$job->jobClass})");

                $job->handle();

                $io->text("<info>✓ Completed:</info> {$job->id}");
            } catch (\Throwable $e) {
                $io->text("<error>✗ Failed:</error> {$e->getMessage()}");
            }
        }

        return Command::SUCCESS;
    }
}
