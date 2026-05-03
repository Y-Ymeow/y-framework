# 配置管理 — API 参考

> 由 DocGen 自动生成于 2026-05-03 15:49:15

## 目录

**其他**
- [`ConfigManager`](#framework-config-configmanager) — 配置管理器

---

### 其他

<a name="framework-config-configmanager"></a>
#### `Framework\Config\ConfigManager`

配置管理器

**文件:** `php/src/Config/ConfigManager.php`

**方法：**

| 方法 | 说明 | 参数 |
|---|---|---|
| `load` | 加载配置：先加载框架默认配置，再用用户项目 config/ 覆盖 | — |
| `set` | 设置配置值并持久化 | `string $key`, `mixed $value` |
| `get` | 获取配置值（支持点号分隔的键） | `string $key`, `mixed $default` = null |
| `validate` | 验证必需配置项 | `array $rules` |
| `disableCache` | 禁用配置缓存 | — |
| `clearCache` | 清除配置缓存 | — |
| `reset` | 重置缓存（测试用） | — |


