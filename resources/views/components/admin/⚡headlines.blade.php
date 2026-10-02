<?php

use App\Enums\HeadlineVerdict;
use App\Models\Headline;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component
{
    public const LIMIT = 10;

    public function mount(): void
    {
        abort_unless(auth()->user()?->is_admin, 403);
    }

    #[Computed]
    public function headlines(): Collection
    {
        return Headline::query()
            ->with(['topic' => fn ($query) => $query->withCount('posts')->with('forum')])
            ->latest('pub_date')
            ->limit(self::LIMIT)
            ->get();
    }

    public function accept(int $headlineId): void
    {
        abort_unless(auth()->user()?->is_admin, 403);

        Headline::query()->findOrFail($headlineId)->accept();
        unset($this->headlines);

        $this->dispatch('toast', message: __('admin/index.headlines.accepted'), type: 'success');
    }

    public function reject(int $headlineId): void
    {
        abort_unless(auth()->user()?->is_admin, 403);

        Headline::query()->findOrFail($headlineId)->reject();
        unset($this->headlines);

        $this->dispatch('toast', message: __('admin/index.headlines.rejected'), type: 'success');
    }
};
?>

<section class="flex flex-col flex-gap-m">
    <h2>@lang('admin/index.headlines.title')</h2>
    <div class="panel">
        @if ($this->headlines->isNotEmpty())
            <ol>
                @foreach ($this->headlines as $headline)
                    <li class="headlineListItem" wire:key="headline-{{ $headline->id }}">
                        <div class="headlineListItem__text">
                            <div>
                                <a class="headlineListItem__title" href="{{ $headline->link }}" target="_blank">{{ $headline->title }}</a>
                                <span class="headlineListItem__verdict headlineListItem__verdict--{{ $headline->verdict?->value ?? 'pending' }}">
                                    @lang('admin/index.headlines.verdict.' . ($headline->verdict?->value ?? 'pending'))
                                </span>
                            </div>
                            @if ($headline->verdict_reason)
                                <p class="headlineListItem__reason">{{ $headline->verdict_reason }}</p>
                            @endif
                            <ul class="meta">
                                <li class="meta__item">{{ time_diff($headline->pub_date) }} @lang('ui.ago')</li>
                                @if ($headline->topic)
                                    <li class="meta__item">
                                        @lang($headline->topic->is_visible ? 'admin/index.headlines.visible' : 'admin/index.headlines.hidden')
                                    </li>
                                    <li class="meta__item">
                                        <x-icon icon="message-circle" />
                                        {{ $headline->topic->posts_count - 1 }}
                                    </li>
                                    <li class="meta__item">
                                        <a href="{{ route('topic.show', [$headline->topic->forum, $headline->topic, $headline->topic->slug]) }}" wire:navigate>
                                            @lang('admin/index.headlines.view_topic')
                                            <x-icon icon="arrow-right" />
                                        </a>
                                    </li>
                                @endif
                            </ul>
                        </div>
                        <div class="headlineListItem__actions">
                            @if ($headline->verdict !== HeadlineVerdict::APPROVED)
                                <x-btn
                                    :aria-label="__('admin/index.headlines.accept')"
                                    good
                                    icon="check"
                                    small
                                    :title="__('admin/index.headlines.accept')"
                                    wire:click="accept({{ $headline->id }})"
                                />
                            @endif
                            @if ($headline->verdict !== HeadlineVerdict::BLOCKED)
                                <x-btn
                                    :aria-label="__('admin/index.headlines.reject')"
                                    danger
                                    icon="x"
                                    small
                                    :title="__('admin/index.headlines.reject')"
                                    wire:click="reject({{ $headline->id }})"
                                    wire:confirm="{{ __('admin/index.headlines.confirm_reject') }}"
                                />
                            @endif
                        </div>
                    </li>
                @endforeach
            </ol>
        @else
            <p class="panel--padded">@lang('admin/index.headlines.empty')</p>
        @endif
    </div>
</section>
