<?php

use App\Models\Post;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Livewire\Component;
use Livewire\Attributes\Computed;
use Livewire\WithPagination;

new class extends Component
{
    use WithPagination;

    public string $pageName = 'p';

    public function mount(): void
    {
        abort_unless(auth()->user()?->is_admin, 403);
    }

    #[Computed]
    public function posts(): LengthAwarePaginator
    {
        return Post::query()
            ->whereHas('reports')
            ->withCount('reports')
            ->withMax('reports', 'created_at')
            ->with(['likes', 'user.area', 'topic.forum'])
            ->orderByDesc('reports_count')
            ->orderByDesc('reports_max_created_at')
            ->paginate(Post::PAGINATE_COUNT, pageName: 'p');
    }

    public function render()
    {
        return $this->view()->title('Admin');
    }
};
?>

<div>
    <x-header hide-path title="Admin" />
    <div class="flex flex-col flex-gap-l">
        @if ($this->posts->isNotEmpty())
            <div class="panel">
                <ol>
                    @foreach ($this->posts as $post)
                        <livewire:post
                            :post="$post"
                            :number="(($this->posts->currentPage() - 1) * Post::PAGINATE_COUNT) + ($loop->iteration)"
                            wire:key="post-{{ $post->id }}"
                        />
                    @endforeach
                </ol>
            </div>
            {{ $this->posts->links() }}
        @else
            <div class="panel panel--padded">
                <p>@lang('admin/index.no_reported_posts')</p>
            </div>
        @endif
    </div>
</div>
