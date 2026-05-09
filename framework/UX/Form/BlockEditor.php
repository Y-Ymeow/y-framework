<?php

declare(strict_types=1);

namespace Framework\UX\Form;

use Framework\View\Base\Element;
use Framework\View\Document\AssetRegistry;
use Framework\CSS\BlockEditorRules;

/**
 * 区块编辑器 (Block Editor)
 * 
 * 类似 Gutenberg 的区块化编辑器，支持左侧主编辑区和右侧属性面板。
 * 数据以 JSON 格式存储。
 *
 * @ux-category Form
 * @ux-since 1.1.0
 */
class BlockEditor extends FormField
{
    protected static ?string $componentName = 'blockEditor';

    protected array $allowedBlocks = ['paragraph', 'heading', 'image', 'quote', 'list', 'code', 'spacer'];
    protected ?string $height = '600px';

    public function __construct()
    {
        parent::__construct();
        AssetRegistry::getInstance()->inlineStyle('ux:block-editor', BlockEditorRules::getStyles());
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
                initEditor(el) {
                    const editorId = el.dataset.editorId;
                    const hiddenInput = el.querySelector("input[type=\"hidden\"]");
                    const canvas = el.querySelector(".ux-block-editor-canvas");
                    const sidebar = el.querySelector(".ux-block-editor-sidebar-content");
                    
                    let blocks = [];
                    try {
                        blocks = JSON.parse(hiddenInput.value || "[]");
                    } catch(e) { blocks = []; }

                    const editor = {
                        el,
                        hiddenInput,
                        canvas,
                        sidebar,
                        blocks,
                        selectedUid: null,
                        
                        render() {
                            canvas.innerHTML = "";
                            if (this.blocks.length === 0) {
                                const empty = document.createElement("div");
                                empty.className = "ux-block-editor-empty";
                                empty.innerHTML = "<p>开始添加区块...</p>";
                                canvas.appendChild(empty);
                            }
                            this.blocks.forEach((block, index) => {
                                canvas.appendChild(this.createBlockElement(block, index));
                            });
                            this.updateInput();
                        },
                        
                        createBlockElement(block, index) {
                            const bEl = document.createElement("div");
                            bEl.className = "ux-block-item" + (this.selectedUid === block.uid ? " is-selected" : "");
                            bEl.dataset.uid = block.uid;
                            
                            const toolbar = document.createElement("div");
                            toolbar.className = "ux-block-item-toolbar";
                            toolbar.innerHTML = `
                                <div class="ux-block-item-type">${block.type}</div>
                                <div class="ux-block-item-actions">
                                    <button type="button" class="ux-block-item-action" data-action="move-up"><i class="bi bi-arrow-up"></i></button>
                                    <button type="button" class="ux-block-item-action" data-action="move-down"><i class="bi bi-arrow-down"></i></button>
                                    <button type="button" class="ux-block-item-action" data-action="delete"><i class="bi bi-trash"></i></button>
                                </div>
                            `;
                            
                            const content = document.createElement("div");
                            content.className = "ux-block-item-content";
                            
                            if (block.type === "paragraph") {
                                content.contentEditable = "true";
                                content.innerHTML = block.content || "";
                                content.oninput = () => { block.content = content.innerHTML; this.updateInput(); };
                            } else if (block.type === "heading") {
                                const tag = "H" + (block.level || 2);
                                content.innerHTML = `<${tag} contenteditable="true">${block.content || ""}</${tag}>`;
                                const h = content.querySelector(tag);
                                h.oninput = () => { block.content = h.innerHTML; this.updateInput(); };
                            } else if (block.type === "image") {
                                content.innerHTML = `<img src="${block.url || ""}" style="max-width:100%">`;
                                if (!block.url) content.innerHTML = "<div class=\"ux-block-image-placeholder\">点击设置图片</div>";
                            } else {
                                content.innerHTML = `<pre>${JSON.stringify(block)}</pre>`;
                            }
                            
                            bEl.appendChild(toolbar);
                            bEl.appendChild(content);
                            
                            bEl.onclick = (e) => {
                                e.stopPropagation();
                                this.selectBlock(block.uid);
                            };
                            
                            bEl.querySelector("[data-action=\"delete\"]").onclick = () => this.deleteBlock(index);
                            bEl.querySelector("[data-action=\"move-up\"]").onclick = () => this.moveBlock(index, -1);
                            bEl.querySelector("[data-action=\"move-down\"]").onclick = () => this.moveBlock(index, 1);
                            
                            return bEl;
                        },
                        
                        selectBlock(uid) {
                            this.selectedUid = uid;
                            this.render();
                            this.renderSidebar();
                        },
                        
                        deleteBlock(index) {
                            this.blocks.splice(index, 1);
                            this.render();
                            this.renderSidebar();
                        },
                        
                        moveBlock(index, dir) {
                            const target = index + dir;
                            if (target < 0 || target >= this.blocks.length) return;
                            const temp = this.blocks[index];
                            this.blocks[index] = this.blocks[target];
                            this.blocks[target] = temp;
                            this.render();
                        },
                        
                        addBlock(type) {
                            const uid = "b" + Math.random().toString(36).substr(2, 9);
                            const newBlock = { uid, type, content: "" };
                            if (type === "heading") newBlock.level = 2;
                            this.blocks.push(newBlock);
                            this.render();
                            this.selectBlock(uid);
                        },
                        
                        renderSidebar() {
                            sidebar.innerHTML = "";
                            const block = this.blocks.find(b => b.uid === this.selectedUid);
                            if (!block) {
                                sidebar.innerHTML = "<p class=\"text-muted\">选择一个区块进行设置</p>";
                                return;
                            }
                            
                            const title = document.createElement("h4");
                            title.innerText = "区块设置 (" + block.type + ")";
                            sidebar.appendChild(title);
                            
                            if (block.type === "heading") {
                                const label = document.createElement("label");
                                label.innerText = "标题层级";
                                const select = document.createElement("select");
                                select.className = "ux-form-input";
                                [1,2,3,4,5,6].forEach(l => {
                                    const opt = document.createElement("option");
                                    opt.value = l;
                                    opt.text = "H" + l;
                                    if (block.level == l) opt.selected = true;
                                    select.appendChild(opt);
                                });
                                select.onchange = () => { block.level = parseInt(select.value); this.render(); };
                                sidebar.appendChild(label);
                                sidebar.appendChild(select);
                            }
                            
                            if (block.type === "image") {
                                const btn = document.createElement("button");
                                btn.className = "page-btn page-btn-outline w-100";
                                btn.innerText = "选择图片";
                                btn.onclick = () => {
                                    // Trigger MediaPicker or something
                                    alert("点击了图片选择");
                                };
                                sidebar.appendChild(btn);
                            }
                        },
                        
                        updateInput() {
                            this.hiddenInput.value = JSON.stringify(this.blocks);
                            el.dispatchEvent(new CustomEvent("ux:change", { detail: { value: this.hiddenInput.value }, bubbles: true }));
                        }
                    };
                    
                    el.querySelector(".ux-block-editor-add-btn").onclick = () => {
                        const type = prompt("输入区块类型 (paragraph, heading, image):", "paragraph");
                        if (type) editor.addBlock(type);
                    };
                    
                    editor.render();
                    this.editors.set(editorId, editor);
                }
            };
            return BlockEditor;
        ');
    }

    public function height(string $height): static
    {
        $this->height = $height;
        return $this;
    }

    protected function toElement(): Element
    {
        $groupEl = Element::make('div')->class('ux-form-group');
        $labelEl = $this->renderLabel();
        if ($labelEl) $groupEl->child($labelEl);

        $editorId = $this->name . '-block-editor';
        $wrapper = Element::make('div')
            ->class('ux-block-editor')
            ->attr('data-editor-id', $editorId);
        
        if ($this->height) {
            $wrapper->style("height: {$this->height}");
        }

        $header = Element::make('div')->class('ux-block-editor-header');
        $header->child(
            Element::make('button')
                ->class('ux-block-editor-add-btn')
                ->attr('type', 'button')
                ->html('<i class="bi bi-plus-circle"></i> 添加区块')
        );
        $wrapper->child($header);

        $main = Element::make('div')->class('ux-block-editor-main');
        
        $canvas = Element::make('div')->class('ux-block-editor-canvas');
        $main->child($canvas);

        $sidebar = Element::make('div')->class('ux-block-editor-sidebar');
        $sidebar->child(Element::make('div')->class('ux-block-editor-sidebar-title')->text('属性'));
        $sidebar->child(Element::make('div')->class('ux-block-editor-sidebar-content'));
        $main->child($sidebar);

        $wrapper->child($main);

        $hiddenInput = Element::make('input')
            ->attr('type', 'hidden')
            ->attr('name', $this->name)
            ->attr('value', (string)$this->getValue());

        if ($this->liveModel) {
            $hiddenInput->attr('data-live-model', $this->liveModel);
        }

        if ($this->submitMode) {
            $hiddenInput->attr('data-submit-field', $this->name);
        }

        $wrapper->child($hiddenInput);
        $groupEl->child($wrapper);

        return $groupEl;
    }
}
