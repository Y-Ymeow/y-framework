document.addEventListener('click', function (e) {
    var uploadTrigger = e.target.closest('[data-media-trigger]');
    if (uploadTrigger) {
        var uploadZone = uploadTrigger.closest('[data-live-upload]');
        if (uploadZone) {
            var fileInput = uploadZone.querySelector('input[type="file"]');
            if (fileInput) fileInput.click();
        }
        return;
    }

    var removeBtn = e.target.closest('[data-media-remove]');
    if (removeBtn) {
        var picker = removeBtn.closest('.ux-form-media-picker');
        if (picker) {
            var hiddenInput = picker.querySelector('[data-media-value]');
            if (hiddenInput) {
                hiddenInput.value = '';
                hiddenInput.dispatchEvent(new Event('input', { bubbles: true }));
            }
            var preview = picker.querySelector('.ux-form-media-preview');
            if (preview) {
                preview.classList.remove('has-image');
                preview.classList.add('empty');
                preview.innerHTML = '<div class="ux-form-media-placeholder"><i class="bi bi-image"></i><span>未选择图片</span></div>';
            }
            removeBtn.remove();
        }
        return;
    }

    var gridItem = e.target.closest('.media-picker-item');
    if (gridItem) {
        var url = gridItem.getAttribute('data-media-url');
        if (!url) return;

        var modal = gridItem.closest('[id^="media-picker-"]');
        var modalId = modal ? modal.id : '';

        var group = modal ? modal.closest('.ux-form-group') : null;
        if (!group) return;

        var hiddenInput = group.querySelector('[data-media-value]');
        if (hiddenInput) {
            hiddenInput.value = url;
            hiddenInput.dispatchEvent(new Event('input', { bubbles: true }));
        }

        var picker = group.querySelector('.ux-form-media-picker');
        if (picker) {
            var preview = picker.querySelector('.ux-form-media-preview');
            if (preview) {
                preview.classList.add('has-image');
                preview.classList.remove('empty');
                var img = preview.querySelector('img');
                if (img) {
                    img.src = url;
                } else {
                    img = document.createElement('img');
                    img.className = 'ux-form-media-preview-img';
                    img.src = url;
                    img.alt = '';
                    preview.innerHTML = '';
                    preview.appendChild(img);
                }
            }
            var actions = picker.querySelector('.ux-form-media-actions');
            var existingRemove = actions ? actions.querySelector('[data-media-remove]') : null;
            if (!existingRemove && actions) {
                var btn = document.createElement('button');
                btn.className = 'ux-form-media-remove';
                btn.type = 'button';
                btn.setAttribute('data-media-remove', '');
                btn.innerHTML = '<i class="bi bi-x"></i>';
                actions.appendChild(btn);
            }
        }
        return;
    }
});
