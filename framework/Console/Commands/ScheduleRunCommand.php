<?php

declare(strict_types=1);

namespace Framework\Console\Commands;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Framework\Foundation\Application;
use Framework\Scheduler\Scheduler;

#[AsCommand(
    name: 'schedule:run',
    description: 'Run scheduled tasks that are due',
)]
class ScheduleRunCommand extends Command
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

        if (!$this->app->isBooted()) {
            $this->app->bootstrapProviders();
        }

        $scheduler = $this->app->make(Scheduler::class);
        $io->title('Running Scheduled Tasks');

        $due = $scheduler->due();
        $count = count($due);

        if ($count === 0) {
            $io->info('No tasks due.');
            return Command::SUCCESS;
        }

        $io->text("{$count} task(s) due.");

        $completed = 0;
        $failed = 0;

        foreach ($due as $event) {
            $io->text("Running: " . get_class($event));
            try {
                $event->run();
                $io->text("<info>✓ Completed</info>");
                $completed++;
            } catch (\Throwable $e) {
                $io->text("<error>✗ Failed:</error> " . $e->getMessage());
                $failed++;
            }
        }

        $io->newLine();
        $io->success("{$completed} completed, {$failed} failed.");

        return $failed > 0 ? Command::FAILURE : Command::SUCCESS;
    }
}
