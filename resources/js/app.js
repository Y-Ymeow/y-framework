// Application Entry - 统一入口
// 按需引入各层

// 1. 指令层 (响应式基础)
import './y-directive/index.js';

// 2. Live 层 (通信、状态同步)
import './y-live/index.js';

// 3. UX 层 (组件交互)
import './y-ux/index.js';

// 配置 Live 框架
if (window.L) {
    // 启用 Live badge (开发调试)
    // L.configure({ badge: true });
}

console.log('App loaded: y-directive + y-live + y-ux');
