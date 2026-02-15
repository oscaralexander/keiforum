@props([
    'model' => null,
])

<div x-data="editor(@entangle($model))" class="editor" x-ref="editorWrapper">
    <div class="editor__box">
        <header class="editor__header">
            <div class="editor__toolbar">
                <button class="editor__toolbar-btn" :class="{ 'is-active': isActive('strong') }" tabindex="-1" title="@lang('editor.bold')" type="button" x-on:click="toggleBold()"><x-icon icon="bold" /></button>
                <button class="editor__toolbar-btn" :class="{ 'is-active': isActive('em') }" tabindex="-1" title="@lang('editor.italic')" type="button" x-on:click="toggleItalic()"><x-icon icon="italic" /></button>
                <button class="editor__toolbar-btn" :class="{ 'is-active': isActive('strikethrough') }" tabindex="-1" title="@lang('editor.strikethrough')" type="button" x-on:click="toggleStrikethrough()"><x-icon icon="strikethrough" /></button>
                <button class="editor__toolbar-btn" title="@lang('editor.link')" tabindex="-1" type="button" x-on:click="toggleLink()" x-show="!isActive('link')"><x-icon icon="link" /></button>
                <button class="editor__toolbar-btn" title="@lang('editor.unlink')" tabindex="-1" type="button" x-on:click="unlink()" x-show="isActive('link')"><x-icon icon="unlink" /></button>
                <button class="editor__toolbar-btn" :class="{ 'is-active': isActive('bullet_list') }" tabindex="-1" title="@lang('editor.bullet_list')" type="button" x-on:click="toggleUnorderedList()"><x-icon icon="list" /></button>
                <button class="editor__toolbar-btn" :class="{ 'is-active': isActive('ordered_list') }" tabindex="-1" title="@lang('editor.ordered_list')" type="button" x-on:click="toggleOrderedList()"><x-icon icon="list-ordered" /></button> 
                <button class="editor__toolbar-btn" :class="{ 'is-active': isActive('blockquote') }" tabindex="-1" title="@lang('editor.blockquote')" type="button" x-on:click="toggleBlockquote()"><x-icon icon="text-quote" /></button>
                <button class="editor__toolbar-btn" tabindex="-1" title="@lang('editor.image')" type="button" x-show="!isUploading">
                    <x-icon icon="image" />
                    <input accept="image/heic,image/jpeg,image/png,image/webp" tabindex="-1" type="file" x-ref="imageInput">
                </button>
                <div class="editor__toolbar-spinner" x-cloak x-show="isUploading"><x-icon icon="loader-circle" /></div>
            </div>
            <div class="editor__progressBar" style="--progress: 0;" x-cloak x-ref="progressBar" x-show="isUploading"></div>
        </header>
        <div class="editor__view js-editorView" wire:ignore x-ignore></div>
    </div>
    <div 
        class="editor__mentions"
        style="display: none;"
        :style="`top: ${mentionCoords.top}; left: ${mentionCoords.left}`"
        x-show="showMentionMenu && users.length > 0"
        x-transition.opacity.duration.100ms
    >
        <template x-for="(user, index) in users" :key="user.username">
            <div 
                class="editor__mention-item"
                :class="{ 'is-active': index === mentionIndex }"
                x-on:click="selectUser(user)"
                x-on:mouseenter="mentionIndex = index"
            >
                <img :alt="user.name" :src="user.avatar" class="editor__mention-item-avatar">
                <span x-text="user.username"></span>
            </div>
        </template>
    </div>
</div>