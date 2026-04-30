export const RichEditor = {
    commands: {
        bold: 'bold',
        italic: 'italic',
        underline: 'underline',
        strike: 'strikeThrough',
        heading: 'formatBlock',
        quote: 'formatBlock',
        code: 'formatBlock',
        list: 'insertUnorderedList',
        link: 'createLink',
        image: 'insertImage',
        undo: 'undo',
        redo: 'redo',
        clear: 'removeFormat'
    },

    exec(editorId, action, value = null) {
        const editor = document.getElementById(editorId);
        if (!editor) return;

        editor.focus();
        
        // 获取当前选区所在的块级元素
        const selection = window.getSelection();
        let container = selection.anchorNode;
        if (container && container.nodeType === 3) container = container.parentNode;
        const currentBlock = container ? container.closest('h1, h2, h3, h4, h5, h6, blockquote, pre') : null;

        // 逻辑：如果已经处于目标格式中，再次点击则取消格式（转为 p）
        if (currentBlock) {
            const isHeading = action === 'heading' && currentBlock.tagName.startsWith('H');
            const isQuote = action === 'quote' && currentBlock.tagName === 'BLOCKQUOTE';
            const isCode = action === 'code' && currentBlock.tagName === 'PRE';

            if (isHeading || isQuote || isCode) {
                document.execCommand('formatBlock', false, 'p');
                this.updateInput(editor);
                return;
            }
        }

        if (action === 'link') {
            this.showLinkModal(editor);
            return;
        }

        if (action === 'image') {
            this.showImageModal(editor);
            return;
        }

        const command = this.commands[action];
        if (!command) return;

        if (action === 'heading') {
            document.execCommand(command, false, 'h2');
        } else if (action === 'quote') {
            document.execCommand(command, false, 'blockquote');
        } else if (action === 'code') {
            document.execCommand(command, false, 'pre');
        } else {
            document.execCommand(command, false, value);
        }

        this.updateInput(editor);
    },

    // ... showLinkModal, showImageModal, createRichModal 保持不变 ...
    showLinkModal(editor) {
        const selection = window.getSelection();
        const range = selection.rangeCount > 0 ? selection.getRangeAt(0) : null;
        const selectedText = range ? range.toString() : '';

        this.createRichModal({
            title: '插入链接',
            fields: [
                { name: 'text', label: '链接文本', value: selectedText, placeholder: '输入链接显示的文字' },
                { name: 'url', label: '链接地址', value: 'https://', placeholder: 'https://example.com' }
            ],
            onConfirm: (data) => {
                editor.focus();
                if (range) {
                    selection.removeAllRanges();
                    selection.addRange(range);
                }

                if (data.text && data.text !== selectedText) {
                    document.execCommand('insertHTML', false, `<a href="${data.url}">${data.text}</a>`);
                } else {
                    document.execCommand('createLink', false, data.url);
                }
                this.updateInput(editor);
            }
        });
    },

    showImageModal(editor) {
        this.createRichModal({
            title: '插入图片',
            fields: [
                { name: 'url', label: '图片地址', value: 'https://', placeholder: 'https://example.com/image.jpg' },
                { name: 'alt', label: '替代文字', value: '', placeholder: '描述图片内容' }
            ],
            onConfirm: (data) => {
                editor.focus();
                const imgHtml = `<img src="${data.url}" alt="${data.alt || ''}" style="max-width:100%">`;
                document.execCommand('insertHTML', false, imgHtml);
                this.updateInput(editor);
            }
        });
    },

    createRichModal({ title, fields, onConfirm }) {
        const overlay = document.createElement('div');
        overlay.className = 'ux-rich-modal-overlay';
        
        const modal = document.createElement('div');
        modal.className = 'ux-rich-modal';
        
        let fieldsHtml = fields.map(f => `
            <div class="ux-rich-modal-field">
                <label>${f.label}</label>
                <input type="text" name="${f.name}" value="${f.value || ''}" placeholder="${f.placeholder || ''}">
            </div>
        `).join('');

        modal.innerHTML = `
            <div class="ux-rich-modal-header">${title}</div>
            <div class="ux-rich-modal-body">${fieldsHtml}</div>
            <div class="ux-rich-modal-footer">
                <button type="button" class="ux-rich-modal-btn cancel">取消</button>
                <button type="button" class="ux-rich-modal-btn confirm">确定</button>
            </div>
        `;

        overlay.appendChild(modal);
        document.body.appendChild(overlay);

        const close = () => {
            overlay.classList.add('fade-out');
            setTimeout(() => { if (overlay.parentNode) document.body.removeChild(overlay); }, 200);
        };

        modal.querySelector('.cancel').onclick = close;
        modal.querySelector('.confirm').onclick = () => {
            const data = {};
            modal.querySelectorAll('input').forEach(input => {
                data[input.name] = input.value;
            });
            onConfirm(data);
            close();
        };

        overlay.onclick = (e) => {
            if (e.target === overlay) close();
        };

        setTimeout(() => modal.querySelector('input').focus(), 50);
    },

    updateInput(editor) {
        const inputId = editor.dataset.inputId;
        const input = inputId 
            ? document.getElementById(inputId) 
            : editor.closest('.ux-rich-editor')?.querySelector('input[type="hidden"]');
        
        if (input) {
            const newValue = editor.innerHTML;
            if (input.value !== newValue) {
                input.value = newValue;
                input.dispatchEvent(new Event('input', { bubbles: true }));
                input.dispatchEvent(new Event('change', { bubbles: true }));
            }
        }
    },

    init() {
        let debounceTimer = null;
        
        // 监听回车键，处理“退出”块级元素
        document.addEventListener('keydown', (e) => {
            const editor = e.target.closest('.ux-rich-editor__area');
            if (!editor) return;

            if (e.key === 'Enter' && !e.shiftKey) {
                const selection = window.getSelection();
                if (!selection.rangeCount) return;

                const range = selection.getRangeAt(0);
                const container = range.commonAncestorContainer;
                const block = (container.nodeType === 1 ? container : container.parentElement).closest('blockquote, pre');

                if (block) {
                    // 如果在块的最后一行输入回车，且该行只有换行符或为空，则跳出
                    const text = block.innerText || '';
                    const cursorAtEnd = range.startOffset === container.length || container.nodeType === 1;

                    // 这里的逻辑：如果当前行是空的，则跳出
                    // 简单的处理方案：如果在 blockquote/pre 内部按回车，先执行默认，
                    // 但如果点击了两次回车，或者通过工具栏切换，用户能感知到。
                    // 
                    // 更激进的做法：在 PRE 内部，Enter 默认插入 \n，Shift+Enter 跳出。
                    // 这里我们采用最稳妥的方案：支持“双击回车退出”。
                    if (text.endsWith('\n\n') || text === '\n') {
                        e.preventDefault();
                        block.innerText = text.trim(); // 清理最后的空行
                        const p = document.createElement('p');
                        p.innerHTML = '<br>';
                        block.parentNode.insertBefore(p, block.nextSibling);
                        
                        // 移动光标
                        const newRange = document.createRange();
                        newRange.setStart(p, 0);
                        newRange.collapse(true);
                        selection.removeAllRanges();
                        selection.addRange(newRange);
                        
                        this.updateInput(editor);
                    }
                }
            }
        });

        document.addEventListener('input', (e) => {
            const editor = e.target.closest('.ux-rich-editor__area');
            if (editor) {
                clearTimeout(debounceTimer);
                debounceTimer = setTimeout(() => {
                    RichEditor.updateInput(editor);
                }, 300);
            }
        }, true);

        document.addEventListener('blur', (e) => {
            const editor = e.target.closest('.ux-rich-editor__area');
            if (editor) {
                RichEditor.updateInput(editor);
            }
        }, true);

        document.addEventListener('click', (e) => {
            const btn = e.target.closest('.ux-rich-editor__btn');
            if (!btn) return;

            const action = btn.dataset.action;
            const editorId = btn.dataset.editor;

            if (action && editorId) {
                e.preventDefault();
                RichEditor.exec(editorId, action);
            }
        });

        document.addEventListener('paste', (e) => {
            const editor = e.target.closest('.ux-rich-editor__area');
            if (editor) {
                e.preventDefault();
                const text = (e.clipboardData || window.clipboardData).getData('text/plain');
                document.execCommand('insertText', false, text);
                RichEditor.updateInput(editor);
            }
        }, true);
    }
};

export default RichEditor;
