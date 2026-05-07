import Sortable from 'sortablejs';

class MenuManager {
    constructor() {
        this.instances = new Map();
    }

    init(root = document) {
        this.destroy(root);

        root.querySelectorAll('[data-menu-sortable]').forEach(el => {
            const menuId = el.dataset.menuId;
            const groupName = `menu-${menuId}`;

            const instance = Sortable.create(el, {
                group: { name: groupName, put: true },
                handle: '.menu-tree-item-handle',
                animation: 150,
                fallbackOnBody: true,
                swapThreshold: 0.5,
                invertSwap: true,
                ghostClass: 'menu-sortable-ghost',
                chosenClass: 'menu-sortable-chosen',
                dragClass: 'menu-sortable-drag',
                onEnd: (evt) => {
                    this.handleDragEnd(el);
                },
            });

            this.instances.set(el, instance);

            el.querySelectorAll('[data-menu-sortable-children]').forEach(childList => {
                const childInstance = Sortable.create(childList, {
                    group: { name: groupName, put: true },
                    handle: '.menu-tree-item-handle',
                    animation: 150,
                    fallbackOnBody: true,
                    swapThreshold: 0.5,
                    invertSwap: true,
                    ghostClass: 'menu-sortable-ghost',
                    chosenClass: 'menu-sortable-chosen',
                    dragClass: 'menu-sortable-drag',
                    onEnd: (evt) => {
                        this.handleDragEnd(el);
                    },
                });

                this.instances.set(childList, childInstance);
            });
        });
    }

    handleDragEnd(rootEl) {
        const orderData = this.collectOrder(rootEl);
        const liveEl = rootEl.closest('[data-live]');
        if (!liveEl || !liveEl.$live) return;

        const result = liveEl.$live.saveOrder({ order: orderData });

        if (result && typeof result.then === 'function') {
            result.then(() => {
                setTimeout(() => this.init(liveEl), 50);
            });
        } else {
            setTimeout(() => this.init(liveEl), 200);
        }
    }

    collectOrder(rootEl) {
        const items = [];
        let sortIndex = 0;

        const processLevel = (container, parentId) => {
            const directChildren = container.querySelectorAll(':scope > .menu-tree-item, :scope > .menu-tree-item-children > .menu-tree-item');
            directChildren.forEach(item => {
                const id = parseInt(item.dataset.itemId, 10);
                if (!id) return;

                items.push({
                    id,
                    parentId: parentId || '',
                    sort: sortIndex++,
                });
            });

            const childContainers = container.querySelectorAll(':scope > .menu-tree-item-children');
            childContainers.forEach(childContainer => {
                const parentItem = childContainer.previousElementSibling;
                let childParentId = parentId;
                if (parentItem && parentItem.classList.contains('menu-tree-item')) {
                    childParentId = parseInt(parentItem.dataset.itemId, 10) || parentId;
                }

                const childItems = childContainer.querySelectorAll(':scope > .menu-tree-item');
                childItems.forEach(item => {
                    const id = parseInt(item.dataset.itemId, 10);
                    if (!id) return;

                    items.push({
                        id,
                        parentId: childParentId || '',
                        sort: sortIndex++,
                    });

                    const nestedChildren = item.nextElementSibling;
                    if (nestedChildren && nestedChildren.classList.contains('menu-tree-item-children')) {
                        processLevel(nestedChildren, id);
                    }
                });
            });
        };

        processLevel(rootEl, null);
        return items;
    }

    saveCurrentOrder(btnEl) {
        const treeEl = btnEl.closest('.menu-tree');
        if (!treeEl) return;
        const rootEl = treeEl.querySelector('[data-menu-sortable]');
        if (!rootEl) return;

        const orderData = this.collectOrder(rootEl);
        const liveEl = rootEl.closest('[data-live]');
        if (!liveEl || !liveEl.$live) return;

        const result = liveEl.$live.saveOrder({ order: orderData });

        if (result && typeof result.then === 'function') {
            result.then(() => {
                setTimeout(() => this.init(liveEl), 50);
            });
        } else {
            setTimeout(() => this.init(liveEl), 200);
        }
    }

    destroy(root = document) {
        this.instances.forEach((instance, el) => {
            if (!root.contains(el) && root !== document) return;
            if (instance && typeof instance.destroy === 'function') {
                instance.destroy();
            }
        });

        if (root === document) {
            this.instances.clear();
        } else {
            const toRemove = [];
            this.instances.forEach((instance, el) => {
                if (root.contains(el)) {
                    toRemove.push(el);
                }
            });
            toRemove.forEach(el => this.instances.delete(el));
        }
    }
}

window.MenuManager = new MenuManager();

document.addEventListener('DOMContentLoaded', () => {
    window.MenuManager.init();
});

window.addEventListener('y:ready', () => {
    window.MenuManager.init();
});

window.addEventListener('y:updated', (e) => {
    const root = e.detail?.el || document;
    window.MenuManager.init(root);
});
