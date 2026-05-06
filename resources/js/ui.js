// 1. 指令层 (响应式基础)
import './y-directive/index.js';

// 2. Live 层 (通信、状态同步)
import './y-live/index.js';

// 3. 启动指令系统 (确保在所有指令注册完成后执行)
if (window.Y && window.Y.boot) {
    document.body.classList.add('y-initializing');
    
    const boot = () => {
        window.Y.boot();
        setTimeout(() => {
            document.body.classList.remove('y-initializing');
            document.body.classList.add('y-ready');
        }, 50);
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }
}

import 'bootstrap-icons/font/bootstrap-icons.css';
