<?php

declare(strict_types=1);

namespace Framework\Support;

class VarDumper
{
    private static bool $headerRendered = false;

    public static function dump(mixed ...$vars): void
    {
        echo '<div class="yf-dump-container">';
        echo '<div class="yf-dump-label">Debug Output</div>';

        if (!self::$headerRendered) {
            self::renderHeader();
            self::$headerRendered = true;
        }

        foreach ($vars as $i => $var) {
            if ($i > 0) {
                echo '<hr class="yf-dump-divider">';
            }
            self::renderVar($var);
            echo "\n";
        }

        echo '</div>';
    }

    private static function renderHeader(): void
    {
        echo <<<'HTML'
<style>
.yf-dump-container {
    font-family: 'JetBrains Mono', 'Fira Code', 'SF Mono', Menlo, Monaco, Consolas, monospace;
    font-size: 13px;
    line-height: 1.6;
    background: #1e1e2e;
    color: #cdd6f4;
    padding: 16px;
    margin: 12px 0;
    border-radius: 8px;
    border: 1px solid #313244;
    overflow-x: auto;
}
.yf-dump-container * {
    box-sizing: border-box;
    margin: 0;
    padding: 0;
}
.yf-dump-type {
    color: #89b4fa;
    font-weight: 600;
}
.yf-dump-string {
    color: #a6e3a1;
}
.yf-dump-number {
    color: #fab387;
}
.yf-dump-bool {
    color: #cba6f7;
}
.yf-dump-null {
    color: #6c7086;
    font-style: italic;
}
.yf-dump-key {
    color: #89dceb;
}
.yf-dump-toggle {
    cursor: pointer;
    user-select: none;
    display: inline-flex;
    align-items: center;
    gap: 4px;
    color: #89b4fa;
}
.yf-dump-toggle:hover {
    color: #b4befe;
}
.yf-dump-toggle .arrow {
    display: inline-block;
    transition: transform 0.15s ease;
    font-size: 10px;
}
.yf-dump-toggle.collapsed .arrow {
    transform: rotate(-90deg);
}
.yf-dump-children {
    padding-left: 24px;
    border-left: 1px solid #313244;
    margin-left: 6px;
}
.yf-dump-children.hidden {
    display: none;
}
.yf-dump-prop {
    margin: 2px 0;
}
.yf-dump-separator {
    color: #585b70;
}
.yf-dump-count {
    color: #585b70;
    font-size: 11px;
}
.yf-dump-object-name {
    color: #f9e2af;
}
.yf-dump-divider {
    border: none;
    border-top: 1px solid #313244;
    margin: 8px 0;
}
.yf-dump-label {
    color: #585b70;
    font-size: 11px;
    margin-bottom: 4px;
}
</style>
<script>
(function(){
    document.addEventListener('click', function(e){
        var toggle = e.target.closest('.yf-dump-toggle');
        if(toggle){
            toggle.classList.toggle('collapsed');
            var children = toggle.nextElementSibling;
            if(children && children.classList.contains('yf-dump-children')){
                children.classList.toggle('hidden');
            }
        }
    });
})();
</script>
HTML;
    }

    private static function renderVar(mixed $var, int $depth = 0): void
    {
        if (is_array($var)) {
            self::renderArray($var, $depth);
        } elseif (is_object($var)) {
            self::renderObject($var, $depth);
        } elseif (is_string($var)) {
            echo '<span class="yf-dump-type">string</span><span class="yf-dump-separator">(</span><span class="yf-dump-number">' . strlen($var) . '</span><span class="yf-dump-separator">)</span> ';
            echo '<span class="yf-dump-string">' . htmlspecialchars($var) . '</span>';
        } elseif (is_int($var) || is_float($var)) {
            echo '<span class="yf-dump-type">' . gettype($var) . '</span> ';
            echo '<span class="yf-dump-number">' . $var . '</span>';
        } elseif (is_bool($var)) {
            echo '<span class="yf-dump-type">bool</span> ';
            echo '<span class="yf-dump-bool">' . ($var ? 'true' : 'false') . '</span>';
        } elseif ($var === null) {
            echo '<span class="yf-dump-null">null</span>';
        } elseif (is_resource($var)) {
            echo '<span class="yf-dump-type">resource</span> ';
            echo '<span class="yf-dump-number">' . get_resource_type($var) . '</span>';
        } else {
            echo '<span class="yf-dump-null">unknown</span>';
        }
    }

    private static function renderArray(array $array, int $depth): void
    {
        $count = count($array);
        $id = 'yf-' . uniqid();

        if ($count === 0) {
            echo '<span class="yf-dump-type">array</span><span class="yf-dump-separator">(</span><span class="yf-dump-number">0</span><span class="yf-dump-separator">)</span> ';
            echo '<span class="yf-dump-separator">[]</span>';
            return;
        }

        echo '<span class="yf-dump-toggle"><span class="arrow">▼</span><span class="yf-dump-type">array</span><span class="yf-dump-separator">(</span><span class="yf-dump-number">' . $count . '</span><span class="yf-dump-separator">)</span></span>';
        echo '<div class="yf-dump-children">';

        foreach ($array as $key => $value) {
            echo '<div class="yf-dump-prop">';
            echo '<span class="yf-dump-key">' . $key . '</span>';
            echo '<span class="yf-dump-separator"> => </span>';
            self::renderVar($value, $depth + 1);
            echo '</div>';
        }

        echo '</div>';
    }

    private static function renderObject(object $obj, int $depth): void
    {
        $class = get_class($obj);
        $id = 'yf-' . uniqid();

        $props = [];
        $reflection = new \ReflectionClass($obj);

        foreach ($reflection->getProperties() as $prop) {
            try {
                $prop->setAccessible(true);
                $value = $prop->getValue($obj);
                $props[$prop->getName()] = $value;
            } catch (\Throwable $e) {
                // skip
            }
        }

        $count = count($props);

        if ($count === 0) {
            echo '<span class="yf-dump-type">object</span> ';
            echo '<span class="yf-dump-object-name">' . htmlspecialchars($class) . '</span>';
            echo '<span class="yf-dump-separator"> {}</span>';
            return;
        }

        echo '<span class="yf-dump-toggle"><span class="arrow">▼</span><span class="yf-dump-type">object</span> ';
        echo '<span class="yf-dump-object-name">' . htmlspecialchars($class) . '</span>';
        echo '<span class="yf-dump-separator">(</span><span class="yf-dump-count">' . $count . ' props</span><span class="yf-dump-separator">)</span></span>';

        echo '<div class="yf-dump-children">';

        foreach ($props as $key => $value) {
            echo '<div class="yf-dump-prop">';
            echo '<span class="yf-dump-key">+' . htmlspecialchars($key) . '</span>';
            echo '<span class="yf-dump-separator"> => </span>';
            self::renderVar($value, $depth + 1);
            echo '</div>';
        }

        echo '</div>';
    }
}
