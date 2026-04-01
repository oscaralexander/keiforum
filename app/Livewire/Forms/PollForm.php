<?php

namespace App\Livewire\Forms;

use App\Models\Poll;
use App\Models\PollOption;
use App\Models\Topic;
use Illuminate\Support\Str;
use Livewire\Form;

class PollForm extends Form
{
    /**
     * Whether the poll is active (being created or edited).
     * Used to conditionally validate and show the editor.
     */
    public bool $active = false;

    /**
     * The topic author's ID, set when editing an existing poll.
     * Used to determine which options are locked (have non-author votes).
     */
    public ?int $authorId = null;

    public string $question = '';

    /**
     * Each item: ['id' => int|string, 'label' => string, 'isLocked' => bool]
     * Existing options use their DB id (int); new options use a UUID string.
     */
    public array $options = [];

    protected function rules(): array
    {
        if (! $this->active) {
            return [];
        }

        return [
            'question' => ['required', 'max:255'],
            'options' => ['required', 'array', 'min:2'],
            'options.*.label' => ['required', 'string', 'max:255'],
        ];
    }

    public function updatedActive(bool $value): void
    {
        if ($value && empty($this->options)) {
            $this->addOption();
            $this->addOption();
        }
    }

    public function addOption(): void
    {
        $this->options[] = ['id' => Str::uuid()->toString(), 'label' => '', 'isLocked' => false];
    }

    public function removeOption(string $id): void
    {
        if (is_numeric($id) && $this->authorId !== null) {
            $option = PollOption::find($id);
            if ($option && $option->votes()->where('user_id', '!=', $this->authorId)->exists()) {
                return;
            }
        }

        $this->options = collect($this->options)
            ->reject(fn ($opt) => (string) $opt['id'] === $id)
            ->values()
            ->all();
    }

    public function reorderOptions(string $id, int $position): void
    {
        $options = collect($this->options);
        $item = $options->firstWhere('id', (int) $id) ?? $options->firstWhere('id', $id);

        if (! $item) {
            return;
        }

        $without = $options->reject(fn ($opt) => (string) $opt['id'] === $id)->values();

        $this->options = $without
            ->slice(0, $position)
            ->push($item)
            ->merge($without->slice($position))
            ->values()
            ->all();
    }

    /**
     * Populate the form from an existing poll, marking options as locked
     * if they have votes from users other than the topic author.
     */
    public function loadFromPoll(Poll $poll, int $authorId): void
    {
        $this->authorId = $authorId;
        $this->active = true;
        $this->question = $poll->question;
        $this->options = $poll->options->map(fn ($opt) => [
            'id' => $opt->id,
            'label' => $opt->label,
            'isLocked' => $opt->votes()->where('user_id', '!=', $authorId)->exists(),
        ])->all();
    }

    /**
     * Create a new poll and its options for the given topic.
     */
    public function saveNew(Topic $topic): void
    {
        $poll = Poll::create([
            'topic_id' => $topic->id,
            'question' => $this->question,
        ]);

        foreach ($this->options as $index => $option) {
            PollOption::create([
                'poll_id' => $poll->id,
                'label' => $option['label'],
                'order' => $index,
            ]);
        }
    }

    /**
     * Persist edits to an existing poll, respecting locked options.
     */
    public function saveExisting(Poll $poll): void
    {
        $poll->update(['question' => $this->question]);

        $existingIds = $poll->options->pluck('id')->toArray();
        $submittedIds = collect($this->options)
            ->filter(fn ($o) => is_numeric($o['id']))
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->toArray();

        // Delete removed options, server-side verifying they are unlocked
        foreach (array_diff($existingIds, $submittedIds) as $removedId) {
            $option = PollOption::find($removedId);
            if ($option && ! $this->isLocked($option)) {
                $option->delete();
            }
        }

        // Update existing and create new options
        foreach ($this->options as $index => $optionData) {
            if (is_numeric($optionData['id'])) {
                $option = PollOption::find($optionData['id']);
                if ($option && $option->poll_id === $poll->id) {
                    $updateData = ['order' => $index];
                    if (! $this->isLocked($option)) {
                        $updateData['label'] = $optionData['label'];
                    }
                    $option->update($updateData);
                }
            } else {
                PollOption::create([
                    'poll_id' => $poll->id,
                    'label' => $optionData['label'],
                    'order' => $index,
                ]);
            }
        }
    }

    private function isLocked(PollOption $option): bool
    {
        if ($this->authorId === null) {
            return false;
        }

        return $option->votes()->where('user_id', '!=', $this->authorId)->exists();
    }
}
