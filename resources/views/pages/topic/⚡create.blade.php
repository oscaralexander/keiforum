<?php

use App\Enums\AdType;
use App\Models\Area;
use App\Models\Forum;
use App\Models\Post;
use App\Models\Topic;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\Attributes\Computed;

new class extends Component
{   
    public ?AdType $ad_type = null;

    public string $body;

    public ?Forum $forum = null;
    
    public int $forum_id;

    public bool $subscribe = false;

    public string $title;

    public array $topicAreas = [];

    #[Computed]
    public function areas()
    {
        return Area::all();
    }

    #[Computed]
    public function forums()
    {
        return Forum::all();
    }

    public function mount(?Forum $forum = null)
    {
        $this->forum = $forum;

        if ($this->forum) {
            $this->forum_id = $this->forum->id;

            if ($this->forum->is_marketplace) {
                $this->ad_type = AdType::OFFERED;
                $this->topicAreas = array_filter([auth()->user()->area_id]);
            }
        }
    }

    #[Computed]
    public function path(): array
    {
        $path = [];

        if ($this->forum) {
            $path[] = ['label' => $this->forum->name, 'href' => route('forum.show', $this->forum)];
        }

        return $path;
    }

    public function render()
    {
        return $this->view()
            ->title(__('topic/create.title_' . ($this->forum && $this->forum->is_marketplace ? 'ad' : 'topic')));
    }

    public function rules()
    {
        $rules = [
            'title' => ['required', 'max:255'],
            'topicAreas' => ['nullable', 'array'],
            'topicAreas.*' => ['exists:areas,id'],
            'body' => ['required'],
        ];

        if ($this->forum && $this->forum->is_marketplace) {
            $rules['ad_type'] = ['nullable', Rule::enum(AdType::class)];
        }

        return $rules;
    }

    public function submit()
    {
        $this->validate();

        // Create Topic
        $topic = Topic::create([
            'ad_type' => $this->ad_type,
            'title' => $this->title,
            'forum_id' => $this->forum_id,
            'user_id' => auth()->id(),
        ]);

        if (!empty($this->topicAreas)) {
            // Attach Areas to Topic
            $topic->areas()->sync($this->topicAreas);
        }

        // Create Post
        Post::create([
            'body' => $this->body,
            'topic_id' => $topic->id,
            'user_id' => auth()->id(),
        ]);

        $topic->trackedByUsers()->syncWithoutDetaching([
            auth()->id() => ['is_subscribed' => $this->subscribe],
        ]);

        $this->redirect(route('topic.show', [$this->forum, $topic]));
    }
};
?>

@auth
    @push('scripts.auth')
        @vite(['resources/js/auth.js'])
    @endpush
@endauth

<div>
    <x-header
        :path="$this->path"
        :title="__('topic/create.title_' . ($forum && $forum->is_marketplace ? 'ad' : 'topic'))"
    />
    <div class="panel panel--padded">
        <form wire:submit="submit">
            <div class="flex flex-col flex-gap-xl">
                <div class="flex flex-col flex-gap-m">
                    <x-field :label="__('topic/form.title.label')" model="title">
                        <x-input.text large model="title" required />
                    </x-field>
                    <div class="flex flex-col flex-gap-m l:flex-gap-m l:flex-row">                    
                        <x-field
                            class="flex-flex"
                            :label="__('topic/form.forum.label')"
                            model="forum_id"
                        >
                            <x-input.select
                                model="forum_id"
                                :options="$this->forums->pluck('name', 'id')"
                                :placeholder="__('topic/form.forum.placeholder')"
                                required
                            />
                        </x-field>
                        <x-field
                            class="flex-flex"
                            :label="__('topic/form.topic_areas.label')"
                            model="topicAreas"
                        >
                            <x-input.multi-select
                                model="topicAreas"
                                :options="$this->areas"
                                :placeholder="__('topic/form.topic_areas.placeholder')"
                            />
                        </x-field>
                    </div>
                    <x-field :label="__('topic/form.ad_type.label')" model="ad_type" x-cloak x-show="$wire.forum_id == 2">
                        <div class="flex flex-gap-m">
                            @foreach (AdType::options() as $value => $label)
                                <x-input.option
                                    :label="$label"
                                    model="ad_type"
                                    name="ad_type"
                                    type="radio"
                                    :value="$value"
                                />
                            @endforeach
                            @if (auth()->id() == 1)
                                <x-input.option
                                    :label="__('topic/form.ad_type.null')"
                                    model="ad_type"
                                    name="ad_type"
                                    type="radio"
                                    value=""
                                />
                            @endif
                        </div>
                    </x-field>
                    <x-field :label="__('topic/form.body.label')" model="body">
                        <x-editor model="body" required />
                    </x-field>
                    <x-input.toggle :label="__('topic/form.subscribe.label')" wire:model.live="subscribe" />
                </div>
                <div class="flex flex-align-center flex-gap-m">
                    <x-btn primary submit>@lang('ui.post')</x-btn>
                    @if ($this->forum)
                        <x-btn text href="{{ route('forum.show', $this->forum) }}">@lang('ui.cancel')</x-btn>
                    @else
                        <x-btn text href="{{ route('home') }}">@lang('ui.cancel')</x-btn>
                    @endif
                </div>
            </div>
        </form>
    </div>
</div>