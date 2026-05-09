document.addEventListener('click', function (e) {
    var applyBtn = e.target.closest('[data-link-apply]');
    if (applyBtn) {
        var modalId = applyBtn.getAttribute('data-link-apply');
        var modal = document.getElementById(modalId);
        if (!modal) return;

        var urlEl = modal.querySelector('[data-link-modal-url]');
        var labelEl = modal.querySelector('[data-link-modal-label]');
        var targetEl = modal.querySelector('[data-link-modal-target]');
        var url = urlEl ? urlEl.value : '';
        var label = labelEl ? labelEl.value : '';
        var target = targetEl ? targetEl.value : '_self';

        var group = modal.closest('.ux-form-group');
        if (!group) return;

        var picker = group.querySelector('.ux-form-link-picker');
        if (picker) {
            var hiddenUrl = picker.querySelector('[data-link-url]');
            var hiddenTarget = picker.querySelector('[data-link-target]');
            var hiddenLabel = picker.querySelector('[data-link-label]');
            if (hiddenUrl) hiddenUrl.value = url;
            if (hiddenTarget) hiddenTarget.value = target;
            if (hiddenLabel) hiddenLabel.value = label;

            var display = picker.querySelector('.ux-form-link-display');
            if (display) {
                if (url) {
                    var targetText = target;
                    var targetElSelect = modal.querySelector('[data-link-modal-target] option[value="' + target + '"]');
                    if (targetElSelect) targetText = targetElSelect.textContent;
                    display.innerHTML = '<span class="ux-form-link-url-display">' + url + '</span><span class="ux-form-link-target-display">' + targetText + '</span>';
                } else {
                    display.innerHTML = '<span class="ux-form-link-placeholder">未设置链接</span>';
                }
            }

            var actions = picker.querySelector('.ux-form-link-actions');
            var existingRemove = actions ? actions.querySelector('[data-link-remove]') : null;
            if (!existingRemove && url && actions) {
                var btn = document.createElement('button');
                btn.className = 'ux-form-link-remove';
                btn.type = 'button';
                btn.setAttribute('data-link-remove', '');
                btn.innerHTML = '<i class="bi bi-x"></i>';
                actions.appendChild(btn);
            }
        }

        // Handle LiveAction if present
        var liveAction = applyBtn.getAttribute('data-action');
        if (liveAction && window.L) {
            var componentEl = applyBtn.closest('[data-live]');
            if (componentEl) {
                var componentId = componentEl.getAttribute('data-live-id');
                var actionParams = JSON.parse(applyBtn.getAttribute('data-action-params') || '{}');
                actionParams.url = url;
                actionParams.label = label;
                actionParams.target = target;
                
                L.executeAction(componentId, liveAction, actionParams);
                e.stopImmediatePropagation();
                return;
            }
        }

        return;
    }

    var removeBtn = e.target.closest('[data-link-remove]');
    if (removeBtn) {
        var picker = removeBtn.closest('.ux-form-link-picker');
        if (picker) {
            var hiddenUrl = picker.querySelector('[data-link-url]');
            var hiddenTarget = picker.querySelector('[data-link-target]');
            var hiddenLabel = picker.querySelector('[data-link-label]');
            if (hiddenUrl) hiddenUrl.value = '';
            if (hiddenTarget) hiddenTarget.value = '_self';
            if (hiddenLabel) hiddenLabel.value = '';
            var d = picker.querySelector('.ux-form-link-display');
            if (d) d.innerHTML = '<span class="ux-form-link-placeholder">未设置链接</span>';
            removeBtn.remove();
        }
        return;
    }
});
