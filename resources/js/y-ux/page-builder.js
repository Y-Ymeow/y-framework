import Sortable from 'sortablejs';

class PageBuilder {
    constructor() {
        this.sortableInstances = [];
    }

    init(root = document) {
        root.querySelectorAll('[data-page-builder]').forEach(builder => {
            this.initDragFromPanel(builder);
            this.initCanvasSortable(builder);
        });
    }

    initDragFromPanel(builder) {
        const items = builder.querySelectorAll('.page-builder-component-item[draggable]');
        items.forEach(item => {
            item.addEventListener('dragstart', (e) => {
                e.dataTransfer.setData('component-type', item.dataset.componentType);
                e.dataTransfer.effectAllowed = 'copy';
            });
        });

        const canvases = builder.querySelectorAll('[data-builder-canvas]');
        canvases.forEach(canvas => {
            canvas.addEventListener('dragover', (e) => {
                e.preventDefault();
                e.stopPropagation();
                e.dataTransfer.dropEffect = 'copy';
                canvas.classList.add('page-builder-canvas-dragover');
            });

            canvas.addEventListener('dragleave', (e) => {
                if (!canvas.contains(e.relatedTarget)) {
                    canvas.classList.remove('page-builder-canvas-dragover');
                }
            });

            canvas.addEventListener('drop', (e) => {
                e.preventDefault();
                e.stopPropagation();
                canvas.classList.remove('page-builder-canvas-dragover');

                const componentType = e.dataTransfer.getData('component-type');
                if (!componentType) return;

                const isChildCanvas = canvas.matches('.pb-card-children');
                if (isChildCanvas) {
                    const card = canvas.closest('.pb-card');
                    const parentUid = card?.dataset.uid;
                    if (parentUid) {
                        this.callAddChild(parentUid, componentType, builder);
                        return;
                    }
                }

                this.addComponent(componentType, builder);
            });
        });
    }

    addComponent(componentType, builder) {
        const tree = this.getTree(builder);
        const uid = 'c' + Date.now() + Math.random().toString(36).substr(2, 5);

        tree.push({
            uid,
            type: componentType,
            settings: {},
            children: [],
        });

        this.setTree(builder, tree);
        this.callUpdateTree(builder, tree); // 更新 LiveComponent 状态，不保存文件
    }

    callAddChild(parentUid, componentType, builder) {
        const liveEl = builder.closest('[data-live]');
        if (liveEl && liveEl.$live) {
            liveEl.$live.addChildComponent({ parentUid, componentType });
        }
    }

    callUpdateTree(builder, tree) {
        const liveEl = builder.closest('[data-live]');
        if (liveEl && liveEl.$live) {
            liveEl.$live.updateComponentTree({ tree: JSON.stringify(tree) });
        }
    }

    initCanvasSortable(builder) {
        this.sortableInstances.forEach(i => i.destroy());
        this.sortableInstances = [];

        builder.querySelectorAll('[data-builder-canvas]').forEach(canvas => {
            const instance = Sortable.create(canvas, {
                group: 'page-builder',
                handle: '.pb-card-header',
                animation: 150,
                ghostClass: 'page-builder-sortable-ghost',
                chosenClass: 'page-builder-sortable-chosen',
                onEnd: () => {
                    const tree = this.readTreeFromDom(builder);
                    this.setTree(builder, tree);
                    this.callUpdateTree(builder, tree); // 更新 LiveComponent 状态，不保存文件
                },
            });
            this.sortableInstances.push(instance);
        });
    }

    getTree(builder) {
        const attr = builder.dataset.componentTree;
        try {
            return JSON.parse(attr || '[]');
        } catch {
            return [];
        }
    }

    setTree(builder, tree) {
        builder.dataset.componentTree = JSON.stringify(tree);
    }

    readTreeFromDom(builder) {
        const mainCanvas = builder.querySelector('.page-builder-canvas[data-builder-canvas]');
        if (!mainCanvas) return [];

        const readLevel = (container) => {
            const items = [];
            const cards = container.querySelectorAll(':scope > .pb-card');
            cards.forEach(card => {
                const uid = card.dataset.uid || '';
                const type = card.dataset.componentType || '';
                const existing = this.findInTree(this.getTree(builder), uid);
                const settings = existing ? existing.settings || {} : {};
                const childContainer = card.querySelector(':scope > .pb-card-children');
                const childItems = childContainer ? readLevel(childContainer) : [];

                items.push({ uid, type, settings, children: childItems });
            });
            return items;
        };

        return readLevel(mainCanvas);
    }

    findInTree(tree, uid) {
        for (const item of tree) {
            if (item.uid === uid) return item;
            if (item.children && item.children.length) {
                const found = this.findInTree(item.children, uid);
                if (found) return found;
            }
        }
        return null;
    }

    callSaveTree(builder, tree) {
        const liveEl = builder.closest('[data-live]');
        if (liveEl && liveEl.$live) {
            liveEl.$live.saveTree({ tree: JSON.stringify(tree) });
        }
    }

    destroy() {
        this.sortableInstances.forEach(i => i.destroy());
        this.sortableInstances = [];
    }
}

window.PageBuilder = new PageBuilder();

document.addEventListener('DOMContentLoaded', () => {
    window.PageBuilder.init();
});

window.addEventListener('y:ready', () => {
    window.PageBuilder.init();
});

window.addEventListener('y:updated', (e) => {
    const root = e.detail?.el || document;
    window.PageBuilder.init(root);
});
