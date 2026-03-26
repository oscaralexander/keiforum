@if ($paginator->hasPages())
    <nav class="pagination" role="navigation">
        <div>
            @lang('pagination.pagination', ['page' => $paginator->currentPage(), 'lastPage' => $paginator->lastPage(), 'total' => $paginator->total()])
        </div>
        <div class="pagination__list">
            @if ($paginator->onFirstPage())
                <span class="pagination__link pagination__link--disabled">
                    <x-icon icon="arrow-left" />
                </span>
            @else
                <a aria-label="@lang('pagination.previous')" class="pagination__link" href="{{ $paginator->previousPageUrl() }}" rel="prev" wire:loading.attr="disabled" wire:navigate>
                    <x-icon icon="arrow-left" />
                </a>
            @endif
            @foreach ($elements as $element)
                @if (is_string($element))
                    <span aria-disabled="true" class="pagination__link pagination__link--disabled">&hellip;</span>
                @endif
                @if (is_array($element))
                    @foreach ($element as $page => $url)
                        @if ($page == $paginator->currentPage())
                            <span aria-current="page" class="pagination__link pagination__link--active">{{ $page }}</span>
                        @else
                            <a aria-label="@lang('pagination.go_to_page', ['page' => $page])" class="pagination__link" href="{{ $url }}" wire:loading.attr="disabled" wire:navigate>
                                {{ $page }}
                            </a>
                        @endif
                    @endforeach
                @endif
            @endforeach
            @if ($paginator->hasMorePages())
                <a aria-label="@lang('pagination.next')" class="pagination__link" href="{{ $paginator->nextPageUrl() }}" rel="next" wire:loading.attr="disabled" wire:navigate><x-icon icon="arrow-right" /></a>
            @else
                <span class="pagination__link pagination__link--disabled"><x-icon icon="arrow-right" /></span>
            @endif
        </div>
    </nav>
@endif

