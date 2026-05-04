<?php

declare(strict_types=1);

namespace Framework\View;

use Framework\View\Base\Element;

class FragmentRegistry
{
    private static ?self $instance = null;
    private array $fragments = [];
    private array $targets = [];

    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public static function setInstance(self $instance): void
    {
        self::$instance = $instance;
    }

    public function setTargets(array $targets): void
    {
        foreach ($targets as $name => $mode) {
            if (is_int($name)) {
                $this->targets[$mode] = 'replace';
            } else {
                $this->targets[$name] = $mode;
            }
        }
    }

    public function record(string $name, Element $element): void
    {
        if (isset($this->targets[$name])) {
            $this->fragments[$name] = [
                'element' => $element,
                'mode' => $this->targets[$name],
            ];
        }
    }

    public function has(string $name): bool
    {
        return isset($this->fragments[$name]);
    }

    public function getFragment(string $name): ?array
    {
        return $this->fragments[$name] ?? null;
    }

    public function getFragments(): array
    {
        return $this->fragments;
    }

    public function reset(): void
    {
        $this->fragments = [];
        $this->targets = [];
    }

    public static function resetInstance(): void
    {
        self::$instance = null;
    }

    public static function staticSetTargets(array $targets): void
    {
        self::getInstance()->setTargets($targets);
    }

    public static function staticRecord(string $name, Element $element): void
    {
        self::getInstance()->record($name, $element);
    }

    public static function staticGetFragments(): array
    {
        return self::getInstance()->getFragments();
    }

    public static function staticReset(): void
    {
        self::getInstance()->reset();
    }
}
