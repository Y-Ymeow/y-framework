// Persistent - Live 组件持久化支持
// 处理 local/session 类型的属性持久化，跨页面/跨会话保持组件状态

const STORAGE_PREFIX = 'y_live_persistent:';

export function initPersistent() {
    restoreAllPersistentData();
    bindPersistentElements();

    // 暴露 API 供组件使用
    window.$persistent = {
        set,
        get,
        remove,
        clear,
    };
}

/**
 * 从浏览器存储恢复所有持久化数据
 */
function restoreAllPersistentData() {
    const data = {};

    // 从 localStorage 恢复
    for (let i = 0; i < localStorage.length; i++) {
        const key = localStorage.key(i);
        if (key && key.startsWith(STORAGE_PREFIX + 'local:')) {
            const persistentKey = key.substring(STORAGE_PREFIX.length + 6);
            try {
                const item = JSON.parse(localStorage.getItem(key));
                if (item.expires && Date.now() > item.expires) {
                    localStorage.removeItem(key);
                    continue;
                }
                data[persistentKey] = item.value;
            } catch (e) {
                // ignore
            }
        }
    }

    // 从 sessionStorage 恢复
    for (let i = 0; i < sessionStorage.length; i++) {
        const key = sessionStorage.key(i);
        if (key && key.startsWith(STORAGE_PREFIX + 'session:')) {
            const persistentKey = key.substring(STORAGE_PREFIX.length + 8);
            try {
                const item = JSON.parse(sessionStorage.getItem(key));
                if (item.expires && Date.now() > item.expires) {
                    sessionStorage.removeItem(key);
                    continue;
                }
                data[persistentKey] = item.value;
            } catch (e) {
                // ignore
            }
        }
    }

    // 将数据注入到全局，供组件初始化时读取
    if (Object.keys(data).length > 0) {
        window.__y_persistent_data = data;
    }
}

/**
 * 绑定所有带有 data-persistent 属性的元素
 */
function bindPersistentElements() {
    document.querySelectorAll('[data-persistent]').forEach(el => {
        if (el._y_persistent_bound) return;
        el._y_persistent_bound = true;

        try {
            const meta = JSON.parse(el.dataset.persistent);
            const componentId = el.dataset.liveId;

            // 注册持久化元数据
            if (!window.__y_persistent_meta) {
                window.__y_persistent_meta = {};
            }

            for (const [propName, propMeta] of Object.entries(meta)) {
                const fullKey = `${componentId}.${propName}`;
                window.__y_persistent_meta[fullKey] = propMeta;

                // 如果有本地/会话存储数据，恢复它
                if (['local', 'session'].includes(propMeta.driver)) {
                    const stored = get(fullKey, propMeta.driver);
                    if (stored !== null) {
                        // 通知组件恢复数据（通过自定义事件）
                        el.dispatchEvent(new CustomEvent('y:persistent:restored', {
                            detail: { property: propName, value: stored, key: fullKey }
                        }));
                    }
                }
            }
        } catch (e) {
            // ignore parse errors
        }
    });
}

/**
 * 监听组件更新事件，保存持久化数据
 */
document.addEventListener('y:component-updated', (e) => {
    saveComponentPersistentData(e.detail);
});

// 监听 y:updated 事件，重新绑定新渲染的元素
document.addEventListener('y:updated', () => {
    bindPersistentElements();
});

/**
 * 保存组件的持久化数据
 */
function saveComponentPersistentData(componentData) {
    const { componentId, patches, meta } = componentData || {};

    if (!patches || !componentId) return;

    // 从 data-persistent 获取元数据
    const persistentMeta = window.__y_persistent_meta || {};

    for (const [key, value] of Object.entries(patches)) {
        const fullKey = `${componentId}.${key}`;
        const propMeta = persistentMeta[fullKey];

        if (!propMeta) continue;

        const { driver, ttl } = propMeta;

        if (driver === 'local') {
            set(fullKey, value, 'local', ttl);
        } else if (driver === 'session') {
            set(fullKey, value, 'session', ttl);
        }
    }
}

/**
 * 设置持久化数据
 */
export function set(key, value, driver = 'local', ttl = null) {
    const storageKey = STORAGE_PREFIX + driver + ':' + key;
    const data = {
        value,
        expires: ttl ? Date.now() + (ttl * 1000) : null,
        timestamp: Date.now(),
    };

    const json = JSON.stringify(data);

    try {
        if (driver === 'local') {
            localStorage.setItem(storageKey, json);
        } else if (driver === 'session') {
            sessionStorage.setItem(storageKey, json);
        }
        return true;
    } catch (e) {
        console.error('Persistent storage error:', e);
        return false;
    }
}

/**
 * 获取持久化数据
 */
export function get(key, driver = 'local') {
    const storageKey = STORAGE_PREFIX + driver + ':' + key;

    try {
        let json;
        if (driver === 'local') {
            json = localStorage.getItem(storageKey);
        } else if (driver === 'session') {
            json = sessionStorage.getItem(storageKey);
        }

        if (!json) return null;

        const data = JSON.parse(json);

        // 检查过期时间
        if (data.expires && Date.now() > data.expires) {
            remove(key, driver);
            return null;
        }

        return data.value;
    } catch (e) {
        return null;
    }
}

/**
 * 删除持久化数据
 */
export function remove(key, driver = 'local') {
    const storageKey = STORAGE_PREFIX + driver + ':' + key;

    try {
        if (driver === 'local') {
            localStorage.removeItem(storageKey);
        } else if (driver === 'session') {
            sessionStorage.removeItem(storageKey);
        }
        return true;
    } catch (e) {
        return false;
    }
}

/**
 * 清除所有持久化数据
 */
export function clear(driver = null) {
    try {
        const storage = driver === 'session' ? sessionStorage : localStorage;
        const prefix = driver ? (STORAGE_PREFIX + driver + ':') : STORAGE_PREFIX;

        const keysToRemove = [];
        for (let i = 0; i < storage.length; i++) {
            const key = storage.key(i);
            if (key.startsWith(prefix)) {
                keysToRemove.push(key);
            }
        }
        keysToRemove.forEach(key => storage.removeItem(key));
    } catch (e) {
        console.error('Persistent clear error:', e);
    }
}
