export default () => {
    return {
        $clippingContainer: null,
        align() {
            // Determine the scrolling container (clipping container) or default to window/document
            let $container = this.$clippingContainer;
            let rootRect = this.$root.getBoundingClientRect();

            let containerRect;
            let containerScrollTop = 0;
            let containerScrollLeft = 0;

            if ($container) {
                containerRect = $container.getBoundingClientRect();
                containerScrollTop = $container.scrollTop;
                containerScrollLeft = $container.scrollLeft;
            } else {
                // Fallback to viewport
                containerRect = {
                    top: 0,
                    bottom: window.innerHeight,
                    left: 0,
                    right: window.innerWidth,
                    height: window.innerHeight,
                    width: window.innerWidth,
                };
                containerScrollTop = window.scrollY || window.pageYOffset;
                containerScrollLeft = window.scrollX || window.pageXOffset;
            }

            // Calculate available space above and below the root in the container
            const spaceAbove = rootRect.top - containerRect.top + containerScrollTop;
            const spaceBelow = containerRect.bottom - rootRect.bottom + containerScrollTop;

            // Toggle is-top and is-bottom classes
            this.$root.classList.remove('is-top', 'is-bottom');

            if (spaceAbove > spaceBelow) {
                this.$root.classList.add('is-top');
            } else {
                this.$root.classList.add('is-bottom');
            }

            // Calculate available space to the left and right of the root in the container
            const spaceLeft = rootRect.left - containerRect.left + containerScrollLeft;
            const spaceRight = containerRect.right - rootRect.right + containerScrollLeft;

            // Toggle is-left and is-right classes
            this.$root.classList.remove('is-left', 'is-right');

            if (spaceLeft > spaceRight) {
                this.$root.classList.add('is-left');
            } else {
                this.$root.classList.add('is-right');
            }
        },
        isOpen: false,
        init() {
            // Determine menu offsets
            const computedStyle = getComputedStyle(this.$root);
            const offsetX = computedStyle.getPropertyValue('--offset-x');
            const offsetY = computedStyle.getPropertyValue('--offset-y');
            this.offsetX = offsetX !== '' ? parseInt(offsetX) : 0;
            this.offsetY = offsetY !== '' ? parseInt(offsetY) : 0;

            // Identify closest clipping container
            this.$clippingContainer = null;
            let $currentElement = this.$root;

            while ($currentElement && $currentElement !== document.body) {
                const overflow = window.getComputedStyle($currentElement).overflow;

                if (overflow === 'hidden' || overflow === 'auto' || overflow === 'scroll') {
                    this.$clippingContainer = $currentElement;
                    break;
                }

                $currentElement = $currentElement.parentElement;
            }

            this.$root.addEventListener('click', this.onClick.bind(this));
        },
        onClick($event) {
            $event.preventDefault();

            this.isOpen = !this.isOpen;

            if (this.isOpen) {
                this.align();
            }
        },
        offsetX: 0,
        offsetY: 0,
    };
};
