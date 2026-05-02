<?php

declare(strict_types=1);

namespace Framework\Foundation;

/**
 * 应用运行环境检测
 *
 * 框架支持在多种环境中运行：
 * - **web**: 传统 PHP-FPM / Apache，输出完整 HTML 文档
 * - **cli**: 命令行（Artisan/Console），无 HTTP 栈
 * - **wasm**: PHP-WASM（Tauri 桌面应用），通过 JS Bridge 通信
 *
 * @since 2.0
 */
class AppEnvironment
{
    public const WEB = 'web';
    public const CLI = 'cli';
    public const WASM = 'wasm';

    private static ?string $detected = null;
    private static bool $forceMode = false;

    /**
     * 检测当前运行环境
     *
     * 检测优先级：
     * 1. 强制设置的环境（setEnvironment）
     * 2. APP_ENV 环境变量 / .env 配置
     * 3. PHP_SAPI 自动检测
     * 4. WASM 特征检测（php_wasm 扩展或特殊全局变量）
     */
    public static function detect(): string
    {
        if (self::$detected !== null) {
            return self::$detected;
        }

        // 1. 检查强制设置
        if (self::$forceMode && self::$detected !== null) {
            return self::$detected;
        }

        // 2. 检查配置文件或环境变量
        $env = getenv('APP_ENV') ?: ($_ENV['APP_ENV'] ?? null);
        if ($env && in_array($env, [self::WEB, self::CLI, self::WASM], true)) {
            return self::$detected = $env;
        }

        // 3. PHP_SAPI 检测
        $sapi = PHP_SAPI;

        if ($sapi === 'cli' || $sapi === 'phpdbg') {
            return self::$detected = self::CLI;
        }

        // 4. WASM 特征检测
        if (self::isWasmRuntime()) {
            return self::$detected = self::WASM;
        }

        // 默认为 Web
        return self::$detected = self::WEB;
    }

    /**
     * 检测是否运行在 WASM 环境
     *
     * 检测方法：
     * - php_wasm 扩展存在
     * - 全局变量 __WASM__ 或 WASM_RUNTIME 存在
     * - SAPI 为 'wasm' 或 'embed'
     * - 特殊的内存文件系统特征
     */
    public static function isWasmRuntime(): bool
    {
        // 方法1: 检查 php_wasm 扩展
        if (extension_loaded('wasm') || extension_loaded('php_wasm')) {
            return true;
        }

        // 方法2: 检查特殊全局变量（Wasm 运行时通常会设置）
        if (isset($GLOBALS['__WASM__']) || isset($GLOBALS['WASM_RUNTIME'])) {
            return true;
        }

        // 方法3: 检查 SAPI
        $sapi = strtolower(PHP_SAPI);
        if (in_array($sapi, ['wasm', 'embed', 'webassembly'], true)) {
            return true;
        }

        // 方法4: 检查 Tauri 特征
        if (isset($_SERVER['TAURI']) || isset($_SERVER['HTTP_X_TAURI'])) {
            return true;
        }

        // 方法5: 检查特殊的函数可用性（Wasm 可能缺失某些函数）
        // 注意：这不是 100% 可靠，仅作为辅助判断
        if (!function_exists('posix_getpid') && !function_exists('getmypid')) {
            // 缺失基本进程函数，可能是受限环境
            // 结合其他条件判断
        }

        return false;
    }

    /**
     * 当前是否为 Web 环境
     */
    public static function isWeb(): bool
    {
        return self::detect() === self::WEB;
    }

    /**
     * 当前是否为 CLI 环境
     */
    public static function isCli(): bool
    {
        return self::detect() === self::CLI;
    }

    /**
     * 当前是否为 WASM 环境
     */
    public static function isWasm(): bool
    {
        return self::detect() === self::WASM;
    }

    /**
     * 是否需要完整 HTML 文档输出
     *
     * Web 环境需要完整文档，Wasm/Tauri 只需要内容片段
     */
    public static function requiresFullDocument(): bool
    {
        return !self::isWasm();
    }

    /**
     * 是否支持原生 Session/Cookie
     *
     * Wasm 环境需要使用替代方案（如 localStorage + JS Bridge）
     */
    public static function supportsNativeSession(): bool
    {
        return !self::isWasm();
    }

    /**
     * 是否支持 header() 函数
     *
     * Wasm 环境无法设置 HTTP 头
     */
    public static function supportsHeaders(): bool
    {
        return !self::isWasm();
    }

    /**
     * 强制设置运行环境（用于测试）
     */
    public static function setEnvironment(string $env): void
    {
        if (!in_array($env, [self::WEB, self::CLI, self::WASM], true)) {
            throw new \InvalidArgumentException("Invalid environment: {$env}");
        }
        self::$detected = $env;
        self::$forceMode = true;
    }

    /**
     * 重置检测状态（用于测试）
     */
    public static function reset(): void
    {
        self::$detected = null;
        self::$forceMode = false;
    }

    /**
     * 获取环境信息（用于调试）
     *
     * @return array{environment: string, sapi: string, wasm: bool}
     */
    public static function info(): array
    {
        return [
            'environment' => self::detect(),
            'sapi' => PHP_SAPI,
            'wasm' => self::isWasmRuntime(),
            'php_version' => PHP_VERSION,
            'os' => PHP_OS,
        ];
    }
}
