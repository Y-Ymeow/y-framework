<?php

declare(strict_types=1);

namespace Framework\UX\Form;

use Framework\View\Base\Element;
use Framework\View\Document\AssetRegistry;
use Framework\UX\RichEditor\BlockRegistry;
use Framework\UX\RichEditor\BlockType;
use Framework\UX\RichEditor\RichEditorExtension;
use Framework\CSS\RichEditorRules;

/**
 * Block 编辑器
 *
 * Gutenberg 风格的 Block 编辑器，Block 类型在 PHP 端注册，
 * 前端动态获取定义并渲染编辑界面。
 *
 * @ux-category Form
 * @ux-since 2.0.0
 * @ux-example
 * BlockEditor::make()->name('content')->label('内容')
 *     ->allowedBlocks(['paragraph', 'heading', 'image', 'quote', 'code', 'list'])
 * @ux-example
 * BlockEditor::make()->name('article')->addBlockType('callout', BlockType::make('callout')
 *     ->title('标注')
 *     ->category('common')
 *     ->attribute('text', ['type' => 'string', 'default' => ''])
 *     ->attribute('type', ['type' => 'string', 'default' => 'info'])
 *     ->withRenderElement(function($attrs) {
 *         return Element::make('div')
 *             ->class('callout', 'callout--' . ($attrs['type'] ?? 'info'))
 *             ->text($attrs['text'] ?? '');
 *     })
 * )
 */
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
                    const canvasEl = wrapperEl.querySelector(".ux-block-editor__canvas");
                    const hiddenInput = wrapperEl.querySelector("input[type=hidden]");
                    const blockDefs = JSON.parse(wrapperEl.dataset.blocks || "{}");
                    const placeholder = wrapperEl.dataset.placeholder || "";
                    const state = { blocks: [], selectedBlockIndex: -1, blockDefs, placeholder };
                    const initialBlocks = canvasEl.dataset.initialBlocks;
                    if (initialBlocks) { try { state.blocks = JSON.parse(initialBlocks); } catch(e) { state.blocks = []; } }
                    if (state.blocks.length === 0 && placeholder) { state.blocks.push({ blockName: "paragraph", attributes: { content: "" } }); }
                    this.editors.set(editorId, { wrapperEl, canvasEl, hiddenInput, state });
                    this.renderBlocks(editorId);
                    this.bindInserter(editorId);
                },
                renderBlocks(editorId) {
                    const editor = this.editors.get(editorId);
                    if (!editor) return;
                    const { canvasEl, state } = editor;
                    canvasEl.innerHTML = "";
                    if (state.blocks.length === 0) {
                        const emptyEl = document.createElement("div");
                        emptyEl.className = "ux-block-editor__empty";
                        emptyEl.textContent = state.placeholder || "点击 + 添加内容块";
                        canvasEl.appendChild(emptyEl);
                        return;
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
                    const toolbar = document.createElement("div");
                    toolbar.className = "ux-block-editor__block-toolbar";
                    toolbar.innerHTML = \'<span class="ux-block-editor__block-type">\' + (blockDef ? blockDef.title : block.blockName) + \'</span><div class="ux-block-editor__block-actions"><button type="button" class="ux-block-editor__block-btn" data-action="move-up" title="上移">↑</button><button type="button" class="ux-block-editor__block-btn" data-action="move-down" title="下移">↓</button><button type="button" class="ux-block-editor__block-btn" data-action="delete" title="删除">✕</button></div>\';
                    const content = document.createElement("div");
                    content.className = "ux-block-editor__block-content";
                    this.renderBlockContent(content, block, blockDef);
                    wrapper.appendChild(toolbar);
                    wrapper.appendChild(content);
                    wrapper.addEventListener("click", () => this.selectBlock(editorId, index));
                    toolbar.addEventListener("click", (e) => {
                        const btn = e.target.closest("[data-action]");
                        if (!btn) return;
                        e.stopPropagation();
                        const action = btn.dataset.action;
                        if (action === "move-up") this.moveBlock(editorId, index, -1);
                        else if (action === "move-down") this.moveBlock(editorId, index, 1);
                        else if (action === "delete") this.deleteBlock(editorId, index);
                    });
                    return wrapper;
                },
                renderBlockContent(container, block, blockDef) {
                    if (!blockDef) { container.innerHTML = \'<div class="ux-block-editor__block-unknown">未知 Block: \' + block.blockName + "</div>"; return; }
                    const attrs = block.attributes || {};
                    switch (block.blockName) {
                        case "paragraph": this.renderParagraphBlock(container, attrs); break;
                        case "heading": this.renderHeadingBlock(container, attrs); break;
                        case "image": this.renderImageBlock(container, attrs); break;
                        case "quote": this.renderQuoteBlock(container, attrs); break;
                        case "code": this.renderCodeBlock(container, attrs); break;
                        case "list": this.renderListBlock(container, attrs); break;
                        case "divider": this.renderDividerBlock(container); break;
                        default: this.renderGenericBlock(container, block, blockDef); break;
                    }
                },
                renderParagraphBlock(container, attrs) {
                    const el = document.createElement("div");
                    el.className = "ux-block-editor__editable";
                    el.contentEditable = true;
                    el.dataset.attr = "content";
                    el.innerHTML = attrs.content || "";
                    if (!attrs.content) { el.dataset.empty = "true"; el.textContent = "输入段落文字..."; }
                    this.bindEditable(el);
                    container.appendChild(el);
                },
                renderHeadingBlock(container, attrs) {
                    const level = attrs.level || 2;
                    const levelSelector = document.createElement("select");
                    levelSelector.className = "ux-block-editor__heading-level";
                    levelSelector.dataset.attr = "level";
                    for (let i = 1; i <= 6; i++) { const opt = document.createElement("option"); opt.value = i; opt.textContent = "H" + i; if (i === level) opt.selected = true; levelSelector.appendChild(opt); }
                    this.bindSelect(levelSelector);
                    container.appendChild(levelSelector);
                    const el = document.createElement("div");
                    el.className = "ux-block-editor__editable ux-block-editor__editable--heading";
                    el.contentEditable = true;
                    el.dataset.attr = "content";
                    el.innerHTML = attrs.content || "";
                    if (!attrs.content) { el.dataset.empty = "true"; el.textContent = "输入标题..."; }
                    this.bindEditable(el);
                    container.appendChild(el);
                },
                renderImageBlock(container, attrs) {
                    const wrapper = document.createElement("div");
                    wrapper.className = "ux-block-editor__image-wrapper";
                    if (attrs.src) { const img = document.createElement("img"); img.src = attrs.src; img.alt = attrs.alt || ""; img.className = "ux-block-editor__image-preview"; wrapper.appendChild(img); }
                    ["src", "alt", "caption"].forEach(key => {
                        const input = document.createElement("input");
                        input.type = "text"; input.className = "ux-block-editor__input";
                        input.placeholder = key === "src" ? "图片地址" : key === "alt" ? "替代文字" : "图片说明";
                        input.value = attrs[key] || ""; input.dataset.attr = key;
                        this.bindInput(input);
                        wrapper.appendChild(input);
                    });
                    container.appendChild(wrapper);
                },
                renderQuoteBlock(container, attrs) {
                    const el = document.createElement("blockquote");
                    el.className = "ux-block-editor__editable ux-block-editor__editable--quote";
                    el.contentEditable = true; el.dataset.attr = "content";
                    el.innerHTML = attrs.content || "";
                    if (!attrs.content) { el.dataset.empty = "true"; el.textContent = "输入引用内容..."; }
                    this.bindEditable(el);
                    container.appendChild(el);
                    const citeInput = document.createElement("input");
                    citeInput.type = "text"; citeInput.className = "ux-block-editor__input";
                    citeInput.placeholder = "引用来源"; citeInput.value = attrs.citation || "";
                    citeInput.dataset.attr = "citation"; this.bindInput(citeInput);
                    container.appendChild(citeInput);
                },
                renderCodeBlock(container, attrs) {
                    const langInput = document.createElement("input");
                    langInput.type = "text"; langInput.className = "ux-block-editor__input";
                    langInput.placeholder = "语言（如 php, js）"; langInput.value = attrs.language || "";
                    langInput.dataset.attr = "language"; this.bindInput(langInput);
                    container.appendChild(langInput);
                    const el = document.createElement("pre");
                    el.className = "ux-block-editor__editable ux-block-editor__editable--code";
                    el.contentEditable = true; el.dataset.attr = "content";
                    el.textContent = attrs.content || "";
                    if (!attrs.content) { el.dataset.empty = "true"; el.textContent = "输入代码..."; }
                    this.bindEditable(el);
                    container.appendChild(el);
                },
                renderListBlock(container, attrs) {
                    const items = attrs.items || [];
                    const toggleWrapper = document.createElement("div");
                    toggleWrapper.className = "ux-block-editor__list-toggle";
                    const orderedLabel = document.createElement("label");
                    const orderedCheck = document.createElement("input");
                    orderedCheck.type = "checkbox"; orderedCheck.checked = attrs.ordered || false;
                    orderedCheck.dataset.attr = "ordered";
                    orderedLabel.appendChild(orderedCheck);
                    orderedLabel.appendChild(document.createTextNode(" 有序列表"));
                    toggleWrapper.appendChild(orderedLabel);
                    this.bindCheckbox(orderedCheck);
                    container.appendChild(toggleWrapper);
                    const listEl = document.createElement("div");
                    listEl.className = "ux-block-editor__list-items";
                    listEl.dataset.attr = "items";
                    items.forEach((item, i) => listEl.appendChild(this.createListItem(item, i)));
                    container.appendChild(listEl);
                    const addBtn = document.createElement("button");
                    addBtn.type = "button"; addBtn.className = "ux-block-editor__list-add";
                    addBtn.textContent = "+ 添加列表项";
                    addBtn.addEventListener("click", () => {
                        const idx = listEl.querySelectorAll(".ux-block-editor__list-item").length;
                        listEl.appendChild(this.createListItem("", idx));
                        this.syncListItems(listEl);
                    });
                    container.appendChild(addBtn);
                },
                createListItem(text, index) {
                    const itemEl = document.createElement("div");
                    itemEl.className = "ux-block-editor__list-item";
                    const input = document.createElement("input");
                    input.type = "text"; input.className = "ux-block-editor__input";
                    input.value = text; input.dataset.itemIndex = index;
                    input.addEventListener("input", () => this.syncListItems(input.closest(".ux-block-editor__list-items")));
                    const removeBtn = document.createElement("button");
                    removeBtn.type = "button"; removeBtn.className = "ux-block-editor__list-item-remove";
                    removeBtn.textContent = "✕";
                    removeBtn.addEventListener("click", () => { itemEl.remove(); this.syncListItems(itemEl.closest(".ux-block-editor__list-items")); });
                    itemEl.appendChild(input);
                    itemEl.appendChild(removeBtn);
                    return itemEl;
                },
                syncListItems(listEl) {
                    if (!listEl) return;
                    const editorId = listEl.closest(".ux-block-editor").dataset.editorId;
                    const blockEl = listEl.closest(".ux-block-editor__block");
                    const index = parseInt(blockEl.dataset.blockIndex);
                    const items = [];
                    listEl.querySelectorAll(".ux-block-editor__list-item input").forEach(input => items.push(input.value));
                    const editor = this.editors.get(editorId);
                    if (editor && editor.state.blocks[index]) {
                        editor.state.blocks[index].attributes.items = items;
                        this.syncToHidden(editorId);
                    }
                },
                renderDividerBlock(container) {
                    const hr = document.createElement("hr");
                    hr.className = "ux-block-editor__divider";
                    container.appendChild(hr);
                },
                renderGenericBlock(container, block, blockDef) {
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
                        if (type === "boolean") {
                            input = document.createElement("input"); input.type = "checkbox";
                            input.checked = !!value; input.dataset.attr = attrName;
                            this.bindCheckbox(input);
                        } else if (type === "rich-text") {
                            input = document.createElement("div"); input.className = "ux-block-editor__editable";
                            input.contentEditable = true; input.dataset.attr = attrName;
                            input.innerHTML = value || ""; this.bindEditable(input);
                        } else if (type === "number") {
                            input = document.createElement("input"); input.type = "number";
                            input.className = "ux-block-editor__input"; input.value = value;
                            if (attrDef.min !== undefined) input.min = attrDef.min;
                            if (attrDef.max !== undefined) input.max = attrDef.max;
                            input.dataset.attr = attrName; this.bindInput(input);
                        } else {
                            input = document.createElement("input"); input.type = "text";
                            input.className = "ux-block-editor__input"; input.value = value;
                            input.dataset.attr = attrName; this.bindInput(input);
                        }
                        fieldWrapper.appendChild(input);
                        container.appendChild(fieldWrapper);
                    }
                },
                bindEditable(el) {
                    el.addEventListener("focus", () => { if (el.dataset.empty === "true") { el.textContent = ""; delete el.dataset.empty; } });
                    el.addEventListener("blur", () => {
                        if (!el.textContent.trim()) {
                            el.dataset.empty = "true";
                            const blockEl = el.closest(".ux-block-editor__block");
                            el.textContent = this.getPlaceholder(blockEl?.dataset.blockName);
                        }
                        this.syncFromEditable(el);
                    });
                    el.addEventListener("input", () => this.syncFromEditable(el));
                },
                bindInput(input) { input.addEventListener("input", () => this.syncFromInput(input)); },
                bindSelect(select) { select.addEventListener("change", () => this.syncFromSelect(select)); },
                bindCheckbox(checkbox) { checkbox.addEventListener("change", () => this.syncFromCheckbox(checkbox)); },
                getPlaceholder(blockName) {
                    return { paragraph: "输入段落文字...", heading: "输入标题...", quote: "输入引用内容...", code: "输入代码..." }[blockName] || "输入内容...";
                },
                syncFromEditable(el) {
                    const blockEl = el.closest(".ux-block-editor__block");
                    if (!blockEl) return;
                    const editorId = blockEl.closest(".ux-block-editor").dataset.editorId;
                    const index = parseInt(blockEl.dataset.blockIndex);
                    const attrName = el.dataset.attr;
                    const editor = this.editors.get(editorId);
                    if (editor && editor.state.blocks[index]) {
                        editor.state.blocks[index].attributes[attrName] = el.innerHTML;
                        this.syncToHidden(editorId);
                    }
                },
                syncFromInput(input) {
                    const blockEl = input.closest(".ux-block-editor__block");
                    if (!blockEl) return;
                    const editorId = blockEl.closest(".ux-block-editor").dataset.editorId;
                    const index = parseInt(blockEl.dataset.blockIndex);
                    const attrName = input.dataset.attr;
                    const editor = this.editors.get(editorId);
                    if (editor && editor.state.blocks[index]) {
                        editor.state.blocks[index].attributes[attrName] = input.value;
                        this.syncToHidden(editorId);
                    }
                },
                syncFromSelect(select) {
                    const blockEl = select.closest(".ux-block-editor__block");
                    if (!blockEl) return;
                    const editorId = blockEl.closest(".ux-block-editor").dataset.editorId;
                    const index = parseInt(blockEl.dataset.blockIndex);
                    const attrName = select.dataset.attr;
                    const editor = this.editors.get(editorId);
                    if (editor && editor.state.blocks[index]) {
                        editor.state.blocks[index].attributes[attrName] = parseInt(select.value);
                        this.syncToHidden(editorId);
                    }
                },
                syncFromCheckbox(checkbox) {
                    const blockEl = checkbox.closest(".ux-block-editor__block");
                    if (!blockEl) return;
                    const editorId = blockEl.closest(".ux-block-editor").dataset.editorId;
                    const index = parseInt(blockEl.dataset.blockIndex);
                    const attrName = checkbox.dataset.attr;
                    const editor = this.editors.get(editorId);
                    if (editor && editor.state.blocks[index]) {
                        editor.state.blocks[index].attributes[attrName] = checkbox.checked;
                        this.syncToHidden(editorId);
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
                    const newBlock = { blockName, attributes: Object.assign({}, defaultAttrs) };
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
                bindInserter(editorId) {
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
                    else if (op.action === "getValue" && op.id) this.getValue(op.id);
                    else if (op.action === "insertBlock" && op.editorId) this.insertBlock(op.editorId, op.blockName);
                    else if (typeof this[op.action] === "function") this[op.action](op.id, op.value);
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
            ->data('blocks', json_encode($blockDefinitions, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

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
