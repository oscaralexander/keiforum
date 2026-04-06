<div class="toast" x-data="toast()">
    <template :key="toast.id" x-for="toast in toasts">
        <div
            class="toast__item"
            :class="[
                'toast__item--' + toast.type,
                toast.visible ? 'is-visible' : '',
                toast.leaving ? 'is-leaving' : '',
            ]"
            x-text="toast.message"
        ></div>
    </template>
</div>
