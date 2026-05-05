// Rate 评分组件
const Rate = {
    init() {
        // 使用事件委托
        document.addEventListener('click', (e) => {
            if (!e.target || !e.target.closest) return;
            const star = e.target.closest('.ux-rate-star');
            if (star) {
                const rate = star.closest('.ux-rate');
                if (rate && !rate.dataset.rateDisabled && !rate.dataset.rateReadonly) {
                    this.handleClick(rate, star);
                }
            }

            const halfTrigger = e.target.closest('.ux-rate-star-half-trigger');
            if (halfTrigger) {
                const rate = halfTrigger.closest('.ux-rate');
                if (rate && !rate.dataset.rateDisabled && !rate.dataset.rateReadonly) {
                    this.handleHalfClick(rate, halfTrigger);
                }
            }
        });

        document.addEventListener('mouseenter', (e) => {
            if (!e.target || !e.target.closest) return;
            const star = e.target.closest('.ux-rate-star');
            if (star) {
                const rate = star.closest('.ux-rate');
                if (rate && !rate.dataset.rateDisabled && !rate.dataset.rateReadonly) {
                    this.handleHover(rate, star);
                }
            }
        }, true);

        document.addEventListener('mouseleave', (e) => {
            if (!e.target || !e.target.closest) return;
            const rate = e.target.closest('.ux-rate');
            if (rate && !rate.dataset.rateDisabled && !rate.dataset.rateReadonly) {
                this.handleLeave(rate);
            }
        }, true);
    },

    handleClick(rate, star) {
        const index = parseFloat(star.dataset.rateIndex);

        // 更新值
        rate.dataset.rateValue = index;
        this.updateStars(rate, index);

        // 派发 ux:change 事件 → 桥接层自动同步到 Live
        rate.dispatchEvent(new CustomEvent('ux:change', {
            detail: { value: index },
            bubbles: true
        }));
    },

    handleHalfClick(rate, halfTrigger) {
        const index = parseFloat(halfTrigger.dataset.rateIndex);

        // 更新值
        rate.dataset.rateValue = index;
        this.updateStars(rate, index);

        // 派发 ux:change 事件 → 桥接层自动同步到 Live
        rate.dispatchEvent(new CustomEvent('ux:change', {
            detail: { value: index },
            bubbles: true
        }));
    },

    handleHover(rate, star) {
        const index = parseFloat(star.dataset.rateIndex);
        const hoverAction = rate.dataset.rateHoverAction;

        // 更新视觉状态
        this.updateHoverStars(rate, index);

        // 触发 hover action
        if (hoverAction && window.L) {
            window.L.executeOperation({
                op: 'action',
                action: hoverAction,
                params: { value: index }
            });
        }
    },

    handleLeave(rate) {
        // 恢复到实际值
        const value = parseFloat(rate.dataset.rateValue) || 0;
        this.updateStars(rate, value);
    },

    updateStars(rate, value) {
        const stars = rate.querySelectorAll('.ux-rate-star');
        const allowHalf = rate.dataset.rateAllowHalf === 'true';

        stars.forEach((star) => {
            const index = parseFloat(star.dataset.rateIndex);
            star.classList.remove('ux-rate-star-full', 'ux-rate-star-half', 'ux-rate-star-empty');

            if (index <= value) {
                star.classList.add('ux-rate-star-full');
            } else if (allowHalf && index - 0.5 <= value) {
                star.classList.add('ux-rate-star-half');
            } else {
                star.classList.add('ux-rate-star-empty');
            }
        });
    },

    updateHoverStars(rate, hoverIndex) {
        const stars = rate.querySelectorAll('.ux-rate-star');

        stars.forEach((star) => {
            const index = parseFloat(star.dataset.rateIndex);
            if (index <= hoverIndex) {
                star.classList.add('hovered');
            } else {
                star.classList.remove('hovered');
            }
        });
    },

    // 程序化设置值
    setValue(id, value) {
        const rate = document.querySelector(`#${id}.ux-rate`);
        if (rate) {
            rate.dataset.rateValue = value;
            this.updateStars(rate, value);
        }
    },

    // 获取值
    getValue(id) {
        const rate = document.querySelector(`#${id}.ux-rate`);
        return rate ? parseFloat(rate.dataset.rateValue) || 0 : 0;
    }
};

export default Rate;
