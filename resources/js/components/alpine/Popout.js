/**
 * A button with a dropdown menu.
 *
 * Where the browser supports the Popover API, the menu is rendered in the top
 * layer and positioned against the viewport, so no ancestor's `overflow`,
 * `transform` or `mask` can clip or cover it. It stays in place in the DOM, so
 * Livewire keeps morphing it and `wire:click` on its items keeps working.
 * Older browsers fall back to the menu being absolutely positioned inside the
 * button.
 */
export default () => {
    return {
        $clippingContainer: null,
        isOpen: false,
        isTopLayer: false,
        offsetX: 0,
        offsetY: 0,
        reposition: null,

        init() {
            const computedStyle = getComputedStyle(this.$root);
            const offsetX = computedStyle.getPropertyValue('--offset-x');
            const offsetY = computedStyle.getPropertyValue('--offset-y');
            this.offsetX = offsetX !== '' ? parseFloat(offsetX) * this.pixelsPerUnit(offsetX) : 0;
            this.offsetY = offsetY !== '' ? parseFloat(offsetY) * this.pixelsPerUnit(offsetY) : 0;

            this.isTopLayer = this.$refs.menu.hasAttribute('popover')
                && typeof this.$refs.menu.showPopover === 'function';

            // In the top layer nothing clips the menu but the viewport itself.
            this.$clippingContainer = this.isTopLayer ? null : this.findClippingContainer();
            this.reposition = () => this.isOpen && this.align();

            this.$root.addEventListener('click', this.onClick.bind(this));
            this.$watch('isOpen', (isOpen) => (isOpen ? this.show() : this.hide()));
        },

        destroy() {
            this.hide();
        },

        /**
         * The number of pixels per unit of a CSS length: 16 for `rem`, 1 for `px`.
         */
        pixelsPerUnit(value) {
            return value.trim().endsWith('rem')
                ? parseFloat(getComputedStyle(document.documentElement).fontSize)
                : 1;
        },

        findClippingContainer() {
            const clippingValues = ['hidden', 'clip', 'auto', 'scroll'];
            let $element = this.$root;

            while ($element && $element !== document.body) {
                const computed = getComputedStyle($element);

                if (clippingValues.includes(computed.overflowX) || clippingValues.includes(computed.overflowY)) {
                    return $element;
                }

                $element = $element.parentElement;
            }

            return null;
        },

        align() {
            const rootRect = this.$root.getBoundingClientRect();
            const containerRect = this.$clippingContainer
                ? this.$clippingContainer.getBoundingClientRect()
                : { top: 0, bottom: window.innerHeight, left: 0, right: window.innerWidth };

            const isTop = rootRect.top - containerRect.top > containerRect.bottom - rootRect.bottom;
            const isLeft = rootRect.left - containerRect.left > containerRect.right - rootRect.right;

            this.$root.classList.remove('is-top', 'is-bottom', 'is-left', 'is-right');
            this.$root.classList.add(isTop ? 'is-top' : 'is-bottom', isLeft ? 'is-left' : 'is-right');

            if (!this.isTopLayer) {
                return;
            }

            // The top layer is positioned against the viewport, so translate
            // the classes above into coordinates. The classes keep driving the
            // in-animations.
            const { width: menuWidth, height: menuHeight } = this.$refs.menu.getBoundingClientRect();
            const left = isLeft ? rootRect.right - menuWidth - this.offsetX : rootRect.left + this.offsetX;
            const top = isTop ? rootRect.top - this.offsetY - menuHeight : rootRect.bottom + this.offsetY;

            this.$refs.menu.style.left = `${left}px`;
            this.$refs.menu.style.top = `${top}px`;
        },

        show() {
            if (this.isTopLayer) {
                // Livewire may have morphed the menu while it was open;
                // showing an open popover again would throw.
                if (!this.$refs.menu.matches(':popover-open')) {
                    this.$refs.menu.showPopover();
                }

                // The trigger moves when any ancestor scrolls, the menu doesn't.
                document.addEventListener('scroll', this.reposition, { capture: true, passive: true });
                window.addEventListener('resize', this.reposition, { passive: true });
            }

            this.align();
        },

        hide() {
            if (!this.isTopLayer) {
                return;
            }

            document.removeEventListener('scroll', this.reposition, { capture: true });
            window.removeEventListener('resize', this.reposition);

            if (this.$refs.menu.matches(':popover-open')) {
                this.$refs.menu.hidePopover();
            }
        },

        onClick($event) {
            $event.preventDefault();

            this.isOpen = !this.isOpen;
        },
    };
};
