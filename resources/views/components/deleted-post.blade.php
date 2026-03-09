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
            @lang('post/show.deleted_by_user', ['username' => $post->deletedBy->username])
        </p>
    </div>
</li>