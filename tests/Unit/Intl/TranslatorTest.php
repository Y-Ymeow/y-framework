<?php

declare(strict_types=1);

namespace Tests\Unit\Intl;

use Framework\Intl\Translator;

class TranslatorTest extends \PHPUnit\Framework\TestCase
{
    private static string $tempDir;

    public static function setUpBeforeClass(): void
    {
        self::$tempDir = sys_get_temp_dir() . '/lang_test_' . uniqid();
        mkdir(self::$tempDir . '/en', 0755, true);
        mkdir(self::$tempDir . '/zh', 0755, true);

        file_put_contents(self::$tempDir . '/en/messages.php', '<?php return [
            "welcome" => "Welcome!",
            "greeting" => "Hello, :name!",
            "items" => "item|item|items",
            "nested" => [
                "key" => "Nested Value",
            ],
        ];');

        file_put_contents(self::$tempDir . '/zh/messages.php', '<?php return [
            "welcome" => "欢迎！",
            "greeting" => "你好，:name！",
            "items" => "项",
            "nested" => [
                "key" => "嵌套值",
            ],
        ];');

        Translator::init(self::$tempDir, 'en', 'en');
    }

    public static function tearDownAfterClass(): void
    {
        array_map('unlink', glob(self::$tempDir . '/en/*.php'));
        array_map('unlink', glob(self::$tempDir . '/zh/*.php'));
        rmdir(self::$tempDir . '/en');
        rmdir(self::$tempDir . '/zh');
        rmdir(self::$tempDir);
    }

    public function test_basic_translation(): void
    {
        $this->assertEquals('Welcome!', Translator::get('messages.welcome'));
    }

    public function test_parameter_replacement(): void
    {
        $this->assertEquals('Hello, John!', Translator::get('messages.greeting', ['name' => 'John']));
    }

    public function test_nested_translation(): void
    {
        $this->assertEquals('Nested Value', Translator::get('messages.nested.key'));
    }

    public function test_fallback_to_key(): void
    {
        $this->assertEquals('messages.nonexistent', Translator::get('messages.nonexistent'));
    }

    public function test_change_locale(): void
    {
        Translator::setLocale('zh');
        $this->assertEquals('欢迎！', Translator::get('messages.welcome'));
        Translator::setLocale('en');
    }

    public function test_choice_singular(): void
    {
        $this->assertEquals('item', Translator::choice('messages.items', 1));
    }

    public function test_choice_plural(): void
    {
        $this->assertEquals('items', Translator::choice('messages.items', 5));
    }

    public function test_has(): void
    {
        $this->assertTrue(Translator::has('messages.welcome'));
        $this->assertFalse(Translator::has('messages.nonexistent'));
    }

    public function test_all(): void
    {
        $all = Translator::all('en');
        $this->assertArrayHasKey('messages', $all);
        $this->assertEquals('Welcome!', $all['messages']['welcome']);
    }
}
