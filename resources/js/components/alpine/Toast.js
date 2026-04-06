export default () => {
    return {
        duration: 2500,
        toasts: [],
        add(message, type = 'success') {
            const id = Date.now();
            this.toasts.push({ id, message, type, visible: false, leaving: false });

            this.$nextTick(() => {
                const toast = this.toasts.find((t) => t.id === id);

                if (toast) {
                    toast.visible = true;
                }
            });

            setTimeout(() => this.remove(id), this.duration);
        },
        remove(id) {
            const toast = this.toasts.find((t) => t.id === id);

            if (toast) {
                toast.visible = false;
                toast.leaving = true;

                setTimeout(() => {
                    this.toasts = this.toasts.filter((t) => t.id !== id);
                }, 300);
            }
        },
        init() {
            window.addEventListener('toast', (event) => {
                this.add(event.detail.message, event.detail.type ?? 'success');
            });
        },
    };
};
