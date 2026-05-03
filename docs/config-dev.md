# 配置管理 — 开发文档

> 由 DocGen 自动生成于 2026-05-03 15:49:15

## 组件清单

| 名称 | 命名空间 | 文件 | 类型 |
|---|---|---|---|
| `ConfigManager` | `Framework\Config` | `php/src/Config/ConfigManager.php` | class |

---

## 详细实现

### `Framework\Config\ConfigManager`

- **文件:** `php/src/Config/ConfigManager.php`

**公开方法 (7)：**

- `load(): array` — 加载配置：先加载框架默认配置，再用用户项目 config/ 覆盖
- `set(string $key, mixed $value): void` — 设置配置值并持久化
- `get(string $key, mixed $default = null): mixed` — 获取配置值（支持点号分隔的键）
- `validate(array $rules): array` — 验证必需配置项
- `disableCache(): void` — 禁用配置缓存
- `clearCache(): void` — 清除配置缓存
- `reset(): void` — 重置缓存（测试用）

