<?php

declare(strict_types=1);

namespace Framework\Routing;

use Framework\Http\Request\Request;
use Framework\Http\Response\Response;
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
use Framework\CSS\GradientRules;
use Framework\CSS\UtilityRules;
use Framework\View\Document\CssCollector;

class CssRoute
{
    private string $basePath;
    private string $outputPath;
    private bool $debug;

    private static array $classProviders = [];

    public static function registerClassProvider(callable $provider): void
    {
        self::$classProviders[] = $provider;
    }

    public function __construct(string $basePath, bool $debug = true)
    {
        $this->basePath = $basePath;
        $this->outputPath = $basePath . '/public/assets/css/generated.css';
        $this->debug = $debug;
    }

    public function handle(Request $request): Response
    {
        $css = $this->generate($request);

        return new Response($css, 200, [
            'Content-Type' => 'text/css; charset=utf-8',
            'Cache-Control' => $this->debug
                ? 'no-cache, no-store, must-revalidate'
                : 'public, max-age=31536000',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    private function generate(Request $request): string
    {
        if (!$this->debug && is_file($this->outputPath)) {
            $css = file_get_contents($this->outputPath);
        } else {
            $classes = $this->scanForUsedClasses();
            $css = $this->buildCss($classes);

            if (!$this->debug) {
                $dir = dirname($this->outputPath);
                if (!is_dir($dir)) {
                    mkdir($dir, 0755, true);
                }
                file_put_contents($this->outputPath, $css);
            }
        }

        $collector = CssCollector::getInstance();

        $snippetsParam = $request->query('snippets');
        if ($snippetsParam) {
            $ids = explode(',', $snippetsParam);
            foreach ($ids as $id) {
                $id = trim($id);
                if ($id) {
                    $collector->getSnippet($id);
                }
            }
        }

        if (!app()->isDebug()) {
            $collector->loadFromCache($snippetsParam);
        }

        $snippets = $collector->collect();
        if (!empty($snippets)) {
            $css .= "\n/* === Component CSS Snippets === */\n\n";
            $css .= $snippets;
        }

        return $css;
    }

    private function scanForUsedClasses(): array
    {
        $classes = [];
        $scanDirs = $this->getScanDirs();

        foreach ($scanDirs as $scanDir) {
            $files = $this->findPhpFiles($scanDir);
            foreach ($files as $file) {
                $content = file_get_contents($file);
                $this->extractAllClassesFromString($content, $classes);
            }
        }

        $alwaysGenerated = config('css.always_generated', CSSEngine::$alwaysGenerated);
        foreach ($alwaysGenerated as $cls) {
            $classes[$cls] = true;
        }

        foreach (self::$classProviders as $provider) {
            try {
                $extraClasses = $provider();
                if (is_array($extraClasses)) {
                    foreach ($extraClasses as $cls) {
                        if (is_string($cls) && !empty($cls)) {
                            $classes[$cls] = true;
                        }
                    }
                }
            } catch (\Throwable $e) {
                // skip provider errors during CSS generation
            }
        }

        return $classes;
    }

    private function getScanDirs(): array
    {
        $configuredDirs = config('css.scan_dirs', []);
        $dirs = [];

        foreach ($configuredDirs as $dir) {
            $dirs[] = $this->basePath . '/' . $dir;
        }

        return $dirs;
    }

    private function extractAllClassesFromString(string $content, array &$classes): void
    {
        preg_match_all('/[\'"]([^\'"]*)[\'"]/', $content, $stringMatches);

        foreach ($stringMatches[1] as $string) {
            preg_match_all('/[a-zA-Z][a-zA-Z0-9_:.!-]{1,59}/', $string, $tokenMatches);

            foreach ($tokenMatches[0] as $token) {
                if (!str_contains($token, '-') && !str_contains($token, ':')) {
                    continue;
                }

                if (preg_match('/^(?:namespace|use|class|function|return|static|public|private|protected|new|extends|implements|interface|abstract|final|const|var|require|include|echo|print|throw|try|catch|finally|if|else|elseif|for|foreach|while|do|switch|case|break|continue|default|array|true|false|null|self|parent|void|string|int|float|bool|mixed|object|iterable|callable|never)$/', $token)) {
                    continue;
                }

                $classes[$token] = true;
            }
        }
    }

    /**
     * Recursively find all .php files in a directory.
     * PHP's glob() does NOT support ** for recursive matching,
     * so we must implement our own recursive scanner.
     */
    private function findPhpFiles(string $dir): array
    {
        $files = [];
        if (!is_dir($dir)) {
            return $files;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($iterator as $file) {
            if ($file->isFile() && $file->getExtension() === 'php') {
                $files[] = $file->getPathname();
            }
        }

        return $files;
    }

    private function buildCss(array $classes): string
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
            GradientRules::class,
            UtilityRules::class,
        ];

        foreach ($classes as $class => $_) {
            $parsed = CSSEngine::parseClass($class);

            if ($parsed['base'] !== $class && str_starts_with($parsed['base'], 'animate-')) {
                $animationClasses[$parsed['base']] = true;
                continue;
            }

            $parsedMatch = false;
            foreach ($parsers as $parser) {
                if ($result = $parser::parse($parsed['base'], $parsed['pseudo'], $class)) {
                    if ($parsed['important']) {
                        $result = str_replace(';', ' !important;', $result);
                    }
                    $selector = CSSEngine::buildSelector($class, $parsed['pseudo']);
                    $rule = CSSEngine::buildRule($selector, $result, $parsed['media']);
                    $rules[] = $rule;
                    $parsedMatch = true;
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

        $css[] = implode("", $rules);

        return implode("", $css);
    }
}
