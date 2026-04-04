export default (content, delay = 100) => {
    return {
        _delayTimer: null,
        _hideHandler: null,
        _showHandler: null,
        $arrow: null,
        $tooltip: null,
        content,
        delay,
        show() {
            this._delayTimer = setTimeout(() => {
                this.$tooltip = document.createElement('div');
                this.$tooltip.textContent = this.content;
                this.$tooltip.classList.add('tooltip');

                this.$arrow = document.createElement('div');
                this.$arrow.classList.add('tooltip__arrow');
                this.$tooltip.appendChild(this.$arrow);

                document.body.appendChild(this.$tooltip);
                this.position();
            }, this.delay);
        },
        position() {
            const anchorRect = this.$root.getBoundingClientRect();
            const tooltipRect = this.$tooltip.getBoundingClientRect();
            const arrowSize = 4;
            const margin = arrowSize + 4;
            const viewportPadding = 8;

            // Default: centered above the anchor
            let isAbove = true;
            let top = anchorRect.top - tooltipRect.height - margin;
            let left = anchorRect.left + anchorRect.width / 2 - tooltipRect.width / 2;

            // If not enough space above, show below
            if (top < viewportPadding) {
                isAbove = false;
                top = anchorRect.bottom + margin;
            }

            // Clamp horizontally within the viewport
            left = Math.max(viewportPadding, Math.min(left, window.innerWidth - tooltipRect.width - viewportPadding));

            this.$tooltip.classList.add('is-enter');
            this.$tooltip.style.top = `${top}px`;
            this.$tooltip.style.left = `${left}px`;

            // Position arrow centered over $root, clamped within bubble bounds
            const rootCenterX = anchorRect.left + anchorRect.width / 2;
            const arrowLeft = Math.max(arrowSize, Math.min(rootCenterX - left - arrowSize, tooltipRect.width - arrowSize * 3));

            this.$tooltip.classList.toggle('is-above', isAbove);
            this.$tooltip.classList.toggle('is-below', !isAbove);
            this.$arrow.style.left = `${arrowLeft}px`;
        },
        hide() {
            clearTimeout(this._delayTimer);
            this._delayTimer = null;

            if (this.$tooltip) {
                this.$tooltip.remove();
                this.$tooltip = null;
                this.$arrow = null;
            }
        },
        init() {
            this._showHandler = this.show.bind(this);
            this._hideHandler = this.hide.bind(this);
            this.$root.addEventListener('mouseenter', this._showHandler);
            this.$root.addEventListener('mouseleave', this._hideHandler);
        },
        destroy() {
            this.hide();
            this.$root.removeEventListener('mouseenter', this._showHandler);
            this.$root.removeEventListener('mouseleave', this._hideHandler);
        },
    };
};
