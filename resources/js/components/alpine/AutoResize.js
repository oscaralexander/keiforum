export default (maxRows) => {
    return {
        maxHeight: 0,
        init() {
            const style = getComputedStyle(this.$el);
            const lineHeight = parseFloat(style.lineHeight) || parseFloat(style.fontSize) * 1.2;
            const paddingY = parseFloat(style.paddingTop) + parseFloat(style.paddingBottom);
            const borderY = parseFloat(style.borderTopWidth) + parseFloat(style.borderBottomWidth);
            this.maxHeight = lineHeight * maxRows + paddingY + borderY;
            this.resize();

            // Re-resize after Livewire updates the DOM (e.g. clearing the value after submit).
            Livewire.hook('morph.updated', ({ el }) => {
                if (el === this.$el) {
                    this.$nextTick(() => this.resize());
                }
            });
        },
        resize() {
            this.$el.style.height = 'auto';
            this.$el.style.height = Math.min(this.$el.scrollHeight, this.maxHeight) + 'px';
        },
    };
};
