<?php

declare(strict_types=1);

namespace Framework\Console\Commands;

use Framework\CSS\CSSReset;
use Framework\CSS\CSSEngine;
use Framework\CSS\LayoutRules;
use Framework\CSS\SpacingRules;
use Framework\CSS\TypographyRules;
use Framework\CSS\ColorRules;
use Framework\CSS\BorderRules;
use Framework\CSS\EffectRules;
use Framework\CSS\InteractionRules;
use Framework\CSS\TransitionRules;
use Framework\CSS\TransformRules;
use Framework\CSS\AnimationRules;
use Framework\Foundation\Application;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'css:generate',
    description: 'Scan PHP files and generate CSS for used classes',
)]
class CssGenerateCommand extends Command
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
            ->addOption('output', 'o', InputOption::VALUE_OPTIONAL, 'Output file', 'public/assets/css/generated.css')
            ->addOption('minify', null, InputOption::VALUE_NONE, 'Minify output');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $basePath = $this->app->basePath();

        $io->title('CSS Generator');

        $usedClasses = $this->scanForUsedClasses($basePath);
        $io->info(sprintf('Found %d unique classes', count($usedClasses)));

        $this->generate($basePath, $usedClasses, $input, $io);

        return Command::SUCCESS;
    }

    private function scanForUsedClasses(string $basePath): array
    {
        $classes = [];
        $patterns = [
            $basePath . '/src/**/*.php',
            $basePath . '/admin/**/*.php',
            $basePath . '/app/**/*.php',
        ];

        foreach ($patterns as $pattern) {
            foreach (glob($pattern) as $file) {
                $content = file_get_contents($file);

                if (preg_match_all('/->class\s*\(\s*[\'"]([^\'"]+)[\'"]\s*\)/', $content, $matches)) {
                    foreach ($matches[1] as $classString) {
                        $parts = preg_split('/\s+/', $classString);
                        foreach ($parts as $part) {
                            $part = trim($part);
                            if ($part && !str_starts_with($part, '$') && !str_contains($part, '{{')) {
                                $classes[$part] = true;
                            }
                        }
                    }
                }

                if (preg_match_all('/class\s*=\s*["\']([^"\']+)["\']/', $content, $matches)) {
                    foreach ($matches[1] as $classString) {
                        $parts = preg_split('/\s+/', $classString);
                        foreach ($parts as $part) {
                            $part = trim($part);
                            if ($part && !str_starts_with($part, '$') && !str_contains($part, '{{')) {
                                $classes[$part] = true;
                            }
                        }
                    }
                }

                if (preg_match_all('/->dataClass\s*\(\s*"((?:[^"\\\\]|\\\\.)*)"\s*\)/s', $content, $matches)) {
                    foreach ($matches[1] as $expr) {
                        $this->extractClassesFromExpr($expr, $classes);
                    }
                }

                if (preg_match_all('/->bindAttr\s*\(\s*"class"\s*,\s*"((?:[^"\\\\]|\\\\.)*)"\s*\)/s', $content, $matches)) {
                    foreach ($matches[1] as $expr) {
                        $this->extractClassesFromExpr($expr, $classes);
                    }
                }
            }
        }

        $alwaysGenerated = [
            'opacity-0', 'opacity-100', 'scale-90', 'scale-95', 'scale-100',
            'translate-y-full', 'translate-x-full', 'translate-y-0', 'translate-x-0',
        ];
        foreach ($alwaysGenerated as $cls) {
            $classes[$cls] = true;
        }

        return $classes;
    }

    private function extractClassesFromExpr(string $expr, array &$classes): void
    {
        if (preg_match_all("/'([^']+)'\s*:/", $expr, $matches)) {
            foreach ($matches[1] as $className) {
                $parts = preg_split('/\s+/', $className);
                foreach ($parts as $part) {
                    $part = trim($part);
                    if ($part && !str_starts_with($part, '$')) {
                        $classes[$part] = true;
                    }
                }
            }
        }

        if (preg_match_all('/\[([^\]]+)\]/', $expr, $matches)) {
            foreach ($matches[1] as $arrContent) {
                if (preg_match_all("/'([^']+)'/", $arrContent, $classMatches)) {
                    foreach ($classMatches[1] as $className) {
                        $parts = preg_split('/\s+/', $className);
                        foreach ($parts as $part) {
                            $part = trim($part);
                            if ($part && !str_starts_with($part, '$')) {
                                $classes[$part] = true;
                            }
                        }
                    }
                }
            }
        }
    }

    private function generate(string $basePath, array $classes, InputInterface $input, SymfonyStyle $io): void
    {
        $css = [];

        $css[] = CSSReset::build();

        $rules = [];
        $animationClasses = [];

        $parsers = [
            LayoutRules::class,
            SpacingRules::class,
            TypographyRules::class,
            ColorRules::class,
            BorderRules::class,
            EffectRules::class,
            InteractionRules::class,
            TransitionRules::class,
            TransformRules::class,
            AnimationRules::class,
        ];

        foreach ($classes as $class => $_) {
            $parsed = CSSEngine::parseClass($class);

            if ($parsed['base'] !== $class && str_starts_with($parsed['base'], 'animate-')) {
                $animationClasses[$parsed['base']] = true;
                continue;
            }

            foreach ($parsers as $parser) {
                if ($result = $parser::parse($parsed['base'])) {
                    if ($parsed['important']) {
                        $result = str_replace(';', ' !important;', $result);
                    }
                    $selector = CSSEngine::buildSelector($class, $parsed['pseudo']);
                    $rule = CSSEngine::buildRule($selector, $result, $parsed['media']);
                    $rules[] = $rule;
                    break;
                }
            }
        }

        if (!empty($animationClasses)) {
            $keyframes = AnimationRules::buildAllKeyframes(array_keys($animationClasses));
            if ($keyframes) {
                $rules[] = $keyframes;
            }
        }

        $css[] = implode("\n", $rules);

        $cssContent = implode("\n\n", $css);

        if ($input->getOption('minify')) {
            $cssContent = $this->minify($cssContent);
        }

        $outputPath = $basePath . '/' . $input->getOption('output');
        $dir = dirname($outputPath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        file_put_contents($outputPath, $cssContent);
        $io->success(sprintf('Generated %d bytes → %s', strlen($cssContent), $outputPath));
    }

    private function minify(string $css): string
    {
        $css = preg_replace('/\/\*.*?\*\//s', '', $css);
        $css = preg_replace('/\s+/', ' ', $css);
        $css = preg_replace('/\s*([{:;,}])\s*/', '$1', $css);
        $css = preg_replace('/;}/', '}', $css);
        return trim($css);
    }
}
