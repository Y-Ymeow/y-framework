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
        const canvas = builder.querySelector('.page-builder-canvas[data-builder-canvas]') || builder;
        const attr = canvas.dataset.componentTree;
        try {
            return JSON.parse(attr || '[]');
        } catch {
            return [];
        }
    }

    setTree(builder, tree) {
        const canvas = builder.querySelector('.page-builder-canvas[data-builder-canvas]') || builder;
        canvas.dataset.componentTree = JSON.stringify(tree);
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

        this.initStyleEditor(builder);
    }

    initStyleEditor(builder) {
        const stylePanel = builder.querySelector('[data-properties-panel="style"]');
        if (!stylePanel) return;

        stylePanel.querySelectorAll('.page-builder-style-classes').forEach(input => {
            input.removeEventListener('input', this._syncStylesJson);
            input.addEventListener('input', this._syncStylesJson);
        });

        stylePanel.querySelectorAll('[data-style-remove]').forEach(btn => {
            btn.removeEventListener('click', this._removeStyleRow);
            btn.addEventListener('click', this._removeStyleRow);
        });

        const addBtn = stylePanel.querySelector('[data-style-add]');
        if (addBtn) {
            addBtn.removeEventListener('click', this._addStyleRow);
            addBtn.addEventListener('click', this._addStyleRow);
        }
    }

    _syncStylesJson() {
        const panel = this.closest('[data-properties-panel]');
        if (!panel) return;

        const styles = {};
        panel.querySelectorAll('[data-style-target]').forEach(input => {
            const target = input.dataset.styleTarget;
            const value = input.value.trim();
            if (target && value) {
                styles[target] = value;
            }
        });

        const hiddenInput = panel.querySelector('[data-submit-field="styles_json"]');
        if (hiddenInput) {
            hiddenInput.value = JSON.stringify(styles);
        }
    }

    _removeStyleRow(e) {
        const btn = e.currentTarget;
        const row = btn.closest('.page-builder-style-row');
        if (row) {
            row.remove();
            const panel = btn.closest('[data-properties-panel]');
            const input = panel?.querySelector('[data-submit-field="styles_json"]');
            if (input) {
                const styles = {};
                panel.querySelectorAll('[data-style-target]').forEach(i => {
                    const target = i.dataset.styleTarget;
                    const value = i.value.trim();
                    if (target && value) styles[target] = value;
                });
                input.value = JSON.stringify(styles);
            }
        }
    }

    _addStyleRow() {
        const panel = this.closest('[data-properties-panel]');
        if (!panel) return;

        const select = panel.querySelector('[data-style-target-select]');
        if (!select) return;

        const target = select.value;
        if (!target) return;

        const existing = panel.querySelector(`[data-style-target="${target}"]`);
        if (existing) {
            existing.focus();
            return;
        }

        const option = select.querySelector(`option[value="${target}"]`);
        const label = option ? option.textContent : target;

        const row = document.createElement('div');
        row.className = 'page-builder-style-row';

        const rowHeader = document.createElement('div');
        rowHeader.className = 'page-builder-style-row-header';

        const labelSpan = document.createElement('span');
        labelSpan.className = 'page-builder-style-target';
        labelSpan.textContent = label;

        const removeBtn = document.createElement('button');
        removeBtn.className = 'page-builder-style-remove';
        removeBtn.type = 'button';
        removeBtn.dataset.styleRemove = target;
        removeBtn.innerHTML = '<i class="bi bi-x"></i>';
        removeBtn.addEventListener('click', PageBuilder._removeStyleRow);

        rowHeader.appendChild(labelSpan);
        rowHeader.appendChild(removeBtn);

        const textarea = document.createElement('textarea');
        textarea.className = 'ux-form-input page-builder-style-classes';
        textarea.dataset.styleTarget = target;
        textarea.rows = 2;
        textarea.placeholder = 'CSS 类名，空格分隔';
        textarea.addEventListener('input', PageBuilder._syncStylesJson);

        row.appendChild(rowHeader);
        row.appendChild(textarea);

        const addRow = panel.querySelector('.page-builder-style-add');
        if (addRow) {
            panel.insertBefore(row, addRow);
        } else {
            panel.appendChild(row);
        }

        textarea.focus();
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
        window.PageBuilder.initStyleEditor(builder);
    }
});
