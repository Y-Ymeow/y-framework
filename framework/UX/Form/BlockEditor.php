<?php

declare(strict_types=1);

namespace Framework\UX\Form;

use Framework\View\Base\Element;
use Framework\View\Document\AssetRegistry;
use Framework\UX\RichEditor\BlockRegistry;
use Framework\UX\RichEditor\BlockType;
use Framework\UX\RichEditor\InlineFormatRegistry;
use Framework\UX\RichEditor\RichEditorExtension;
use Framework\CSS\RichEditorRules;

class BlockEditor extends FormField
{
    protected static ?string $componentName = 'blockEditor';

    protected array $allowedBlocks = [];
    protected string $placeholder = '';
    protected ?string $width = null;
    protected ?string $minHeight = null;
    protected array $customBlockTypes = [];
    protected array $blockExtensions = [];

    public function __construct()
    {
        parent::__construct();
        AssetRegistry::getInstance()->inlineStyle('ux:block-editor', RichEditorRules::getBlockEditorStyles());
        BlockRegistry::registerCoreBlocks();
        InlineFormatRegistry::registerCoreFormats();
    }

    protected function init(): void
    {
        $this->registerJs('blockEditor', '
            const BlockEditor = {
                editors: new Map(),
                init() {
                    document.querySelectorAll(".ux-block-editor[data-editor-id]").forEach(el => {
                        if (!this.editors.has(el.dataset.editorId)) this.initEditor(el);
                    });
                },
                initEditor(wrapperEl) {
                    const editorId = wrapperEl.dataset.editorId;
                    if (!editorId) return;
                    const canvasEl = wrapperEl.querySelector(".ux-block-editor__canvas");
                    if (!canvasEl) return;
                    const hiddenInput = wrapperEl.querySelector("input[type=hidden]");
                    const blockDefs = JSON.parse(wrapperEl.dataset.blocks || "{}");
                    const formatDefs = JSON.parse(wrapperEl.dataset.formats || "{}");
                    const placeholder = wrapperEl.dataset.placeholder || "";
                    const state = { blocks: [], selectedBlockIndex: -1, blockDefs, formatDefs, placeholder };
                    const initialBlocks = canvasEl.dataset.initialBlocks;
                    if (initialBlocks) { try { state.blocks = JSON.parse(initialBlocks); } catch(e) { state.blocks = []; } }
                    if (state.blocks.length === 0) {
                        state.blocks.push({ blockName: "paragraph", attributes: { content: [{ text: "" }] } });
                    }
                    this.editors.set(editorId, { wrapperEl, canvasEl, hiddenInput, state });
                    this.renderBlocks(editorId);
                    this.bindGlobalEvents(editorId);
                },
                renderBlocks(editorId) {
                    const editor = this.editors.get(editorId);
                    if (!editor) return;
                    const { canvasEl, state } = editor;
                    canvasEl.innerHTML = "";
                    if (state.blocks.length === 0) {
                        state.blocks.push({ blockName: "paragraph", attributes: { content: [{ text: "" }] } });
                    }
                    state.blocks.forEach((block, index) => {
                        const blockEl = this.renderBlock(editorId, block, index);
                        canvasEl.appendChild(blockEl);
                    });
                },
                renderBlock(editorId, block, index) {
                    const editor = this.editors.get(editorId);
                    const blockDef = editor.state.blockDefs[block.blockName];
                    const isSelected = index === editor.state.selectedBlockIndex;
                    const wrapper = document.createElement("div");
                    wrapper.className = "ux-block-editor__block" + (isSelected ? " ux-block-editor__block--selected" : "");
                    wrapper.dataset.blockIndex = index;
                    wrapper.dataset.blockName = block.blockName;
                    const handle = document.createElement("div");
                    handle.className = "ux-block-editor__block-handle";
                    handle.innerHTML = \'<svg viewBox="0 0 24 24" width="16" height="16"><circle cx="9" cy="6" r="1.5"/><circle cx="15" cy="6" r="1.5"/><circle cx="9" cy="12" r="1.5"/><circle cx="15" cy="12" r="1.5"/><circle cx="9" cy="18" r="1.5"/><circle cx="15" cy="18" r="1.5"/></svg>\';
                    handle.draggable = true;
                    handle.addEventListener("dragstart", (e) => this.onDragStart(e, editorId, index));
                    wrapper.appendChild(handle);
                    const content = document.createElement("div");
                    content.className = "ux-block-editor__block-content";
                    this.renderBlockContent(content, block, blockDef, editorId, index);
                    wrapper.appendChild(content);
                    wrapper.addEventListener("click", () => this.selectBlock(editorId, index));
                    return wrapper;
                },
                renderBlockContent(container, block, blockDef, editorId, blockIndex) {
                    if (!blockDef) { container.innerHTML = \'<div class="ux-block-editor__block-unknown">未知 Block: \' + block.blockName + "</div>"; return; }
                    const attrs = block.attributes || {};
                    switch (block.blockName) {
                        case "paragraph": this.renderRichTextBlock(container, attrs, "content", "输入段落文字...", editorId, blockIndex, true); break;
                        case "heading": this.renderHeadingBlock(container, attrs, editorId, blockIndex); break;
                        case "image": this.renderImageBlock(container, attrs, editorId, blockIndex); break;
                        case "quote": this.renderQuoteBlock(container, attrs, editorId, blockIndex); break;
                        case "code": this.renderCodeBlock(container, attrs, editorId, blockIndex); break;
                        case "list": this.renderListBlock(container, attrs, editorId, blockIndex); break;
                        case "divider": this.renderDividerBlock(container); break;
                        default: this.renderGenericBlock(container, block, blockDef, editorId, blockIndex); break;
                    }
                },
                renderRichTextBlock(container, attrs, attrName, placeholder, editorId, blockIndex, hasInline) {
                    const el = document.createElement("div");
                    el.className = "ux-block-editor__editable";
                    el.contentEditable = "true";
                    el.dataset.attr = attrName;
                    el.dataset.blockIndex = blockIndex;
                    const content = attrs[attrName] || [];
                    if (Array.isArray(content) && content.length > 0 && hasInline) {
                        this.currentFormatDefs = this.editors.get(editorId)?.state.formatDefs || {};
                        el.innerHTML = this.segmentsToHtml(content);
                    } else if (typeof content === "string" && content) {
                        el.innerHTML = content;
                    }
                    if (!el.textContent.trim()) {
                        el.dataset.empty = "true";
                        el.textContent = placeholder;
                    }
                    this.bindEditable(el, editorId);
                    container.appendChild(el);
                },
                renderHeadingBlock(container, attrs, editorId, blockIndex) {
                    const level = attrs.level || 2;
                    const levelSelector = document.createElement("select");
                    levelSelector.className = "ux-block-editor__heading-level";
                    levelSelector.dataset.attr = "level";
                    for (let i = 1; i <= 6; i++) { const opt = document.createElement("option"); opt.value = i; opt.textContent = "H" + i; if (i === level) opt.selected = true; levelSelector.appendChild(opt); }
                    this.bindSelect(levelSelector, editorId);
                    container.appendChild(levelSelector);
                    this.renderRichTextBlock(container, attrs, "content", "输入标题...", editorId, blockIndex, true);
                },
                renderImageBlock(container, attrs, editorId, blockIndex) {
                    const wrapper = document.createElement("div");
                    wrapper.className = "ux-block-editor__image-wrapper";
                    if (attrs.src) { const img = document.createElement("img"); img.src = attrs.src; img.alt = attrs.alt || ""; img.className = "ux-block-editor__image-preview"; wrapper.appendChild(img); }
                    ["src", "alt", "caption"].forEach(key => {
                        const input = document.createElement("input");
                        input.type = "text"; input.className = "ux-block-editor__input";
                        input.placeholder = key === "src" ? "图片地址" : key === "alt" ? "替代文字" : "图片说明";
                        input.value = attrs[key] || ""; input.dataset.attr = key;
                        this.bindInput(input, editorId);
                        wrapper.appendChild(input);
                    });
                    container.appendChild(wrapper);
                },
                renderQuoteBlock(container, attrs, editorId, blockIndex) {
                    this.renderRichTextBlock(container, attrs, "content", "输入引用内容...", editorId, blockIndex, true);
                    const citeInput = document.createElement("input");
                    citeInput.type = "text"; citeInput.className = "ux-block-editor__input";
                    citeInput.placeholder = "引用来源"; citeInput.value = attrs.cite || "";
                    citeInput.dataset.attr = "cite"; this.bindInput(citeInput, editorId);
                    container.appendChild(citeInput);
                },
                renderCodeBlock(container, attrs, editorId, blockIndex) {
                    const langInput = document.createElement("input");
                    langInput.type = "text"; langInput.className = "ux-block-editor__input";
                    langInput.placeholder = "语言（如 php, js）"; langInput.value = attrs.language || "";
                    langInput.dataset.attr = "language"; this.bindInput(langInput, editorId);
                    container.appendChild(langInput);
                    const el = document.createElement("pre");
                    el.className = "ux-block-editor__editable ux-block-editor__editable--code";
                    el.contentEditable = "true"; el.dataset.attr = "content";
                    el.textContent = attrs.content || "";
                    if (!attrs.content) { el.dataset.empty = "true"; el.textContent = "输入代码..."; }
                    this.bindEditable(el, editorId);
                    container.appendChild(el);
                },
                renderListBlock(container, attrs, editorId, blockIndex) {
                    const items = attrs.items || [];
                    const toggleWrapper = document.createElement("div");
                    toggleWrapper.className = "ux-block-editor__list-toggle";
                    const orderedCheck = document.createElement("input");
                    orderedCheck.type = "checkbox"; orderedCheck.checked = attrs.ordered || false;
                    orderedCheck.dataset.attr = "ordered";
                    const orderedLabel = document.createElement("label");
                    orderedLabel.appendChild(orderedCheck);
                    orderedLabel.appendChild(document.createTextNode(" 有序列表"));
                    toggleWrapper.appendChild(orderedLabel);
                    this.bindCheckbox(orderedCheck, editorId);
                    container.appendChild(toggleWrapper);
                    const listEl = document.createElement("div");
                    listEl.className = "ux-block-editor__list-items";
                    listEl.dataset.attr = "items";
                    items.forEach((item, i) => listEl.appendChild(this.createListItem(item, i, editorId)));
                    container.appendChild(listEl);
                    const addBtn = document.createElement("button");
                    addBtn.type = "button"; addBtn.className = "ux-block-editor__list-add";
                    addBtn.textContent = "+ 添加列表项";
                    addBtn.addEventListener("click", () => {
                        const idx = listEl.querySelectorAll(".ux-block-editor__list-item").length;
                        listEl.appendChild(this.createListItem("", idx, editorId));
                        this.syncListItems(listEl, editorId);
                    });
                    container.appendChild(addBtn);
                },
                createListItem(text, index, editorId) {
                    const itemEl = document.createElement("div");
                    itemEl.className = "ux-block-editor__list-item";
                    const input = document.createElement("input");
                    input.type = "text"; input.className = "ux-block-editor__input";
                    input.value = text; input.dataset.itemIndex = index;
                    input.addEventListener("input", () => this.syncListItems(input.closest(".ux-block-editor__list-items"), editorId));
                    const removeBtn = document.createElement("button");
                    removeBtn.type = "button"; removeBtn.className = "ux-block-editor__list-item-remove";
                    removeBtn.textContent = "✕";
                    removeBtn.addEventListener("click", () => { itemEl.remove(); this.syncListItems(itemEl.closest(".ux-block-editor__list-items"), editorId); });
                    itemEl.appendChild(input);
                    itemEl.appendChild(removeBtn);
                    return itemEl;
                },
                syncListItems(listEl, editorId) {
                    if (!listEl) return;
                    const wrapperEl = listEl.closest(".ux-block-editor");
                    if (!wrapperEl) return;
                    const eid = wrapperEl.dataset.editorId;
                    const blockEl = listEl.closest(".ux-block-editor__block");
                    const index = parseInt(blockEl.dataset.blockIndex);
                    const items = [];
                    listEl.querySelectorAll(".ux-block-editor__list-item input").forEach(input => items.push(input.value));
                    const editor = this.editors.get(eid);
                    if (editor && editor.state.blocks[index]) {
                        editor.state.blocks[index].attributes.items = items;
                        this.syncToHidden(eid);
                    }
                },
                renderDividerBlock(container) {
                    const hr = document.createElement("hr");
                    hr.className = "ux-block-editor__divider";
                    container.appendChild(hr);
                },
                renderGenericBlock(container, block, blockDef, editorId, blockIndex) {
                    const attrs = block.attributes || {};
                    const attrDefs = blockDef.attributes || {};
                    for (const [attrName, attrDef] of Object.entries(attrDefs)) {
                        const type = attrDef.type || "string";
                        const value = attrs[attrName] ?? attrDef.default ?? "";
                        const fieldWrapper = document.createElement("div");
                        fieldWrapper.className = "ux-block-editor__field";
                        const label = document.createElement("label");
                        label.className = "ux-block-editor__field-label";
                        label.textContent = attrName;
                        fieldWrapper.appendChild(label);
                        let input;
                        if (type === "rich-text") {
                            input = document.createElement("div"); input.className = "ux-block-editor__editable";
                            input.contentEditable = true; input.dataset.attr = attrName;
                            if (Array.isArray(value) && value.length > 0) {
                                this.currentFormatDefs = this.editors.get(editorId)?.state.formatDefs || {};
                                input.innerHTML = this.segmentsToHtml(value);
                            } else if (typeof value === "string") { input.innerHTML = value; }
                            this.bindEditable(input, editorId);
                        } else if (type === "boolean") {
                            input = document.createElement("input"); input.type = "checkbox";
                            input.checked = !!value; input.dataset.attr = attrName;
                            this.bindCheckbox(input, editorId);
                        } else if (type === "number") {
                            input = document.createElement("input"); input.type = "number";
                            input.className = "ux-block-editor__input"; input.value = value;
                            if (attrDef.min !== undefined) input.min = attrDef.min;
                            if (attrDef.max !== undefined) input.max = attrDef.max;
                            input.dataset.attr = attrName; this.bindInput(input, editorId);
                        } else {
                            input = document.createElement("input"); input.type = "text";
                            input.className = "ux-block-editor__input"; input.value = value;
                            input.dataset.attr = attrName; this.bindInput(input, editorId);
                        }
                        fieldWrapper.appendChild(input);
                        container.appendChild(fieldWrapper);
                    }
                },
                segmentsToHtml(segments) {
                    if (!Array.isArray(segments)) return "";
                    return segments.map(seg => {
                        const text = seg.text || "";
                        const formatKeys = Object.keys(seg).filter(k => k !== "text");
                        if (formatKeys.length === 0) return this.escapeHtml(text);
                        let html = this.escapeHtml(text);
                        for (const fmt of formatKeys.reverse()) {
                            const val = seg[fmt];
                            const formatDef = this.currentFormatDefs ? this.currentFormatDefs[fmt] : null;
                            const tag = formatDef ? formatDef.tag : "span";
                            if (typeof val === "object" && val !== null) {
                                let attrStr = "";
                                for (const [k, v] of Object.entries(val)) {
                                    attrStr += " " + k + \'="\' + this.escapeHtml(String(v)) + \'"\';
                                }
                                html = "<" + tag + attrStr + ">" + html + "</" + tag + ">";
                            } else {
                                html = "<" + tag + ">" + html + "</" + tag + ">";
                            }
                        }
                        return html;
                    }).join("");
                },
                htmlToSegments(html) {
                    if (!html) return [{ text: "" }];
                    const temp = document.createElement("div");
                    temp.innerHTML = html;
                    const segments = [];
                    this.walkDomToSegments(temp, {}, segments);
                    if (segments.length === 0) segments.push({ text: "" });
                    return segments;
                },
                walkDomToSegments(node, activeFormats, segments) {
                    for (const child of node.childNodes) {
                        if (child.nodeType === Node.TEXT_NODE) {
                            const text = child.textContent;
                            if (text) {
                                const seg = { text };
                                for (const [name, attrs] of Object.entries(activeFormats)) {
                                    seg[name] = attrs;
                                }
                                segments.push(seg);
                            }
                        } else if (child.nodeType === Node.ELEMENT_NODE) {
                            const fmt = this.domElementToFormat(child);
                            if (fmt) {
                                const newFormats = { ...activeFormats, [fmt.name]: fmt.attrs };
                                this.walkDomToSegments(child, newFormats, segments);
                            } else {
                                this.walkDomToSegments(child, activeFormats, segments);
                            }
                        }
                    }
                },
                domElementToFormat(el) {
                    const tag = el.tagName.toLowerCase();
                    const tagMap = { strong: "bold", b: "bold", em: "italic", i: "italic", u: "underline", s: "strikethrough", del: "strikethrough", code: "code", a: "link" };
                    if (tagMap[tag]) {
                        const name = tagMap[tag];
                        const attrs = {};
                        if (name === "link") {
                            if (el.hasAttribute("href")) attrs.href = el.getAttribute("href");
                            if (el.hasAttribute("target")) attrs.target = el.getAttribute("target");
                        }
                        return { name, attrs };
                    }
                    const formatName = el.dataset.format;
                    if (formatName) {
                        const attrs = {};
                        for (const attr of el.attributes) {
                            if (attr.name.startsWith("data-") && attr.name !== "data-format") attrs[attr.name] = attr.value;
                        }
                        return { name: formatName, attrs };
                    }
                    if (el.classList && el.classList.contains("ux-mention")) {
                        const attrs = {};
                        if (el.dataset.mentionId) attrs["data-mention-id"] = el.dataset.mentionId;
                        return { name: "mention", attrs };
                    }
                    return null;
                },
                escapeHtml(str) {
                    const div = document.createElement("div");
                    div.textContent = str;
                    return div.innerHTML;
                },
                bindEditable(el, editorId) {
                    el.addEventListener("focus", () => {
                        if (el.dataset.empty === "true") { el.textContent = ""; delete el.dataset.empty; }
                        const blockEl = el.closest(".ux-block-editor__block");
                        if (blockEl) { const idx = parseInt(blockEl.dataset.blockIndex); this.selectBlock(editorId, idx); }
                    });
                    el.addEventListener("blur", () => {
                        if (!el.textContent.trim()) {
                            el.dataset.empty = "true";
                            const blockEl = el.closest(".ux-block-editor__block");
                            el.textContent = this.getPlaceholder(blockEl?.dataset.blockName);
                        }
                        this.syncFromEditable(el, editorId);
                    });
                    el.addEventListener("input", () => this.syncFromEditable(el, editorId));
                    el.addEventListener("keydown", (e) => this.handleEditableKeydown(e, el, editorId));
                    el.addEventListener("mouseup", () => setTimeout(() => this.checkSelectionAndShowToolbar(el, editorId), 10));
                    el.addEventListener("keyup", () => setTimeout(() => this.checkSelectionAndShowToolbar(el, editorId), 10));
                },
                handleEditableKeydown(e, el, editorId) {
                    if (e.key === "Enter" && !e.shiftKey) {
                        const blockEl = el.closest(".ux-block-editor__block");
                        if (!blockEl) return;
                        const blockName = blockEl.dataset.blockName;
                        if (blockName === "code") return;
                        e.preventDefault();
                        this.syncFromEditable(el, editorId);
                        const index = parseInt(blockEl.dataset.blockIndex);
                        this.splitBlockAtCursor(editorId, index, el);
                    }
                    if (e.key === "Backspace" && el.textContent.trim() === "") {
                        const blockEl = el.closest(".ux-block-editor__block");
                        if (!blockEl) return;
                        const index = parseInt(blockEl.dataset.blockIndex);
                        if (index > 0) { e.preventDefault(); this.deleteBlock(editorId, index); }
                    }
                    if (e.key === "/") {
                        const sel = window.getSelection();
                        if (sel && sel.isCollapsed && el.textContent.trim() === "") {
                            e.preventDefault();
                            this.showSlashCommand(el, editorId);
                        }
                    }
                },
                splitBlockAtCursor(editorId, blockIndex, el) {
                    const editor = this.editors.get(editorId);
                    if (!editor) return;
                    const block = editor.state.blocks[blockIndex];
                    if (!block) return;
                    const sel = window.getSelection();
                    if (!sel || sel.rangeCount === 0) return;
                    const range = sel.getRangeAt(0);
                    const preRange = document.createRange();
                    preRange.selectNodeContents(el);
                    preRange.setEnd(range.startContainer, range.startOffset);
                    const postRange = document.createRange();
                    postRange.selectNodeContents(el);
                    postRange.setStart(range.endContainer, range.endOffset);
                    const blockDef = editor.state.blockDefs[block.blockName];
                    const hasInline = blockDef && blockDef.supportsInlineFormats;
                    if (hasInline) {
                        this.currentFormatDefs = editor.state.formatDefs;
                        const beforeSegments = this.htmlToSegments(preRange.toString());
                        const afterSegments = this.htmlToSegments(postRange.toString());
                        block.attributes.content = beforeSegments;
                        editor.state.blocks.splice(blockIndex + 1, 0, { blockName: "paragraph", attributes: { content: afterSegments } });
                    } else {
                        block.attributes.content = preRange.toString();
                        editor.state.blocks.splice(blockIndex + 1, 0, { blockName: "paragraph", attributes: { content: postRange.toString() } });
                    }
                    this.renderBlocks(editorId);
                    this.syncToHidden(editorId);
                    setTimeout(() => {
                        const newBlockEl = editor.canvasEl.children[blockIndex + 1];
                        if (newBlockEl) { const editable = newBlockEl.querySelector("[contenteditable]"); if (editable) editable.focus(); }
                    }, 50);
                },
                showSlashCommand(el, editorId) {
                    const existing = document.querySelector(".ux-block-editor__slash-menu");
                    if (existing) existing.remove();
                    const editor = this.editors.get(editorId);
                    if (!editor) return;
                    const menu = document.createElement("div");
                    menu.className = "ux-block-editor__slash-menu";
                    const rect = el.getBoundingClientRect();
                    menu.style.top = rect.bottom + 4 + "px";
                    menu.style.left = rect.left + "px";
                    const categories = {};
                    for (const [name, def] of Object.entries(editor.state.blockDefs)) {
                        const cat = def.category || "common";
                        if (!categories[cat]) categories[cat] = [];
                        categories[cat].push(def);
                    }
                    for (const [cat, blocks] of Object.entries(categories)) {
                        const section = document.createElement("div");
                        section.className = "ux-block-editor__slash-section";
                        const title = document.createElement("div");
                        title.className = "ux-block-editor__slash-section-title";
                        title.textContent = cat;
                        section.appendChild(title);
                        for (const def of blocks) {
                            const item = document.createElement("div");
                            item.className = "ux-block-editor__slash-item";
                            item.dataset.blockName = def.name;
                            item.innerHTML = \'<span class="ux-block-editor__slash-item-icon">\' + (def.icon || "") + \'</span><span class="ux-block-editor__slash-item-label">\' + def.title + \'</span>\';
                            item.addEventListener("mousedown", (e) => {
                                e.preventDefault();
                                const blockEl = el.closest(".ux-block-editor__block");
                                const index = parseInt(blockEl.dataset.blockIndex);
                                this.replaceBlock(editorId, index, def.name);
                                menu.remove();
                            });
                            section.appendChild(item);
                        }
                        menu.appendChild(section);
                    }
                    document.body.appendChild(menu);
                    const closeSlash = (e) => {
                        if (!menu.contains(e.target)) { menu.remove(); document.removeEventListener("mousedown", closeSlash); }
                    };
                    setTimeout(() => document.addEventListener("mousedown", closeSlash), 10);
                },
                replaceBlock(editorId, index, newBlockName) {
                    const editor = this.editors.get(editorId);
                    if (!editor) return;
                    const blockDef = editor.state.blockDefs[newBlockName];
                    const defaultAttrs = blockDef ? (blockDef.defaultAttributes || {}) : {};
                    const newAttrs = Object.assign({}, defaultAttrs);
                    if (newAttrs.content !== undefined && Array.isArray(newAttrs.content)) newAttrs.content = [{ text: "" }];
                    editor.state.blocks[index] = { blockName: newBlockName, attributes: newAttrs };
                    this.renderBlocks(editorId);
                    this.syncToHidden(editorId);
                    setTimeout(() => {
                        const blockEl = editor.canvasEl.children[index];
                        if (blockEl) { const editable = blockEl.querySelector("[contenteditable]"); if (editable) editable.focus(); }
                    }, 50);
                },
                checkSelectionAndShowToolbar(el, editorId) {
                    const sel = window.getSelection();
                    if (!sel || sel.isCollapsed || sel.rangeCount === 0) { this.hideFormatToolbar(); return; }
                    const range = sel.getRangeAt(0);
                    if (!el.contains(range.commonAncestorContainer)) { this.hideFormatToolbar(); return; }
                    const blockEl = el.closest(".ux-block-editor__block");
                    if (!blockEl) return;
                    const editor = this.editors.get(editorId);
                    if (!editor) return;
                    const blockDef = editor.state.blockDefs[blockEl.dataset.blockName];
                    if (!blockDef || !blockDef.supportsInlineFormats) return;
                    this.showFormatToolbar(sel, range, el, editorId);
                },
                showFormatToolbar(sel, range, el, editorId) {
                    let toolbar = document.querySelector(".ux-block-editor__format-toolbar");
                    if (!toolbar) {
                        toolbar = document.createElement("div");
                        toolbar.className = "ux-block-editor__format-toolbar";
                        document.body.appendChild(toolbar);
                    }
                    const editor = this.editors.get(editorId);
                    this.currentFormatDefs = editor.state.formatDefs;
                    toolbar.innerHTML = "";
                    for (const [name, fmt] of Object.entries(editor.state.formatDefs)) {
                        const btn = document.createElement("button");
                        btn.type = "button";
                        btn.className = "ux-block-editor__format-btn";
                        btn.dataset.format = name;
                        btn.innerHTML = fmt.icon || fmt.title;
                        btn.title = fmt.title;
                        btn.addEventListener("mousedown", (e) => {
                            e.preventDefault();
                            this.toggleFormat(name, fmt, el, editorId);
                        });
                        toolbar.appendChild(btn);
                    }
                    const rect = range.getBoundingClientRect();
                    toolbar.style.top = (rect.top - 40) + "px";
                    toolbar.style.left = (rect.left + rect.width / 2) + "px";
                    toolbar.style.transform = "translateX(-50%)";
                    toolbar.classList.add("ux-block-editor__format-toolbar--visible");
                },
                hideFormatToolbar() {
                    const toolbar = document.querySelector(".ux-block-editor__format-toolbar");
                    if (toolbar) toolbar.classList.remove("ux-block-editor__format-toolbar--visible");
                },
                toggleFormat(formatName, formatDef, el, editorId) {
                    const sel = window.getSelection();
                    if (!sel || sel.rangeCount === 0 || sel.isCollapsed) return;
                    const range = sel.getRangeAt(0);
                    const tag = formatDef.tag || "span";
                    const formatEl = document.createElement(tag);
                    if (formatName !== tag) formatEl.dataset.format = formatName;
                    if (formatName === "link") {
                        const href = prompt("输入链接地址:", "https://");
                        if (!href) return;
                        formatEl.href = href;
                        formatEl.target = "_blank";
                    }
                    try { range.surroundContents(formatEl); } catch(e) {
                        const fragment = range.extractContents();
                        formatEl.appendChild(fragment);
                        range.insertNode(formatEl);
                    }
                    this.syncFromEditable(el, editorId);
                },
                bindInput(input, editorId) { input.addEventListener("input", () => this.syncFromInput(input, editorId)); },
                bindSelect(select, editorId) { select.addEventListener("change", () => this.syncFromSelect(select, editorId)); },
                bindCheckbox(checkbox, editorId) { checkbox.addEventListener("change", () => this.syncFromCheckbox(checkbox, editorId)); },
                getPlaceholder(blockName) {
                    return { paragraph: "输入段落文字...", heading: "输入标题...", quote: "输入引用内容...", code: "输入代码..." }[blockName] || "输入内容...";
                },
                syncFromEditable(el, editorId) {
                    const blockEl = el.closest(".ux-block-editor__block");
                    if (!blockEl) return;
                    const eid = blockEl.closest(".ux-block-editor").dataset.editorId;
                    const index = parseInt(blockEl.dataset.blockIndex);
                    const attrName = el.dataset.attr;
                    const editor = this.editors.get(eid);
                    if (!editor || !editor.state.blocks[index]) return;
                    const block = editor.state.blocks[index];
                    const blockDef = editor.state.blockDefs[block.blockName];
                    const hasInline = blockDef && blockDef.supportsInlineFormats && attrName === "content";
                    if (hasInline) {
                        this.currentFormatDefs = editor.state.formatDefs;
                        block.attributes[attrName] = this.htmlToSegments(el.innerHTML);
                    } else {
                        block.attributes[attrName] = el.innerHTML;
                    }
                    this.syncToHidden(eid);
                },
                syncFromInput(input, editorId) {
                    const blockEl = input.closest(".ux-block-editor__block");
                    if (!blockEl) return;
                    const eid = blockEl.closest(".ux-block-editor").dataset.editorId;
                    const index = parseInt(blockEl.dataset.blockIndex);
                    const attrName = input.dataset.attr;
                    const editor = this.editors.get(eid);
                    if (editor && editor.state.blocks[index]) {
                        editor.state.blocks[index].attributes[attrName] = input.value;
                        this.syncToHidden(eid);
                    }
                },
                syncFromSelect(select, editorId) {
                    const blockEl = select.closest(".ux-block-editor__block");
                    if (!blockEl) return;
                    const eid = blockEl.closest(".ux-block-editor").dataset.editorId;
                    const index = parseInt(blockEl.dataset.blockIndex);
                    const attrName = select.dataset.attr;
                    const editor = this.editors.get(eid);
                    if (editor && editor.state.blocks[index]) {
                        editor.state.blocks[index].attributes[attrName] = parseInt(select.value);
                        this.syncToHidden(eid);
                    }
                },
                syncFromCheckbox(checkbox, editorId) {
                    const blockEl = checkbox.closest(".ux-block-editor__block");
                    if (!blockEl) return;
                    const eid = blockEl.closest(".ux-block-editor").dataset.editorId;
                    const index = parseInt(blockEl.dataset.blockIndex);
                    const attrName = checkbox.dataset.attr;
                    const editor = this.editors.get(eid);
                    if (editor && editor.state.blocks[index]) {
                        editor.state.blocks[index].attributes[attrName] = checkbox.checked;
                        this.syncToHidden(eid);
                    }
                },
                syncToHidden(editorId) {
                    const editor = this.editors.get(editorId);
                    if (!editor || !editor.hiddenInput) return;
                    const json = JSON.stringify(editor.state.blocks);
                    if (editor.hiddenInput.value !== json) {
                        editor.hiddenInput.value = json;
                        editor.hiddenInput.dispatchEvent(new Event("input", { bubbles: true }));
                        editor.hiddenInput.dispatchEvent(new Event("change", { bubbles: true }));
                    }
                },
                selectBlock(editorId, index) {
                    const editor = this.editors.get(editorId);
                    if (!editor) return;
                    editor.state.selectedBlockIndex = index;
                    editor.canvasEl.querySelectorAll(".ux-block-editor__block").forEach((el, i) => {
                        el.classList.toggle("ux-block-editor__block--selected", i === index);
                    });
                },
                insertBlock(editorId, blockName) {
                    const editor = this.editors.get(editorId);
                    if (!editor) return;
                    const blockDef = editor.state.blockDefs[blockName];
                    const defaultAttrs = blockDef ? (blockDef.defaultAttributes || {}) : {};
                    const newAttrs = Object.assign({}, defaultAttrs);
                    if (newAttrs.content !== undefined && Array.isArray(newAttrs.content)) newAttrs.content = [{ text: "" }];
                    const newBlock = { blockName, attributes: newAttrs };
                    const insertIndex = editor.state.selectedBlockIndex >= 0 ? editor.state.selectedBlockIndex + 1 : editor.state.blocks.length;
                    editor.state.blocks.splice(insertIndex, 0, newBlock);
                    editor.state.selectedBlockIndex = insertIndex;
                    this.renderBlocks(editorId);
                    this.syncToHidden(editorId);
                    const inserterPanel = editor.wrapperEl.querySelector(".ux-block-editor__inserter-panel");
                    if (inserterPanel) inserterPanel.classList.remove("ux-block-editor__inserter-panel--open");
                },
                deleteBlock(editorId, index) {
                    const editor = this.editors.get(editorId);
                    if (!editor) return;
                    editor.state.blocks.splice(index, 1);
                    if (editor.state.selectedBlockIndex >= editor.state.blocks.length) editor.state.selectedBlockIndex = editor.state.blocks.length - 1;
                    this.renderBlocks(editorId);
                    this.syncToHidden(editorId);
                },
                moveBlock(editorId, index, direction) {
                    const editor = this.editors.get(editorId);
                    if (!editor) return;
                    const newIndex = index + direction;
                    if (newIndex < 0 || newIndex >= editor.state.blocks.length) return;
                    const temp = editor.state.blocks[index];
                    editor.state.blocks[index] = editor.state.blocks[newIndex];
                    editor.state.blocks[newIndex] = temp;
                    editor.state.selectedBlockIndex = newIndex;
                    this.renderBlocks(editorId);
                    this.syncToHidden(editorId);
                },
                onDragStart(e, editorId, index) {
                    e.dataTransfer.setData("text/plain", index.toString());
                    e.dataTransfer.effectAllowed = "move";
                },
                onDragOver(e) { e.preventDefault(); e.dataTransfer.dropEffect = "move"; },
                onDrop(e, editorId) {
                    e.preventDefault();
                    const fromIndex = parseInt(e.dataTransfer.getData("text/plain"));
                    const blockEl = e.target.closest(".ux-block-editor__block");
                    if (!blockEl) return;
                    const toIndex = parseInt(blockEl.dataset.blockIndex);
                    if (fromIndex === toIndex) return;
                    const editor = this.editors.get(editorId);
                    if (!editor) return;
                    const block = editor.state.blocks.splice(fromIndex, 1)[0];
                    editor.state.blocks.splice(toIndex, 0, block);
                    editor.state.selectedBlockIndex = toIndex;
                    this.renderBlocks(editorId);
                    this.syncToHidden(editorId);
                },
                bindGlobalEvents(editorId) {
                    const editor = this.editors.get(editorId);
                    if (!editor) return;
                    const toggleBtn = editor.wrapperEl.querySelector(".ux-block-editor__inserter-toggle");
                    const panel = editor.wrapperEl.querySelector(".ux-block-editor__inserter-panel");
                    if (toggleBtn && panel) {
                        toggleBtn.addEventListener("click", () => panel.classList.toggle("ux-block-editor__inserter-panel--open"));
                    }
                    editor.wrapperEl.addEventListener("click", (e) => {
                        const item = e.target.closest(".ux-block-editor__inserter-item");
                        if (!item) return;
                        const blockName = item.dataset.blockName;
                        if (blockName) this.insertBlock(editorId, blockName);
                    });
                    editor.canvasEl.addEventListener("dragover", (e) => this.onDragOver(e));
                    editor.canvasEl.addEventListener("drop", (e) => this.onDrop(e, editorId));
                    document.addEventListener("selectionchange", () => {
                        const sel = window.getSelection();
                        if (!sel || sel.isCollapsed) this.hideFormatToolbar();
                    });
                },
                getValue(editorId) { const editor = this.editors.get(editorId); return editor ? JSON.stringify(editor.state.blocks) : ""; },
                setValue(editorId, value) {
                    const editor = this.editors.get(editorId);
                    if (!editor) return;
                    try { editor.state.blocks = JSON.parse(value); } catch(e) { editor.state.blocks = []; }
                    this.renderBlocks(editorId);
                    this.syncToHidden(editorId);
                },
                liveHandler(op) {
                    if (op.action === "setValue" && op.id) this.setValue(op.id, op.value);
                }
            };
            return BlockEditor;
        ');
    }

    public function allowedBlocks(array $blocks): static
    {
        $this->allowedBlocks = $blocks;
        return $this;
    }

    public function placeholder(string $placeholder): static
    {
        $this->placeholder = $placeholder;
        return $this;
    }

    public function width(string $width): static
    {
        $this->width = $width;
        return $this;
    }

    public function minHeight(string $height): static
    {
        $this->minHeight = $height;
        return $this;
    }

    public function addBlockType(string $name, BlockType $blockType): static
    {
        $this->customBlockTypes[$name] = $blockType;
        BlockRegistry::register($name, $blockType);
        return $this;
    }

    public function addExtension(string $name, RichEditorExtension $extension): static
    {
        $extension->asBlock();
        $this->blockExtensions[$name] = $extension;
        BlockRegistry::register($name, $extension->toBlockType());
        return $this;
    }

    protected function getBlockDefinitions(): array
    {
        if (!empty($this->allowedBlocks)) {
            $definitions = [];
            foreach ($this->allowedBlocks as $blockName) {
                $blockType = BlockRegistry::get($blockName);
                if ($blockType) {
                    $definitions[$blockName] = $blockType->toArray();
                }
            }
            return $definitions;
        }

        return BlockRegistry::allDefinitions();
    }

    protected function toElement(): Element
    {
        $groupEl = Element::make('div')->class('ux-form-group');

        if ($this->width) {
            $groupEl->style("width: {$this->width}");
        }

        $labelEl = $this->renderLabel();
        if ($labelEl) {
            $groupEl->child($labelEl);
        }

        $editorId = $this->name . '-block-editor';
        $inputId = $this->name;

        $blockDefinitions = $this->getBlockDefinitions();

        $wrapperEl = Element::make('div')
            ->class('ux-block-editor')
            ->data('editor-id', $editorId)
            ->data('blocks', json_encode($blockDefinitions, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES))
            ->data('formats', json_encode(InlineFormatRegistry::allDefinitions(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        if ($this->placeholder) {
            $wrapperEl->data('placeholder', $this->placeholder);
        }

        if ($this->minHeight) {
            $wrapperEl->style("min-height: {$this->minHeight}");
        }

        $canvasEl = Element::make('div')
            ->class('ux-block-editor__canvas')
            ->attr('id', $editorId);

        $value = $this->value ?? '';
        if ($value) {
            $blocks = BlockRegistry::parse((string)$value);
            $canvasEl->data('initial-blocks', json_encode($blocks, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        }

        $wrapperEl->child($canvasEl);

        $inserterEl = $this->buildInserter($blockDefinitions);
        $wrapperEl->child($inserterEl);

        $hiddenInput = Element::make('input')
            ->attr('type', 'hidden')
            ->attr('id', $inputId)
            ->attr('name', $this->name);

        if ($this->value !== null) {
            $hiddenInput->attr('value', (string)$this->value);
        }

        foreach ($this->buildFieldAttrs() as $key => $value) {
            if (!in_array($key, ['id', 'name'])) {
                $hiddenInput->attr($key, $value);
            }
        }

        $wrapperEl->child($hiddenInput);
        $groupEl->child($wrapperEl);

        $helpEl = $this->renderHelp();
        if ($helpEl) {
            $groupEl->child($helpEl);
        }

        return $groupEl;
    }

    protected function buildInserter(array $blockDefinitions): Element
    {
        $inserterEl = Element::make('div')->class('ux-block-editor__inserter');

        $toggleBtn = Element::make('button')
            ->class('ux-block-editor__inserter-toggle')
            ->attr('type', 'button')
            ->html('<svg viewBox="0 0 24 24" width="20" height="20"><path fill="currentColor" d="M19 13h-6v6h-2v-6H5v-2h6V5h2v6h6v2z"/></svg>');

        $inserterEl->child($toggleBtn);

        $panelEl = Element::make('div')->class('ux-block-editor__inserter-panel');

        $categories = [];
        foreach ($blockDefinitions as $name => $def) {
            $cat = $def['category'] ?? 'common';
            if (!isset($categories[$cat])) {
                $categories[$cat] = [];
            }
            $categories[$cat][] = $def;
        }

        foreach ($categories as $category => $blocks) {
            $sectionEl = Element::make('div')->class('ux-block-editor__inserter-section');
            $sectionEl->child(
                Element::make('div')
                    ->class('ux-block-editor__inserter-section-title')
                    ->text(ucfirst($category))
            );

            $gridEl = Element::make('div')->class('ux-block-editor__inserter-grid');

            foreach ($blocks as $blockDef) {
                $itemEl = Element::make('button')
                    ->class('ux-block-editor__inserter-item')
                    ->attr('type', 'button')
                    ->data('block-name', $blockDef['name']);

                if (!empty($blockDef['icon'])) {
                    $iconEl = Element::make('span')
                        ->class('ux-block-editor__inserter-item-icon')
                        ->html($blockDef['icon']);
                    $itemEl->child($iconEl);
                }

                $labelEl = Element::make('span')
                    ->class('ux-block-editor__inserter-item-label')
                    ->text($blockDef['title'] ?: $blockDef['name']);
                $itemEl->child($labelEl);

                $gridEl->child($itemEl);
            }

            $sectionEl->child($gridEl);
            $panelEl->child($sectionEl);
        }

        $inserterEl->child($panelEl);

        return $inserterEl;
    }
}
