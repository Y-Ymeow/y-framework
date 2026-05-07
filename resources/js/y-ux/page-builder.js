import Sortable from 'sortablejs';

class PageBuilder {
    constructor() {
        this.sortableInstances = [];
        this.initializedBuilders = new WeakSet();
        this.zoomLevels = [0.5, 0.75, 1, 1.25, 1.5];
        this.currentZoomIndex = 2;
        this.currentPreview = 'desktop';
    }

    init(root = document) {
        root.querySelectorAll('[data-page-builder]').forEach(builder => {
            this.initDragFromPanel(builder);
            this.initCanvasSortable(builder);
            this.initZoomControls(builder);
            this.initPreviewControls(builder);
            this.initPropertiesTabs(builder);
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
        const liveEl = builder.closest('[data-live]');
        if (liveEl && liveEl.$live) {
            const tree = this.getTree(builder);
            const uid = 'c' + Date.now() + Math.random().toString(36).substr(2, 5);

            tree.push({
                uid,
                type: componentType,
                settings: {},
                children: [],
            });

            this.setTree(builder, tree);
            liveEl.$live.updateComponentTree({ tree: JSON.stringify(tree) });
            liveEl.$live.toggleComponent({ uid });
        }
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

    initPreviewControls(builder) {
        const canvas = builder.querySelector('.page-builder-canvas');
        if (!canvas) return;

        builder.querySelectorAll('[data-preview-btn]').forEach(btn => {
            btn.addEventListener('click', () => {
                const mode = btn.dataset.previewBtn;
                this.currentPreview = mode;

                builder.querySelectorAll('[data-preview-btn]').forEach(b => b.classList.remove('active'));
                btn.classList.add('active');

                canvas.setAttribute('data-preview', mode);
            });
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

    initPropertiesTabs(builder) {
        const tabs = builder.querySelectorAll('[data-properties-tab]');
        tabs.forEach(tab => {
            tab.addEventListener('click', () => {
                const target = tab.dataset.propertiesTab;
                const panel = tab.closest('.page-builder-properties-body');
                if (!panel) return;

                panel.querySelectorAll('[data-properties-tab]').forEach(t => t.classList.remove('page-builder-properties-tab--active'));
                tab.classList.add('page-builder-properties-tab--active');

                panel.querySelectorAll('[data-properties-panel]').forEach(p => p.classList.remove('page-builder-properties-tab-content--active'));
                const targetPanel = panel.querySelector(`[data-properties-panel="${target}"]`);
                if (targetPanel) targetPanel.classList.add('page-builder-properties-tab-content--active');
            });
        });
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
        window.PageBuilder.initDragFromPanel(builder);
        window.PageBuilder.initCanvasSortable(builder);
        window.PageBuilder.initZoomControls(builder);
        window.PageBuilder.initPreviewControls(builder);
        window.PageBuilder.initPropertiesTabs(builder);
    }
});
