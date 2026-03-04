<div>
    @isset($jsPath)
        <script>{!! file_get_contents($jsPath) !!}</script>
    @endisset
    @isset($cssPath)
        <style>{!! file_get_contents($cssPath) !!}</style>
    @endisset
    <div
        class="modal"
        style="display: none;"
        x-data="LivewireUIModal()"
        x-on:close.stop="setShowPropertyTo(false)"
        x-on:keydown.escape.window="closeModalOnEscape()"
        x-show="show"
    >
        <div class="modal__inner">
            <div
                class="modal__overlay"
                x-show="show"
                x-on:click="closeModalOnClickAway()"
                x-transition:enter.opacity.duration.250ms
                x-transition:leave.opacity.duration.200ms
            ></div>
            <div
                aria-modal="true"
                class="modal__dialog"
                id="modal-container"
                x-bind:class="modalWidth"
                x-show="show && showActiveComponent"
                x-transition:enter.opacity.scale.95.duration.250ms
                x-transition:leave.opacity.scale.95.duration.200ms
            >
                @forelse($components as $id => $component)
                    <div x-show.immediate="activeComponent == '{{ $id }}'" x-ref="{{ $id }}" wire:key="{{ $id }}">
                        @livewire($component['name'], $component['arguments'], key($id))
                    </div>
                @empty
                @endforelse
            </div>
        </div>
    </div>
</div>
