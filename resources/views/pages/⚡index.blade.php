<?php

use Livewire\Component;
use Livewire\Attributes\Computed;
use App\Models\Forum;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

new class extends Component
{
    public string $title;

    #[Computed]
    public function forums(): Collection
    {
        $forums = Forum::query()
            ->withCount('topics')
            ->get();

        $forums->each(function (Forum $forum): void {
            $forum->setRelation('recentTopics',
                $forum->topics()
                    ->where('is_pinned', false)
                    ->withCount('posts')
                    ->with('latestPost.user', 'poll')
                    ->orderByDesc(
                        DB::raw('(SELECT MAX(p.id) FROM posts p WHERE p.topic_id = topics.id)')
                    )
                    ->limit(5)
                    ->get()
            );
        });

        return $forums;
    }

    public function mount(): void
    {
        $hour = now()->hour;

        if ($hour < 12) {
            $greeting = __('home.greeting_morning');
        } else if ($hour < 18) {
            $greeting = __('home.greeting_afternoon');
        } else {
            $greeting = __('home.greeting_evening');
        }

        if (auth()->check()) {
            $this->title = $greeting . ' ' . auth()->user()->first_name;
        } else {
            $this->title = __('home.title', ['greeting' => $greeting]);
        }
    }

    public function render()
    {
        return $this->view()
            ->title('De online huiskamer van Amersfoort');
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
    <x-header hide-path :intro="__('home.intro')" :title="$title">
        <x-slot:actions>
            <x-btn class="m:hide" :href="route('topic.create')" icon="plus" primary small>@lang('forum/show.new_topic')</x-btn>
            <x-btn class="m:show" :href="route('topic.create')" icon="plus" primary>@lang('forum/show.new_topic')</x-btn>
        </x-slot:actions>
    </x-header>
    <div class="panel">
        <ul class="forumList">
            @foreach ($this->forums as $forum)
                <x-forum-list-item :$forum />
            @endforeach
        </ul>
    </div>
</div>