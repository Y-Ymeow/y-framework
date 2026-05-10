function initBlockEditor() {
    document.querySelectorAll(".ux-block-editor").forEach(wrapper => {
        if (wrapper.dataset.blockEditorInit) return;

        const canvas = wrapper.querySelector(".ux-block-editor__canvas");
        if (!canvas) return;

        const liveEl = wrapper.closest("[data-live]");
        if (!liveEl || !liveEl.$live) return;

        wrapper.dataset.blockEditorInit = "1";

        initSortable(canvas, liveEl);
        initEditableBindings(wrapper);
        initAttrInputBindings(wrapper);
        initMediaPicker(wrapper);
        initFormatToolbar(wrapper);
        initListHandlers(wrapper);
        initColCountHandler(wrapper);
        initSubmitSync(wrapper);

        if (window.__beFormatDefs) {
            const formatDefs = window.__beFormatDefs;
            delete window.__beFormatDefs;
            wrapper._formatDefs = formatDefs;
        }
    });
}

function initSortable(canvas, liveEl) {
    const blocks = canvas.querySelectorAll(".ux-block-editor__block");
    if (blocks.length < 2) return;

    if (typeof Sortable === "undefined") {
        const script = document.createElement("script");
        script.src = "https://cdn.jsdelivr.net/npm/sortablejs@1/Sortable.min.js";
        script.onload = () => initSortable(canvas, liveEl);
        document.head.appendChild(script);
        return;
    }

    new Sortable(canvas, {
        handle: "[data-drag-handle]",
        animation: 150,
        ghostClass: "ux-block-editor__block--ghost",
        onEnd() {
            const ordered = [];
            canvas.querySelectorAll(".ux-block-editor__block").forEach(el => {
                const name = el.dataset.blockName || "";
                const attrs = {};
                el.querySelectorAll("[data-editable-index]").forEach(ed => {
                    const attr = ed.dataset.editableAttr || "";
                    if (attr && ed.hasAttribute("contenteditable")) {
                        attrs[attr] = ed.innerHTML;
                    }
                });
                el.querySelectorAll("[data-attr-index]").forEach(inp => {
                    attrs[inp.dataset.attrName] = inp.type === "checkbox" ? inp.checked : inp.value;
                });
                ordered.push({ blockName: name, attributes: attrs });
            });
            liveEl.$live.reorderBlocks({ blocks: ordered });
        }
    });
}

function initEditableBindings(wrapper) {
    const liveEl = wrapper.closest("[data-live]");
    wrapper.addEventListener("focusin", (e) => {
        const ed = e.target.closest("[data-editable-index]");
        if (!ed || ed.dataset.editableBound) return;
        ed.dataset.editableBound = "1";

        ed.addEventListener("blur", () => {
            if (!ed.textContent.trim()) {
                ed.style.removeProperty("color");
                ed.style.setProperty("color", "transparent", "important");
            }
            const index = parseInt(ed.dataset.editableIndex);
            const attr = ed.dataset.editableAttr;
            const sub = ed.dataset.editableSubindex;
            const html = ed.innerHTML;

            if (attr === "listItem") {
                const blockEl = ed.closest(".ux-block-editor__block");
                const listEl = blockEl?.querySelector("[data-list-index]");
                if (listEl) {
                    const items = [];
                    listEl.querySelectorAll("[data-editable-attr='listItem']").forEach(item => {
                        items.push(item.innerHTML ? item.textContent : "");
                    });
                    liveEl.$live.updateListItem({ index, items });
                }
            } else if (attr === "column") {
                const si = parseInt(sub);
                liveEl.$live.updateColumns({ index, colIndex: si, content: html });
            } else {
                liveEl.$live.updateBlockAttr({ index, attr, value: html });
            }
        });

        ed.addEventListener("focus", () => {
            ed.style.removeProperty("color");
            if (ed.dataset.editablePlaceholder && !ed.textContent.trim()) {
                ed.setAttribute("data-editable-empty", "1");
            }
        });
        ed.addEventListener("input", () => {
            if (ed.textContent.trim()) {
                ed.removeAttribute("data-editable-empty");
            }
            clearTimeout(ed._debounceId);
            ed._debounceId = setTimeout(() => {
                const index = parseInt(ed.dataset.editableIndex);
                const attr = ed.dataset.editableAttr;
                const sub = ed.dataset.editableSubindex;
                const html = ed.innerHTML;
                if (attr === "column") {
                    const si = parseInt(sub);
                    liveEl.$live.updateColumns({ index, colIndex: si, content: html });
                } else if (attr !== "listItem") {
                    liveEl.$live.updateBlockAttr({ index, attr, value: html });
                }
            }, 600);
        });
    });

    wrapper.addEventListener("focusin", e => {
        const ed = e.target.closest("[data-table-index]");
        if (!ed || ed.dataset.tableBound) return;
        ed.dataset.tableBound = "1";

        ed.addEventListener("blur", () => {
            const index = parseInt(ed.dataset.tableIndex);
            const type = ed.dataset.tableType;
            const row = parseInt(ed.dataset.tableRow || "0");
            const col = parseInt(ed.dataset.tableCol || "0");
            liveEl.$live.updateTable({ index, type, row, col, value: ed.textContent });
        });
    });
}

function initAttrInputBindings(wrapper) {
    const liveEl = wrapper.closest("[data-live]");
    wrapper.addEventListener("change", e => {
        const el = e.target.closest("[data-attr-index]");
        if (!el) return;
        const index = parseInt(el.dataset.attrIndex);
        const attr = el.dataset.attrName;
        const value = el.type === "checkbox" ? String(el.checked) : el.value;
        liveEl.$live.updateBlockAttr({ index, attr, value });
    });
    wrapper.addEventListener("input", e => {
        const el = e.target.closest("[data-attr-index]");
        if (!el || el.tagName !== "INPUT" || el.type === "checkbox") return;
        const index = parseInt(el.dataset.attrIndex);
        const attr = el.dataset.attrName;
        liveEl.$live.updateBlockAttr({ index, attr, value: el.value });
    });
}

function initMediaPicker(wrapper) {
    const liveEl = wrapper.closest("[data-live]");
    wrapper.addEventListener("click", e => {
        const btn = e.target.closest("[data-media-pick]");
        if (!btn) return;
        const blockIndex = parseInt(btn.dataset.blockIndex);
        openMediaModal(wrapper, liveEl, blockIndex);
    });
}

function openMediaModal(wrapper, liveEl, blockIndex) {
    let modal = document.getElementById("ux-be-media-modal");
    if (!modal) {
        modal = document.createElement("div");
        modal.id = "ux-be-media-modal";
        modal.className = "ux-block-editor__media-modal-overlay";
        modal.innerHTML = '<div class="ux-block-editor__media-modal">'
            + '<div class="ux-block-editor__media-modal-header">'
            + '<span>选择图片</span>'
            + '<button type="button" class="ux-block-editor__media-modal-close">✕</button>'
            + '</div>'
            + '<div class="ux-block-editor__media-modal-body">'
            + '<div class="ux-block-editor__media-upload" data-media-upload>'
            + '<input type="file" accept="image/*" style="display:none">'
            + '<div class="ux-block-editor__media-upload-trigger">点击或拖拽上传</div>'
            + '</div>'
            + '<div class="ux-block-editor__media-grid"></div>'
            + '</div></div>';
        document.body.appendChild(modal);

        modal.querySelector(".ux-block-editor__media-modal-close").addEventListener("click", () => {
            modal.style.display = "none";
        });
        modal.addEventListener("click", e => { if (e.target === modal) modal.style.display = "none"; });

        const uploadZone = modal.querySelector("[data-media-upload]");
        uploadZone.addEventListener("click", () => uploadZone.querySelector("input[type=file]").click());
        uploadZone.querySelector("input[type=file]").addEventListener("change", async () => {
            const input = uploadZone.querySelector("input[type=file]");
            if (!input.files.length) return;
            const fd = new FormData();
            for (const f of input.files) fd.append("files[]", f);
            try {
                const resp = await fetch("/admin/media/upload", { method: "POST", body: fd, headers: { "X-Requested-With": "XMLHttpRequest" } });
                const data = await resp.json();
                if (data.success && data.results) {
                    loadMediaGrid(modal.querySelector(".ux-block-editor__media-grid"), wrapper, liveEl, blockIndex);
                }
            } catch (err) { console.error("Upload failed:", err); }
            input.value = "";
        });
    }

    loadMediaGrid(modal.querySelector(".ux-block-editor__media-grid"), wrapper, liveEl, blockIndex);
    modal.style.display = "flex";
    modal.dataset.blockIndex = blockIndex;
}

async function loadMediaGrid(gridEl, wrapper, liveEl, blockIndex) {
    gridEl.innerHTML = '<div class="ux-block-editor__media-loading">加载中...</div>';
    try {
        const resp = await fetch("/admin/api/media?filter=image&limit=40", { headers: { "X-Requested-With": "XMLHttpRequest" } });
        const data = await resp.json();
        gridEl.innerHTML = "";
        const items = data.items || data.data || [];
        items.forEach(item => {
            const url = item.url || ("/media/" + (item.path || ""));
            const thumb = item.thumbnail || (url + "?w=150&h=150&fit=true");
            const el = document.createElement("div");
            el.className = "ux-block-editor__media-item";
            el.innerHTML = '<img src="' + thumb + '" alt="" loading="lazy">';
            el.addEventListener("click", () => {
                liveEl.$live.updateBlockAttr({ index: blockIndex, attr: "src", value: url });
                const modal = document.getElementById("ux-be-media-modal");
                if (modal) modal.style.display = "none";
            });
            gridEl.appendChild(el);
        });
        if (items.length === 0) gridEl.innerHTML = '<div class="ux-block-editor__media-empty">暂无图片</div>';
    } catch (err) {
        gridEl.innerHTML = '<div class="ux-block-editor__media-error">加载失败</div>';
    }
}

function initFormatToolbar(wrapper) {
    let toolbar = document.getElementById("ux-be-format-toolbar");
    if (!toolbar) {
        toolbar = document.createElement("div");
        toolbar.id = "ux-be-format-toolbar";
        toolbar.className = "ux-block-editor__format-toolbar";
        toolbar.style.display = "none";
        toolbar.style.position = "fixed";
        toolbar.style.zIndex = "10000";
        toolbar.innerHTML = '<button data-fmt="bold"><b>B</b></button>'
            + '<button data-fmt="italic"><i>I</i></button>'
            + '<button data-fmt="underline"><u>U</u></button>'
            + '<button data-fmt="strikethrough"><s>S</s></button>'
            + '<button data-fmt="code">`</button>'
            + '<span class="ux-be-fmt-sep"></span>'
            + '<button data-fmt="link">🔗</button>'
            + '<button data-fmt="unlink">🔓</button>'
            + '<button data-fmt="superscript">A²</button>'
            + '<button data-fmt="subscript">A₂</button>';
        document.body.appendChild(toolbar);

        toolbar.addEventListener("click", e => {
            e.preventDefault();
            const btn = e.target.closest("[data-fmt]");
            if (!btn) return;
            const fmt = btn.dataset.fmt;
            applyFormat(fmt);
            hideFormatToolbar();
        });
    }

    let lastEditable = null;
    wrapper.addEventListener("mouseup", () => {
        setTimeout(() => checkSelection(wrapper, toolbar), 10);
    });
    wrapper.addEventListener("keyup", () => {
        setTimeout(() => checkSelection(wrapper, toolbar), 10);
    });
    wrapper.addEventListener("keydown", () => {
        toolbar.style.display = "none";
    });
    document.addEventListener("mousedown", e => {
        if (!e.target.closest(".ux-block-editor") && !e.target.closest("#ux-be-format-toolbar")) {
            toolbar.style.display = "none";
        }
    });
}

function checkSelection(wrapper, toolbar) {
    const sel = window.getSelection();
    if (!sel || sel.isCollapsed || !sel.rangeCount) {
        toolbar.style.display = "none";
        return;
    }
    const range = sel.getRangeAt(0);
    const editable = range.commonAncestorContainer.closest?.("[contenteditable]");
    if (!editable || !wrapper.contains(editable)) {
        toolbar.style.display = "none";
        return;
    }
    const rect = range.getBoundingClientRect();
    if (!rect || !rect.width) return;
    toolbar.style.display = "flex";
    toolbar.style.left = (rect.left + rect.width / 2 - toolbar.offsetWidth / 2) + "px";
    toolbar.style.top = (rect.top - toolbar.offsetHeight - 8 + window.scrollY) + "px";
}

function hideFormatToolbar() {
    const toolbar = document.getElementById("ux-be-format-toolbar");
    if (toolbar) toolbar.style.display = "none";
}

function applyFormat(fmt) {
    const sel = window.getSelection();
    if (!sel || !sel.rangeCount) return;
    const range = sel.getRangeAt(0);

    if (fmt === "link") {
        const href = prompt("输入链接地址：", "https://");
        if (!href) return;
        const a = document.createElement("a");
        a.href = href;
        a.target = "_blank";
        a.rel = "noopener noreferrer";
        range.surroundContents(a);
        return;
    }
    if (fmt === "unlink") {
        const a = range.commonAncestorContainer.closest?.("a");
        if (a) {
            const parent = a.parentNode;
            while (a.firstChild) parent.insertBefore(a.firstChild, a);
            parent.removeChild(a);
        }
        return;
    }

    const tagMap = {
        bold: "strong", italic: "em", underline: "u",
        strikethrough: "s", code: "code",
        superscript: "sup", subscript: "sub"
    };
    const tag = tagMap[fmt];
    if (!tag) return;

    const el = document.createElement(tag);
    try { range.surroundContents(el); } catch (e) {
        const frag = range.extractContents();
        el.appendChild(frag);
        range.insertNode(el);
    }
}

function initListHandlers(wrapper) {
    const liveEl = wrapper.closest("[data-live]");
    wrapper.addEventListener("click", e => {
        const btn = e.target.closest("[data-list-remove]");
        if (!btn) return;
        const index = parseInt(btn.dataset.listRemove);
        const blockEl = btn.closest(".ux-block-editor__block");
        const listEl = blockEl?.querySelector("[data-list-index]");
        if (!listEl) return;
        const blockIdx = parseInt(listEl.dataset.listIndex);
        const items = [];
        listEl.querySelectorAll("[data-editable-attr='listItem']").forEach((item, i) => {
            if (i !== index) items.push(item.innerHTML ? item.textContent : "");
        });
        liveEl.$live.updateListItem({ index: blockIdx, items });
    });
}

function initColCountHandler(wrapper) {
    const liveEl = wrapper.closest("[data-live]");
    wrapper.addEventListener("change", e => {
        const sel = e.target.closest("[data-col-count-index]");
        if (!sel) return;
        const index = parseInt(sel.dataset.colCountIndex);
        const count = parseInt(sel.value);
        liveEl.$live.changeColumnCount({ index, count });
    });
}

function initSubmitSync(wrapper) {
    wrapper.addEventListener("mousedown", (e) => {
        const btn = e.target.closest("button");
        if (!btn) return;
        const submitAction = btn.getAttribute("data-submit:click");
        if (!submitAction || !btn.closest(".ux-block-editor__toolbar")) return;

        const blockNameEl = wrapper.querySelector("[data-submit-field='blockName']");
        if (blockNameEl) {
            blockNameEl.value = btn.getAttribute("data-block-name") || "";
        }

        const blocksEl = wrapper.querySelector("[data-submit-field='currentBlocks']");
        if (blocksEl) {
            const canvas = wrapper.querySelector(".ux-block-editor__canvas");
            const blocks = [];
            if (canvas) {
                canvas.querySelectorAll(".ux-block-editor__block").forEach(blockEl => {
                    const bn = blockEl.dataset.blockName || "";
                    const attrs = {};
                    blockEl.querySelectorAll("[data-editable-index][data-editable-attr]").forEach(ed => {
                        const attr = ed.dataset.editableAttr;
                        if (attr && ed.hasAttribute("contenteditable")) {
                            attrs[attr] = ed.innerHTML;
                        }
                    });
                    blockEl.querySelectorAll("[data-attr-index][data-attr-name]").forEach(inp => {
                        attrs[inp.dataset.attrName] = inp.type === "checkbox" ? String(inp.checked) : inp.value;
                    });
                    blocks.push({ blockName: bn, attributes: attrs });
                });
            }
            blocksEl.value = JSON.stringify(blocks);
        }
    });
}

document.addEventListener("DOMContentLoaded", initBlockEditor);
document.addEventListener("y:ready", initBlockEditor);
document.addEventListener("y:updated", (e) => {
    const root = e.detail?.el || document;
    const wrapper = root.closest?.(".ux-block-editor") || root.querySelector?.(".ux-block-editor");
    if (!wrapper || !wrapper.dataset.blockEditorInit) return;
    const canvas = wrapper.querySelector(".ux-block-editor__canvas");
    const liveEl = wrapper.closest("[data-live]");
    if (canvas && liveEl?.$live) {
        initSortable(canvas, liveEl);
    }
});

if (typeof window !== "undefined") {
    window.__BlockEditor_updateAttr = function (wrapper, params) {
        const liveEl = wrapper?.closest?.("[data-live]");
        if (liveEl?.$live) liveEl.$live.updateBlockAttr(params);
    };
}