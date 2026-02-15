export default () => {
    return {
        isLoading: false,
        init() {
            this.$el.addEventListener('scroll', this.onScroll.bind(this), { passive: true });
        },
        onScroll() {
            if (this.isLoading || this.$el.scrollTop !== 0) {
                return;
            }

            this.isLoading = true;
            const previousScrollHeight = this.$el.scrollHeight;

            this.$wire.loadMoreMessages().then(() => {
                this.$nextTick(() => {
                    this.$el.scrollTop = this.$el.scrollHeight - previousScrollHeight;

                    requestAnimationFrame(() => {
                        this.isLoading = false;
                    });
                });
            });
        },
        scrollToBottom() {
            this.$el.scrollTop = this.$el.scrollHeight;
        },
    };
};
