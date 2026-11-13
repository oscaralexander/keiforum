@if ($paginator->hasPages())
    <nav class="pagination" role="navigation">
        <div class="pagination__list">
            @if ($paginator->onFirstPage())
                <span aria-disabled="true" class="pagination__link pagination__link--disabled"><x-icon icon="arrow-left-to-line" /></span>
                <span aria-disabled="true" class="pagination__link pagination__link--disabled"><x-icon icon="arrow-left" /></span>
            @else
                <a aria-label="@lang('pagination.first')" class="pagination__link" href="{{ $paginator->url(1) }}" wire:loading.attr="disabled" wire:navigate><x-icon icon="arrow-left-to-line" /></a>
                <a aria-label="@lang('pagination.previous')" class="pagination__link" href="{{ $paginator->previousPageUrl() }}" rel="prev" wire:loading.attr="disabled" wire:navigate><x-icon icon="arrow-left" /></a>
            @endif
        </div>
        <div class="pagination__summary">
            @lang('pagination.page', ['page' => $paginator->currentPage(), 'lastPage' => $paginator->lastPage()])
        </div>
        <div class="pagination__list">
            @if ($paginator->hasMorePages())
                <a aria-label="@lang('pagination.next')" class="pagination__link" href="{{ $paginator->nextPageUrl() }}" rel="next" wire:loading.attr="disabled" wire:navigate><x-icon icon="arrow-right" /></a>
                <a aria-label="@lang('pagination.last')" class="pagination__link" href="{{ $paginator->url($paginator->lastPage()) }}" wire:loading.attr="disabled" wire:navigate><x-icon icon="arrow-right-to-line" /></a>
            @else
                <span aria-disabled="true" class="pagination__link pagination__link--disabled"><x-icon icon="arrow-right" /></span>
                <span aria-disabled="true" class="pagination__link pagination__link--disabled"><x-icon icon="arrow-right-to-line" /></span>
            @endif
        </div>
    </nav>
@endif
