<?php

declare(strict_types=1);

namespace Framework\Database\Traits;

/**
 * HasTranslations 数据库多语言支持
 *
 * 在数据库字段中存储 JSON 格式的多语言数据，通过 trait 自动处理读写。
 *
 * ## 数据库存储格式
 *
 * 字段值存储为 JSON 对象：
 * ```json
 * {"en": "Hello", "zh": "你好", "ja": "こんにちは"}
 * ```
 *
 * ## 使用方式
 *
 * 1. 在 Model 中 use HasTranslations，声明 $translatable 数组
 * 2. 数据库字段类型需要支持 JSON（TEXT 或 JSON 列）
 * 3. 字段需加入 $casts 为 'json' 或 'array'
 *
 * @example
 * class Product extends Model
 * {
 *     use \Framework\Database\Traits\HasTranslations;
 *
 *     protected array $fillable = ['name', 'description', 'price'];
 *     protected array $casts = ['name' => 'json', 'description' => 'json'];
 *     protected array $translatable = ['name', 'description'];
 * }
 *
 * // 写入
 * $product = Product::find(1);
 * $product->setTranslation('name', 'en', 'Hello');
 * $product->setTranslation('name', 'zh', '你好');
 * $product->save();
 *
 * // 读取（自动使用当前 locale）
 * $product->name;           // → 'Hello' (当 locale = 'en')
 * $product->name;           // → '你好' (当 locale = 'zh')
 *
 * // 指定语言读取
 * $product->getTranslation('name', 'zh');  // → '你好'
 *
 * // 获取所有翻译
 * $product->getTranslations('name');  // → ['en' => 'Hello', 'zh' => '你好']
 *
 * // 批量设置
 * $product->setTranslations('name', ['en' => 'Hello', 'zh' => '你好']);
 */
trait HasTranslations
{
    protected array $translatable = [];

    public function getLocale(): string
    {
        return \Framework\Intl\Translator::getLocale();
    }

    public function getTranslation(string $key, ?string $locale = null, ?string $fallback = null): ?string
    {
        $locale = $locale ?? $this->getLocale();
        $fallback = $fallback ?? config('app.fallback_locale', 'en');

        $translations = $this->getTranslations($key);

        if (isset($translations[$locale]) && $translations[$locale] !== '') {
            return $translations[$locale];
        }

        if (isset($translations[$fallback]) && $translations[$fallback] !== '') {
            return $translations[$fallback];
        }

        $firstValue = reset($translations);
        return $firstValue !== false ? (string)$firstValue : null;
    }

    public function getTranslations(string $key): array
    {
        $value = $this->getAttribute($key);

        if (is_array($value)) {
            return $value;
        }

        if (is_string($value)) {
            $decoded = json_decode($value, true);
            return is_array($decoded) ? $decoded : [];
        }

        return [];
    }

    public function setTranslation(string $key, string $locale, string $value): static
    {
        $translations = $this->getTranslations($key);
        $translations[$locale] = $value;
        $this->setAttribute($key, $translations);

        return $this;
    }

    public function setTranslations(string $key, array $translations): static
    {
        $existing = $this->getTranslations($key);
        $merged = array_merge($existing, $translations);

        foreach ($merged as $locale => $value) {
            if ($value === '' || $value === null) {
                unset($merged[$locale]);
            }
        }

        $this->setAttribute($key, $merged);

        return $this;
    }

    public function forgetTranslation(string $key, string $locale): static
    {
        $translations = $this->getTranslations($key);
        unset($translations[$locale]);
        $this->setAttribute($key, $translations);

        return $this;
    }

    public function forgetTranslations(string $key): static
    {
        $this->setAttribute($key, []);

        return $this;
    }

    public function hasTranslation(string $key, ?string $locale = null): bool
    {
        $locale = $locale ?? $this->getLocale();
        $translations = $this->getTranslations($key);

        return isset($translations[$locale]) && $translations[$locale] !== '';
    }

    public function getTranslatedAttributes(?string $locale = null): array
    {
        $result = [];
        $locale = $locale ?? $this->getLocale();

        foreach ($this->translatable as $key) {
            $result[$key] = $this->getTranslation($key, $locale);
        }

        return $result;
    }

    public function getAllTranslations(): array
    {
        $result = [];

        foreach ($this->translatable as $key) {
            $result[$key] = $this->getTranslations($key);
        }

        return $result;
    }

    public function replaceTranslations(string $key, array $translations): static
    {
        $clean = [];
        foreach ($translations as $locale => $value) {
            if ($value !== '' && $value !== null) {
                $clean[$locale] = $value;
            }
        }

        $this->setAttribute($key, $clean);

        return $this;
    }

    public function getAttribute(string $key): mixed
    {
        if (in_array($key, $this->translatable, true)) {
            $value = parent::getAttribute($key);

            if (is_array($value)) {
                $locale = $this->getLocale();
                if (isset($value[$locale]) && $value[$locale] !== '') {
                    return $value[$locale];
                }

                $fallback = config('app.fallback_locale', 'en');
                if (isset($value[$fallback]) && $value[$fallback] !== '') {
                    return $value[$fallback];
                }

                $first = reset($value);
                return $first !== false ? $first : null;
            }

            return $value;
        }

        return parent::getAttribute($key);
    }

    public function setAttribute(string $key, mixed $value): static
    {
        if (in_array($key, $this->translatable, true) && is_string($value)) {
            $translations = $this->getTranslations($key);
            $translations[$this->getLocale()] = $value;
            $value = $translations;
        }

        return parent::setAttribute($key, $value);
    }

    public function getTranslatable(): array
    {
        return $this->translatable;
    }

    public function isTranslatable(string $key): bool
    {
        return in_array($key, $this->translatable, true);
    }
}
