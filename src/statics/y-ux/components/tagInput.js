// TagInput 标签输入组件
const TagInput = {
    init() {
        document.addEventListener('keydown', (e) => {
            if (!e.target || !e.target.classList) return;

            const input = e.target.closest('.ux-tag-input-field');
            if (!input) return;

            const tagInput = input.closest('.ux-tag-input');
            if (!tagInput) return;

            if (e.key === 'Enter') {
                e.preventDefault();
                const value = input.value.trim();
                if (value) {
                    this.addTag(tagInput, value);
                    input.value = '';
                }
            } else if (e.key === 'Backspace' && !input.value) {
                const tags = tagInput.querySelectorAll('.ux-tag-input-tag');
                if (tags.length > 0) {
                    this.removeTag(tags[tags.length - 1]);
                }
            }
        });

        document.addEventListener('click', (e) => {
            if (!e.target || !e.target.closest) return;

            const removeBtn = e.target.closest('.ux-tag-input-tag-remove');
            if (removeBtn) {
                const tag = removeBtn.closest('.ux-tag-input-tag');
                if (tag) {
                    this.removeTag(tag);
                }
            }

            const container = e.target.closest('.ux-tag-input-container');
            if (container) {
                const input = container.querySelector('.ux-tag-input-field');
                if (input) {
                    input.focus();
                }
            }
        });
    },

    addTag(tagInput, value) {
        const maxCount = parseInt(tagInput.dataset.tagMax) || 0;
        const currentTags = tagInput.querySelectorAll('.ux-tag-input-tag');

        if (maxCount > 0 && currentTags.length >= maxCount) {
            return;
        }

        const container = tagInput.querySelector('.ux-tag-input-container');
        const input = tagInput.querySelector('.ux-tag-input-field');

        const tag = document.createElement('span');
        tag.className = 'ux-tag-input-tag';
        tag.innerHTML = `${value}<span class="ux-tag-input-tag-remove"><i class="bi bi-x"></i></span>`;

        container.insertBefore(tag, input);
        this.updateValue(tagInput);
    },

    removeTag(tag) {
        const tagInput = tag.closest('.ux-tag-input');
        tag.remove();
        if (tagInput) {
            this.updateValue(tagInput);
        }
    },

    updateValue(tagInput) {
        const tags = tagInput.querySelectorAll('.ux-tag-input-tag');
        const values = Array.from(tags).map(tag => {
            return tag.childNodes[0].textContent.trim();
        });

        tagInput.dataset.tagValue = JSON.stringify(values);

        const hidden = tagInput.querySelector('.ux-tag-input-hidden');
        if (hidden) {
            hidden.value = values.join(',');
        }

        // 派发 ux:change 事件 → 桥接层自动同步到 Live
        tagInput.dispatchEvent(new CustomEvent('ux:change', {
            detail: { value: values.join(',') },
            bubbles: true
        }));
    }
};

export default TagInput;
