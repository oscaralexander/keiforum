<header class="modal__header">
    <h4 class="modal__title">{{ $slot }}</h4>
    <button
        class="modal__close"
        tabindex="-1"
        type="button"
        wire:click="$dispatch('closeModal')"
    ><x-icon icon="x" /></button>
</header>