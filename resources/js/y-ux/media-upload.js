class MediaUpload {
    constructor() {
        this.bound = new WeakSet();
        this._setupGlobalDrag();
    }

    _setupGlobalDrag() {
        if (document._mediaUploadDragReady) return;
        document._mediaUploadDragReady = true;

        let dragCounter = 0;

        document.addEventListener('dragenter', (e) => {
            dragCounter++;
            const area = e.target.closest('[data-media-upload]');
            if (area) {
                area.classList.add('media-upload-dragover');
            }
        });

        document.addEventListener('dragleave', (e) => {
            dragCounter--;
            if (dragCounter <= 0) {
                dragCounter = 0;
                document.querySelectorAll('.media-upload-dragover').forEach(el => {
                    el.classList.remove('media-upload-dragover');
                });
            }
        });

        document.addEventListener('dragover', (e) => {
            e.preventDefault();
        });

        document.addEventListener('drop', (e) => {
            e.preventDefault();
            document.querySelectorAll('.media-upload-dragover').forEach(el => {
                el.classList.remove('media-upload-dragover');
            });
            dragCounter = 0;

            const area = e.target.closest('[data-media-upload]');
            if (!area) return;

            const files = e.dataTransfer.files;
            if (files.length > 0) {
                const uploadUrl = area.dataset.uploadUrl || '/admin/media/upload';
                this.uploadFiles(files, uploadUrl, area);
            }
        });
    }

    init(root = document) {
        root.querySelectorAll('[data-media-upload]').forEach(el => {
            if (this.bound.has(el)) return;
            this.bound.add(el);

            const input = el.querySelector('.media-upload-input');
            if (!input) return;

            el.addEventListener('click', (e) => {
                if (e.target.closest('.media-upload-input')) return;
                input.click();
            });

            input.addEventListener('change', () => {
                if (input.files.length > 0) {
                    const uploadUrl = el.dataset.uploadUrl || '/admin/media/upload';
                    this.uploadFiles(input.files, uploadUrl, el);
                    input.value = '';
                }
            });
        });
    }

    async uploadFiles(fileList, uploadUrl, areaEl) {
        areaEl.classList.add('media-upload-uploading');

        const formData = new FormData();
        for (let i = 0; i < fileList.length; i++) {
            formData.append('files[]', fileList[i]);
        }

        try {
            const csrfToken = () =>
                document.querySelector('meta[name="csrf-token"]')
                    ?.getAttribute("content") || "";

            const resp = await fetch(uploadUrl, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-Token': csrfToken(),
                },
            });

            const data = await resp.json();

            if (data.success && data.results) {
                const failed = data.results.filter(r => !r.success);
                if (failed.length > 0) {
                    alert(failed.map(r => r.message).join('\n'));
                }
            }

            const liveEl = areaEl.closest('[data-live]');
            if (liveEl && liveEl.$live) {
                liveEl.$live.refresh('media-grid');
            }
        } catch (err) {
            console.error('Upload failed:', err);
            alert('上传失败: ' + err.message);
        } finally {
            areaEl.classList.remove('media-upload-uploading');
        }
    }
}

window.MediaUpload = new MediaUpload();

document.addEventListener('DOMContentLoaded', () => {
    window.MediaUpload.init();
});

window.addEventListener('y:ready', () => {
    window.MediaUpload.init();
});

window.addEventListener('y:updated', (e) => {
    const root = e.detail?.el || document;
    window.MediaUpload.init(root);
});
