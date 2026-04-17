# Y Framework

一个基于 PHP 8.4 的高性能、编译驱动、全函数式 UI 的极简框架。

## 特性

- **极简主义**：拒绝过度工程（Over-engineering），远离 Laravel/Symfony 的复杂性。
- **全函数式 UI**：不写 HTML 模板，用纯 PHP 函数构建类型安全的界面。
- **Y-Live 动态更新**：无需编写 JavaScript，通过 Morphing 技术实现精准的页面局部刷新。
- **现代化前端**：内置 Vite 资源打包与 HMR，原生支持 Inertia.js 风格的 SPA 开发。
- **编译驱动**：路由、配置、UI 片段全面支持预编译与缓存。
- **去耦合 Action**：支持全局函数作为路由处理器，逻辑高度内聚。
- **智能映射器**：基于 PHP 8 Attribute 的 POPO 到数据库映射，内置高性能分页。

## 快速开始

1. **安装依赖**
   ```bash
   composer install
   ```

2. **前端构建 (可选)**
   ```bash
   npm install
   ```

3. **数据库迁移**
   ```bash
   php bin/console db:migrate
   ```

4. **启动开发服务器**
   - 运行 Vite (前端 HMR):
     ```bash
     npm run dev
     ```
   - 使用内置 PHP 服务器:
     ```bash
     php -S 127.0.0.1:8000 -t public public/index.php
     ```

## 文档

更多详细说明请查看 `docs/` 目录：
- [架构概览](docs/architecture.md)
- [函数式 UI 指南](docs/ui.md)
- [Y-Live 动态更新](docs/live.md)
- [现代化前端 (Vite & Inertia)](docs/frontend.md)
- [数据库与映射器](docs/database.md)

## 核心哲学

- **逻辑局部性 (Locality)**：让一个功能的代码待在一起。
- **性能第一**：每一毫秒的运行时解析都是浪费。
- **显式优于隐式**：你可以看到每一行 SQL 和每一个输出。
