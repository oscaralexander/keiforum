<?php

use App\Models\Poll;
use App\Models\PollOption;
use App\Models\PollVote;
use App\Models\Post;
use App\Models\Topic;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component
{
    public Topic $topic;

    public bool $isUpdating = false;

    public ?int $selectedOption = null;

    public function cancelUpdate(): void
    {
        $this->isUpdating = false;
    }

    public function edit(): void
    {
        if (!$this->userVote) {
            return;
        }

        Gate::authorize('update', $this->userVote);

        $this->selectedOption = $this->userVote?->poll_option_id;
        $this->isUpdating = true;
    }

    #[Computed]
    public function poll(): ?Poll
    {
        return $this->topic->poll()->with(['options', 'votes'])->first();
    }

    #[Computed]
    public function userVote(): ?PollVote
    {
        if (!auth()->check() || !$this->poll) {
            return null;
        }

        return $this->poll->votes->firstWhere('user_id', auth()->id());
    }

    #[Computed]
    public function totalVotes(): int
    {
        return $this->poll?->votes->count() ?? 0;
    }

    public function vote(): void
    {
        Gate::authorize('create', PollVote::class);

        if (!$this->selectedOption || !$this->poll) {
            return;
        }

        $option = $this->poll->options->find($this->selectedOption);

        if (!$option) {
            return;
        }

        $this->poll->votes()->where('user_id', auth()->id())->delete();

        PollVote::create([
            'poll_id' => $this->poll->id,
            'poll_option_id' => $this->selectedOption,
            'user_id' => auth()->id(),
        ]);

        $this->cancelUpdate();
        unset($this->poll, $this->userVote, $this->totalVotes);
    }
};
?>

<div class="panel panel--padded poll">
    <h2 class="poll__question">{{ $this->poll->question }}</h2>
    @auth
        @if ($this->userVote && !$this->isUpdating)
            <ul class="poll__results">
                @foreach ($this->poll->options as $option)
                    @php
                        $votes = $option->votes->count();
                        $percentage = $this->totalVotes > 0 ? round(($votes / $this->totalVotes) * 100) : 0;
                        $isVoted = $this->userVote->poll_option_id === $option->id;
                    @endphp
                    <li
                        @class([
                            'poll__result',
                            'poll__result--voted' => $isVoted,
                        ])
                    >
                        <div class="poll__result-icon">
                            <x-icon icon="check" />
                        </div>
                        <div class="poll__result-content">
                            <div class="flex flex-align-baseline flex-justify-spaceBetween flex-gap-m">
                                <span class="poll__result-label">{{ $option->label }}</span>
                                <span class="poll__result-percentage text-num" title="{{ trans_choice('poll.votes', $votes) }}">{{ $percentage }}%</span>
                            </div>
                            <div class="poll__result-bar">
                                <div class="poll__result-bar-fill" style="width: {{ $percentage }}%;"></div>
                            </div>
                        </div>
                    </li>
                @endforeach
            </ul>
            <ul class="meta">
                <li class="meta__item">{{ trans_choice('poll.votes', $this->totalVotes) }}</li>
                <li class="meta__item"><a href="#" wire:click.prevent="edit">{{ __('poll.change_vote') }}</a></li>
            </ul>
        @else
            <form class="flex flex-col flex-gap-m" wire:submit="vote">
                <div>
                    @foreach ($this->poll->options as $option)
                        <x-input.option
                            :label="$option->label"
                            model="selectedOption"
                            name="poll_option"
                            type="radio"
                            :value="$option->id"
                        />
                    @endforeach
                </div>
                <x-actions>
                    <x-btn primary submit>@lang('poll.vote')</x-btn>
                    @if ($this->userVote)
                        <x-btn text wire:click="cancelUpdate">Annuleren</x-btn>
                    @endif
                </x-actions>
            </form>
        @endif
    @else
        {{-- Guest: show options but no counts --}}
        <div class="flex flex-col flex-gap-m">
            <div>
                @foreach ($this->poll->options as $option)
                    <x-input.option
                        :label="$option->label"
                        model="selectedOption"
                        name="poll_option"
                        type="radio"
                        :value="$option->id"
                    />
                @endforeach
            </div>
        </div>
        <p class="text-color-lc">
            {!! __('poll.login_to_vote', ['login_url' => route('login')]) !!}
        </p>
    @endauth
</div>
