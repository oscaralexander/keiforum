<?php

use Livewire\Component;
use Livewire\Attributes\Computed;
use App\Models\Forum;
use Illuminate\Database\Eloquent\Collection;

new class extends Component
{
    #[Computed]
    public function forums(): Collection
    {
        return Forum::query()
            ->withCount('topics')
            ->get();
    }

    public function render()
    {
        return $this->view()
            ->title('Hallo Amersfoort!');
    }

    #[Computed]
    public function schema(): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'WebSite',
            'about' => [
                '@type' => 'Place',
                'address' => [
                    '@type' => 'PostalAddress',
                    'addressLocality' => 'Amersfoort',
                    'addressCountry' => 'NL',
                ],
                'geo' => [
                    '@type' => 'GeoCoordinates',
                    'latitude' => 52.156111,
                    'longitude' => 5.387827,
                ],
                'name' => 'Amersfoort',
            ],
            'description' => 'De online huiskamer van Amersfoort.',
            'headline' => 'De online huiskamer van Amersfoort.',
            'name' => config('app.name'),
            'url' => url('/'),
            'publisher' => [
                '@type' => 'Organization',
                'logo' => [
                    '@type' => 'ImageObject',
                    'url' => asset('assets/img/keiforum-logo.svg'),
                ],
                'name' => config('app.name'),
            ],
        ];
    }
};

?>

@stack('head')

<div>
    <x-schema :data="$this->schema" />
    <x-header hide-path :title="__('home.title')" />
    <div class="panel">
        <ul class="forumList">
            @foreach ($this->forums as $forum)
                <li class="forumListItem">
                    <div class="forumListItem__icon">
                        <x-icon icon="{{ $forum->icon }}" />
                    </div>
                    <div class="forumListItem__text">
                        <div class="forumListItem__nameCount">
                            <a class="forumListItem__name" href="{{ route('forum.show', $forum->slug) }}" wire:navigate>{{ $forum->name }}</a>
                            <div class="forumListItem__count">{{ trans_choice('forum/index.topics', $forum->topics_count, ['count' => $forum->topics_count]) }}</div>
                        </div>
                        <div class="forumListItem__description">{{ $forum->description }}</div>
                    </div>
                </li>
            @endforeach
        </ul>
    </div>
</div>