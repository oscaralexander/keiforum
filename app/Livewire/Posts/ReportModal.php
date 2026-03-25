<?php

namespace App\Livewire\Posts;

use App\Enums\ReportType;
use App\Models\Report;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use LivewireUI\Modal\ModalComponent;

class ReportModal extends ModalComponent
{
    public ?string $comment = null;

    public bool $isSubmitted = false;

    #[Locked]
    public int $postId;

    public ?ReportType $type = null;

    public function mount(): void
    {
        $this->type = $this->report->type;
        $this->comment = $this->report->comment;
    }

    #[Computed]
    public function report(): Report
    {
        return Report::where([
            'post_id' => $this->postId,
            'user_id' => auth('web')->id(),
        ])->firstOrNew([
            'post_id' => $this->postId,
            'user_id' => auth('web')->id(),
        ]);
    }

    public function rules(): array
    {
        return [
            'comment' => ['nullable', 'string', 'max:1000'],
            'type' => ['required', Rule::enum(ReportType::class)],
        ];
    }

    public function submit(): void
    {
        $this->authorize('create', Report::class);

        $this->validate();

        $this->report->comment = $this->comment ?: null;
        $this->report->type = $this->type;
        $this->report->save();

        $this->isSubmitted = true;
    }

    public function render(): View
    {
        return view('livewire.posts.report-modal', [
            'reportTypes' => ReportType::cases(),
        ]);
    }
}
