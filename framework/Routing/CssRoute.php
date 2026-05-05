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

    public function __construct(string $basePath, bool $debug = true)
    {
        $this->basePath = $basePath;
        $this->outputPath = $basePath . '/public/assets/css/generated.css';
        $this->debug = $debug;
    }

    public function handle(Request $request): Response
    {
        $css = $this->generate();

        return new Response($css, 200, [
            'Content-Type' => 'text/css; charset=utf-8',
            'Cache-Control' => $this->debug
                ? 'no-cache, no-store, must-revalidate'
                : 'public, max-age=31536000',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    private function generate(): string
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

        $snippets = CssCollector::getInstance()->collect();
        if (!empty($snippets)) {
            $css .= "\n/* === Component CSS Snippets === */\n\n";
            $css .= $snippets;
        }

        return $css;
    }

    private function scanForUsedClasses(): array
    {
        $classes = [];
        $scanDirs = [
            __DIR__ . '/../../src/Admin',
            __DIR__ . '/../../src/UX',
            $this->basePath . '/admin',
            $this->basePath . '/app',
        ];

        foreach ($scanDirs as $scanDir) {
            $files = $this->findPhpFiles($scanDir);
            foreach ($files as $file) {
                $content = file_get_contents($file);

                // Match ->class(...) calls — both single and multi-argument forms
                // e.g. ->class('a b', 'c') or ->class("a", "b", "c")
                if (preg_match_all('/->class\s*\(([^)]+)\)/s', $content, $matches)) {
                    foreach ($matches[1] as $argsString) {
                        if (preg_match_all('/[\'"]([^\'"]*)[\'"]/', $argsString, $argMatches)) {
                            foreach ($argMatches[1] as $classString) {
                                $parts = preg_split('/\s+/', $classString);
                                foreach ($parts as $part) {
                                    $part = trim($part);
                                    if ($part && !str_starts_with($part, '$') && !str_contains($part, '{{')) {
                                        $classes[$part] = true;
                                    }
                                }
                            }
                        }
                    }
                }

                if (preg_match_all('/class\s*=\s*["\']((?:[^"\']|\[[^\]]*\])+)["\']/', $content, $matches)) {
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

                if (preg_match_all('/\[\'classes\'\]\s*=>\s*\[([^\]]+)\]/s', $content, $matches)) {
                    foreach ($matches[1] as $classesList) {
                        if (preg_match_all('/[\'"]([^\'"]+)[\'"]/', $classesList, $classMatches)) {
                            foreach ($classMatches[1] as $classString) {
                                $parts = preg_split('/\s+/', $classString);
                                foreach ($parts as $part) {
                                    $part = trim($part);
                                    if ($part && !str_starts_with($part, '$') && !str_contains($part, '{{')) {
                                        $classes[$part] = true;
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        foreach (CSSEngine::$alwaysGenerated as $cls) {
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

        $depth = 0;
        $current = '';
        $length = strlen($expr);

        for ($i = 0; $i < $length; $i++) {
            $char = $expr[$i];

            if ($char === '[') {
                if ($depth > 0) {
                    $current .= $char;
                }
                $depth++;
            } elseif ($char === ']') {
                $depth--;
                if ($depth > 0) {
                    $current .= $char;
                } else {
                    if (preg_match_all("/'([^']+)'/", $current, $classMatches)) {
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
                    $current = '';
                }
            } else {
                if ($depth > 0) {
                    $current .= $char;
                }
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
