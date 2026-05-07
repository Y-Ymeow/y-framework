import Sortable from 'sortablejs';

class PageBuilder {
    constructor() {
        this.sortableInstances = [];
        this.initializedBuilders = new WeakSet();
        this.zoomLevels = [0.5, 0.75, 1, 1.25, 1.5];
        this.currentZoomIndex = 2;
    }

    init(root = document) {
        root.querySelectorAll('[data-page-builder]').forEach(builder => {
            this.initDragFromPanel(builder);
            this.initCanvasSortable(builder);
            this.initZoomControls(builder);
        });
    }

    initDragFromPanel(builder) {
        if (this.initializedBuilders.has(builder)) return;
        this.initializedBuilders.add(builder);

        builder.addEventListener('dragstart', (e) => {
            const item = e.target.closest('.page-builder-component-item[draggable]');
            if (!item) return;
            e.dataTransfer.setData('component-type', item.dataset.componentType);
            e.dataTransfer.effectAllowed = 'copy';
        });

        builder.addEventListener('dragover', (e) => {
            const canvas = e.target.closest('[data-builder-canvas]');
            if (!canvas) return;
            e.preventDefault();
            e.stopPropagation();
            e.dataTransfer.dropEffect = 'copy';

            builder.querySelectorAll('.page-builder-canvas-dragover').forEach(el => {
                el.classList.remove('page-builder-canvas-dragover');
            });
            canvas.classList.add('page-builder-canvas-dragover');
        });

        builder.addEventListener('dragleave', (e) => {
            const canvas = e.target.closest('[data-builder-canvas]');
            if (!canvas) return;
            if (!canvas.contains(e.relatedTarget)) {
                canvas.classList.remove('page-builder-canvas-dragover');
            }
        });

        builder.addEventListener('drop', (e) => {
            const canvas = e.target.closest('[data-builder-canvas]');
            if (!canvas) return;
            e.preventDefault();
            e.stopPropagation();
            canvas.classList.remove('page-builder-canvas-dragover');

            const componentType = e.dataTransfer.getData('component-type');
            if (!componentType) return;

            const isChildCanvas = canvas.matches('.pb-comp-children');
            if (isChildCanvas) {
                const comp = canvas.closest('.pb-comp');
                const parentUid = comp?.dataset.uid;
                if (parentUid) {
                    this.callAddChild(parentUid, componentType, builder);
                    return;
                }
            }

            this.addComponent(componentType, builder);
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
        this.callUpdateTree(builder, tree);
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
                handle: '.pb-comp-toolbar',
                animation: 150,
                ghostClass: 'page-builder-sortable-ghost',
                chosenClass: 'page-builder-sortable-chosen',
                onEnd: () => {
                    const tree = this.readTreeFromDom(builder);
                    this.setTree(builder, tree);
                    this.callUpdateTree(builder, tree);
                },
            });
            this.sortableInstances.push(instance);
        });
    }

    initZoomControls(builder) {
        const canvas = builder.querySelector('.page-builder-canvas');
        const label = builder.querySelector('[data-zoom-label]');
        const zoomIn = builder.querySelector('[data-zoom-in]');
        const zoomOut = builder.querySelector('[data-zoom-out]');
        const zoomFit = builder.querySelector('[data-zoom-fit]');

        if (!canvas || !label) return;

        const applyZoom = () => {
            const scale = this.zoomLevels[this.currentZoomIndex];
            canvas.style.transform = `scale(${scale})`;
            canvas.style.transformOrigin = 'top left';
            canvas.style.width = `${100 / scale}%`;
            label.textContent = `${Math.round(scale * 100)}%`;
        };

        if (zoomIn) {
            zoomIn.addEventListener('click', () => {
                if (this.currentZoomIndex < this.zoomLevels.length - 1) {
                    this.currentZoomIndex++;
                    applyZoom();
                }
            });
        }

        if (zoomOut) {
            zoomOut.addEventListener('click', () => {
                if (this.currentZoomIndex > 0) {
                    this.currentZoomIndex--;
                    applyZoom();
                }
            });
        }

        if (zoomFit) {
            zoomFit.addEventListener('click', () => {
                this.currentZoomIndex = 2;
                applyZoom();
            });
        }
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
            const cards = container.querySelectorAll(':scope > .pb-comp');
            cards.forEach(card => {
                const uid = card.dataset.uid || '';
                const type = card.dataset.componentType || '';
                const existing = this.findInTree(this.getTree(builder), uid);
                const settings = existing ? existing.settings || {} : {};
                const childContainer = card.querySelector(':scope > .pb-comp-children');
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

    destroy() {
        this.sortableInstances.forEach(i => i.destroy());
        this.sortableInstances = [];
        this.initializedBuilders = new WeakSet();
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
    const builder = root.closest('[data-page-builder]') || root.querySelector('[data-page-builder]');
    if (builder) {
        window.PageBuilder.initCanvasSortable(builder);
        window.PageBuilder.initZoomControls(builder);
    }
});
