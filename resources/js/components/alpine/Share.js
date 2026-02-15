export default () => {
    return {
        init() {
            this.$root.addEventListener('click', this.onClick.bind(this));
        },
        async onClick($event) {
            $event.preventDefault();

            const data = {
                text: $event.currentTarget.dataset.shareText,
                title: $event.currentTarget.dataset.shareTitle,
                url: $event.currentTarget.href,
            };

            try {
                await navigator.share(data);
            } catch (err) {
                console.error(err);
            }
        },
    };
};
