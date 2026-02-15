export default (model, options) => {
    return {
        isOpen: false,
        get label() {
            return this.options
                .map((option) => (Array.from(this.model).map(String).includes(String(option.value)) ? option.label : null))
                .filter(Boolean)
                .join(', ');
        },
        model,
        options: Array.isArray(options) ? options : [],
    };
};
