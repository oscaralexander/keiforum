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
};

?>

<div>
    <h1>Hallo Amersfoort!</h1>
    <div class="formatted">
        <p>
            Welkom op Keiforum – het digitale dorpsplein van Amersfoort!
            Hier ontmoet je mede-Amersfoorters, deel je ideeën en blijf je op de hoogte van wat er leeft in de stad.
            Van buurtinitiatieven tot lokale tips, van actuele discussies tot creatieve plannen: Keiforum is de plek waar Amersfoort samenkomt.
        </p>
    </div>
    @foreach ($this->forums as $forum)
        <div>
            <a href="{{ route('forum.show', $forum->slug) }}" wire:navigate>{{ $forum->name }}</a>
            <span>{{ $forum->topics_count }} topics</span>
        </div>
    @endforeach
</div>