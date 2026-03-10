@blaze

@props(['post'])

<li class="post post--deleted" id="post-{{ $post->id }}">
    <header class="post__header">
        <div class="post__user">
            <x-avatar :user="$post->user" />
            <div class="post__user-name">
                <a class="post__username" href="{{ route('member.show', $post->user) }}" wire:navigate>{{ $post->user->username }}</a>
                @auth
                    <ul class="meta">
                        <li class="meta__item">{{ $post->user->name }}</li>
                        @if ($post->user->area)
                            <li class="meta__item">{{ $post->user->area->name }}</li>
                        @endif
                    </ul>
                @endauth
            </div>
        </div>
    </header>
    <div class="post__body">
        <p class="text-color-lc">
            @if ($post->deleted_by_id == $post->user_id)
                @lang('post/show.deleted_by_author')
            @else
                @lang('post/show.deleted_by_admin')
            @endif
        </p>
    </div>
</li>