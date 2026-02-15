@props([
    'accept' => 'image/heic,image/jpeg,image/png,image/webp',
    'model' => null,
])

<div
    x-data="{
        onUploadProgress($event) {
            this.progress = $event.detail.progress;
            $refs.btn.dataset.progress = this.progress + '%';
            $refs.btn.style.setProperty('--progress', +(this.progress / 100));
        },
        progress: 0,
        uploading: false,
    }"
    x-on:livewire-upload-cancel="uploading = false"
    x-on:livewire-upload-error="uploading = false"
    x-on:livewire-upload-finish="uploading = false"
    x-on:livewire-upload-progress="onUploadProgress"
    x-on:livewire-upload-start="progress = 0; uploading = true"
    >
    <x-btn icon="image" x-ref="btn" wire:loading.class="is-uploading" wire:target="{{ $model }}">
        @lang('ui.browse')
        <input accept="{{ $accept}}" tabindex="-1" type="file" wire:model="{{ $model }}">
    </x-btn>
</div>