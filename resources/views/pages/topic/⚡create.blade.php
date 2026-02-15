<?php

use App\Models\Area;
use App\Models\Forum;
use App\Models\Post;
use App\Models\Topic;
use Livewire\Component;
use Livewire\Attributes\Computed;

new class extends Component
{   
    public string $body;

    public Forum $forum;

    public int $forum_id;

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

    public function mount(Forum $forum)
    {
        $this->forum = $forum;
        $this->forum_id = $forum->id;
    }

    public function rules()
    {
        return [
            'title' => 'required|string|max:255',
            'topicAreas' => 'required|array',
            'topicAreas.*' => 'required|exists:areas,id',
            'body' => 'required|string',
        ];
    }

    public function submit()
    {
        $this->validate();

        // Create Topic
        $topic = Topic::create([
            'title' => $this->title,
            'forum_id' => $this->forum->id,
            'user_id' => auth()->id(),
        ]);

        // Attach Areas to Topic
        $topic->areas()->sync($this->topicAreas);

        // Create Post
        Post::create([
            'body' => $this->body,
            'topic_id' => $topic->id,
            'user_id' => auth()->id(),
        ]);

        $this->redirect(route('topic.show', [$this->forum, $topic]));
    }
};
?>

<div>
    <header class="page__header">
        <x-path :items="[
            ['label' => $forum->name, 'href' => route('forum.show', $forum)],
        ]" />
        <h1>@lang('topic/create.title')</h1>
    </header>
    <div class="panel panel--padded">
        <form wire:submit="submit">
            <div class="flex flex-col flex-gap-xl">
                <div class="flex flex-col flex-gap-m">
                    <x-field :label="__('topic/form.title.label')" model="title">
                        <x-input.text model="title" required />
                    </x-field>
                    <div class="flex flex-col flex-gap-m l:flex-gap-m l:flex-row">                    
                        <x-field
                            class="flex-flex"
                            :label="__('topic/form.forum.label')"
                            model="topicAreas"
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
                            <x-input.multi-select model="topicAreas" :options="$this->areas" :placeholder="__('topic/form.topic_areas.placeholder')" />
                        </x-field>
                    </div>
                    <x-field :label="__('topic/form.body.label')" model="body">
                        <x-editor model="body" required />
                    </x-field>
                </div>
                <div class="flex flex-align-center flex-gap-m">
                    <x-btn primary submit>@lang('ui.save')</x-btn>
                    <x-btn text href="{{ route('forum.show', $forum) }}">@lang('ui.cancel')</x-btn>
                </div>
            </div>
        </form>
    </div>
</div>