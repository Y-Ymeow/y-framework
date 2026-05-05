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
    name: 'make:translation',
    description: 'Create a new translation file for a locale',
)]
class MakeTranslationCommand extends Command
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
            ->addArgument('locale', InputArgument::REQUIRED, 'The locale code (e.g. zh, en, ja)')
            ->addArgument('domain', InputArgument::OPTIONAL, 'The translation domain (e.g. messages, validation)', 'messages');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $locale = $input->getArgument('locale');
        $domain = $input->getArgument('domain');

        $langPath = $this->app->basePath("lang/{$locale}");
        if (!is_dir($langPath)) {
            mkdir($langPath, 0755, true);
        }

        $filePath = "{$langPath}/{$domain}.php";

        if (file_exists($filePath)) {
            $io->error("Translation file [{$locale}/{$domain}] already exists!");
            return Command::FAILURE;
        }

        $content = $this->getStub($locale);
        file_put_contents($filePath, $content);

        $io->success("Translation [{$locale}/{$domain}] created successfully.");
        $io->note("Path: {$filePath}");
        $io->note("Usage: __('{$domain}.key') or <span data-intl=\"{$domain}.key\">default text</span>");

        return Command::SUCCESS;
    }

    private function getStub(string $locale): string
    {
        $sample = match ($locale) {
            'zh' => "'welcome' => '欢迎使用我们的应用程序',\n    'goodbye' => '再见',",
            'ja' => "'welcome' => 'ようこそ',\n    'goodbye' => 'さようなら',",
            default => "'welcome' => 'Welcome',\n    'goodbye' => 'Goodbye',",
        };

        return <<<PHP
<?php

return [
    {$sample}
];
PHP;
    }
}
