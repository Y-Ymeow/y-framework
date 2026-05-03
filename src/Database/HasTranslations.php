<?php

declare(strict_types=1);

namespace Framework\Database;

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
 * @db-category Trait
 * @db-since 2.0
 *
 * @example
 * class Product extends Model
 * {
 *     use HasTranslations;
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
    /**
     * 声明哪些字段支持多语言
     *
     * 子类需要覆盖此属性：
     * ```php
     * protected array $translatable = ['name', 'description'];
     * ```
     */
    protected array $translatable = [];

    /**
     * 获取当前语言
     * @return string
     */
    public function getLocale(): string
    {
        return \Framework\Intl\Translator::getLocale();
    }

    /**
     * 获取字段的翻译值
     *
     * @param string $key 字段名
     * @param string|null $locale 语言代码，默认当前语言
     * @param string|null $fallback 回退语言，默认使用 app.fallback_locale
     * @return string|null
     *
     * @db-example $product->getTranslation('name', 'zh')
     */
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

    /**
     * 获取字段的所有翻译
     *
     * @param string $key 字段名
     * @return array ['en' => 'Hello', 'zh' => '你好']
     *
     * @db-example $product->getTranslations('name')
     */
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

    /**
     * 设置字段的某个语言翻译
     *
     * @param string $key 字段名
     * @param string $locale 语言代码
     * @param string $value 翻译值
     * @return static
     *
     * @db-example $product->setTranslation('name', 'en', 'Hello')
     */
    public function setTranslation(string $key, string $locale, string $value): static
    {
        $translations = $this->getTranslations($key);
        $translations[$locale] = $value;
        $this->setAttribute($key, $translations);

        return $this;
    }

    /**
     * 批量设置字段翻译
     *
     * @param string $key 字段名
     * @param array $translations ['en' => 'Hello', 'zh' => '你好']
     * @return static
     *
     * @db-example $product->setTranslations('name', ['en' => 'Hello', 'zh' => '你好'])
     */
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

    /**
     * 删除字段的某个语言翻译
     *
     * @param string $key 字段名
     * @param string $locale 语言代码
     * @return static
     *
     * @db-example $product->forgetTranslation('name', 'ja')
     */
    public function forgetTranslation(string $key, string $locale): static
    {
        $translations = $this->getTranslations($key);
        unset($translations[$locale]);
        $this->setAttribute($key, $translations);

        return $this;
    }

    /**
     * 删除字段的所有翻译
     *
     * @param string $key 字段名
     * @return static
     */
    public function forgetTranslations(string $key): static
    {
        $this->setAttribute($key, []);

        return $this;
    }

    /**
     * 判断字段是否有指定语言的翻译
     *
     * @param string $key 字段名
     * @param string|null $locale 语言代码
     * @return bool
     */
    public function hasTranslation(string $key, ?string $locale = null): bool
    {
        $locale = $locale ?? $this->getLocale();
        $translations = $this->getTranslations($key);

        return isset($translations[$locale]) && $translations[$locale] !== '';
    }

    /**
     * 获取所有可翻译字段及其当前语言的值
     *
     * @param string|null $locale 语言代码
     * @return array ['name' => 'Hello', 'description' => 'A product']
     */
    public function getTranslatedAttributes(?string $locale = null): array
    {
        $result = [];
        $locale = $locale ?? $this->getLocale();

        foreach ($this->translatable as $key) {
            $result[$key] = $this->getTranslation($key, $locale);
        }

        return $result;
    }

    /**
     * 获取所有可翻译字段及其全部翻译
     *
     * @return array ['name' => ['en' => 'Hello', 'zh' => '你好'], ...]
     */
    public function getAllTranslations(): array
    {
        $result = [];

        foreach ($this->translatable as $key) {
            $result[$key] = $this->getTranslations($key);
        }

        return $result;
    }

    /**
     * 替换字段的所有翻译（不合并，直接覆盖）
     *
     * @param string $key 字段名
     * @param array $translations 新的翻译数据
     * @return static
     */
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

    /**
     * 拦截 getAttribute，对 translatable 字段自动返回当前语言的值
     */
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

    /**
     * 拦截 setAttribute，对 translatable 字段自动包装为多语言格式
     *
     * 如果传入的是字符串，自动设置为当前语言的翻译。
     * 如果传入的是数组，直接作为多语言数据存储。
     */
    public function setAttribute(string $key, mixed $value): static
    {
        if (in_array($key, $this->translatable, true) && is_string($value)) {
            $translations = $this->getTranslations($key);
            $translations[$this->getLocale()] = $value;
            $value = $translations;
        }

        return parent::setAttribute($key, $value);
    }

    /**
     * 获取可翻译字段列表
     * @return array
     */
    public function getTranslatable(): array
    {
        return $this->translatable;
    }

    /**
     * 判断字段是否为可翻译字段
     * @param string $key 字段名
     * @return bool
     */
    public function isTranslatable(string $key): bool
    {
        return in_array($key, $this->translatable, true);
    }
}
