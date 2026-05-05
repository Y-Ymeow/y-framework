// QRCode 二维码组件
import QRCodeLib from 'qrcode';

const QRCode = {
    init() {
        document.querySelectorAll('.ux-qrcode').forEach(qrcode => {
            this.render(qrcode);
        });
    },

    async render(qrcode) {
        const canvas = qrcode.querySelector('canvas');
        if (!canvas) return;

        const value = qrcode.dataset.qrcodeValue;
        const size = parseInt(qrcode.dataset.qrcodeSize) || 128;
        const color = qrcode.dataset.qrcodeColor || '#000000';
        const bgColor = qrcode.dataset.qrcodeBg || '#ffffff';

        if (!value) return;

        try {
            await QRCodeLib.toCanvas(canvas, value, {
                width: size,
                margin: 2,
                color: {
                    dark: color,
                    light: bgColor
                }
            });

            // 添加图标（如果有）
            const icon = qrcode.dataset.qrcodeIcon;
            if (icon) {
                this.addIcon(qrcode, canvas, icon);
            }
        } catch (err) {
            console.error('QRCode render error:', err);
        }
    },

    addIcon(qrcode, canvas, iconUrl) {
        const iconSize = parseInt(qrcode.dataset.qrcodeIconSize) || 32;
        const ctx = canvas.getContext('2d');
        const img = new Image();
        img.crossOrigin = 'anonymous';
        img.onload = () => {
            const x = (canvas.width - iconSize) / 2;
            const y = (canvas.height - iconSize) / 2;
            ctx.drawImage(img, x, y, iconSize, iconSize);
        };
        img.src = iconUrl;
    },

    // 程序化生成
    async generate(value, options = {}) {
        const {
            size = 128,
            color = '#000000',
            bgColor = '#ffffff'
        } = options;

        try {
            return await QRCodeLib.toDataURL(value, {
                width: size,
                margin: 2,
                color: {
                    dark: color,
                    light: bgColor
                }
            });
        } catch (err) {
            console.error('QRCode generate error:', err);
            return null;
        }
    }
};

export default QRCode;
