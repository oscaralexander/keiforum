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

    public bool $isChangingVote = false;

    public ?int $selectedOption = null;

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

    public function startChangeVote(): void
    {
        Gate::authorize('create', Post::class);

        $this->selectedOption = $this->userVote?->poll_option_id;
        $this->isChangingVote = true;
    }

    public function vote(): void
    {
        Gate::authorize('create', Post::class);

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

        $this->isChangingVote = false;
        unset($this->poll, $this->userVote, $this->totalVotes);
    }
};
?>

<div class="panel panel--padded">
    @if ($this->poll)
        <div class="flex flex-col flex-gap-m">
            <p class="text-weight-bold">{{ $this->poll->question }}</p>

            @auth
                @if ($this->userVote && !$this->isChangingVote)
                    {{-- Results --}}
                    <ul class="flex flex-col flex-gap-s">
                        @foreach ($this->poll->options as $option)
                            @php
                                $votes = $option->votes->count();
                                $percentage = $this->totalVotes > 0 ? round(($votes / $this->totalVotes) * 100) : 0;
                                $isVoted = $this->userVote->poll_option_id === $option->id;
                            @endphp
                            <li class="flex flex-col flex-gap-xs">
                                <div class="flex flex-align-center flex-justify-spaceBetween flex-gap-s">
                                    <span @class(['text-weight-bold' => $isVoted])>{{ $option->label }}</span>
                                    <span class="text-color-lc text-size-s">{{ $percentage }}%</span>
                                </div>
                                <div class="poll-bar">
                                    <div
                                        class="poll-bar__fill"
                                        @class(['poll-bar__fill--voted' => $isVoted])
                                        style="width: {{ $percentage }}%"
                                    ></div>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                    <div class="flex flex-align-center flex-gap-m">
                        <p class="text-color-lc text-size-s">
                            {{ trans_choice('poll.total_votes', $this->totalVotes) }}
                            &middot; {{ __('poll.voted') }}
                        </p>
                        <x-btn small text wire:click="startChangeVote">{{ __('poll.change_vote') }}</x-btn>
                    </div>
                @else
                    {{-- Voting form --}}
                    <form wire:submit="vote" class="flex flex-col flex-gap-m">
                        <ul class="flex flex-col flex-gap-s">
                            @foreach ($this->poll->options as $option)
                                <li>
                                    <x-input.option
                                        :label="$option->label"
                                        model="selectedOption"
                                        name="poll_option"
                                        type="radio"
                                        :value="$option->id"
                                    />
                                </li>
                            @endforeach
                        </ul>
                        <div>
                            <x-btn primary submit>
                                {{ $this->isChangingVote ? __('poll.change_vote') : __('poll.vote') }}
                            </x-btn>
                        </div>
                    </form>
                @endif
            @else
                {{-- Guest: show options but no counts --}}
                <ul class="flex flex-col flex-gap-s">
                    @foreach ($this->poll->options as $option)
                        <li class="text-color-lc">{{ $option->label }}</li>
                    @endforeach
                </ul>
                <p class="text-color-lc text-size-s">
                    {!! __('poll.login_to_vote', ['login_url' => route('login')]) !!}
                </p>
            @endauth
        </div>
    @endif
</div>
