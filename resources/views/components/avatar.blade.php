@use('App\Enums\AvatarSize')

@blaze(memo: true)

@props([
    'imgOnly' => false,
    'size' => AvatarSize::S,
    'user' => null,
])

<div
    {{ $attributes->class([
        'avatar',
        'avatar--' . strtolower($size->name),
        'avatar--online' => !$imgOnly && $user->is_online,
        'avatar--premium' => !$imgOnly && $user->is_premium,
    ]) }}
    title="{{ $user->username }}"
    {{ $attributes }}
>
    <img
        alt="{{ $user->username }}"
        decoding="async"
        height="{{ $size->value / 2 }}"
        loading="lazy"
        src="{{ $user->avatarUrl(size: $size->value) }}"
        width="{{ $size->value / 2 }}"
    >
</div>