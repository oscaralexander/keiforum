@if ($paginator->hasPages())
    <nav class="pagination" role="navigation" aria-label="Pagination Navigation">
        <ul class="pagination__list">
            {{-- Previous Page Link --}}
            @if ($paginator->onFirstPage())
                <li class="pagination__item">
                    <span class="pagination__link pagination__link--disabled" aria-disabled="true" aria-label="@lang('pagination.previous')">
                        <x-icon icon="arrow-left" />
                        <span>Vorige</span>
                    </span>
                </li>
            @else
                <li class="pagination__item">
                    <button type="button" class="pagination__link" wire:click="previousPage('{{ $paginator->getPageName() }}')" wire:loading.attr="disabled" rel="prev" aria-label="@lang('pagination.previous')">
                        <x-icon icon="arrow-left" />
                        <span>Vorige</span>
                    </button>
                </li>
            @endif

            {{-- Next Page Link --}}
            @if ($paginator->hasMorePages())
                <li class="pagination__item">
                    <button type="button" class="pagination__link" wire:click="nextPage('{{ $paginator->getPageName() }}')" wire:loading.attr="disabled" rel="next" aria-label="@lang('pagination.next')">
                        <span>Volgende</span>
                        <x-icon icon="arrow-right" />
                    </button>
                </li>
            @else
                <li class="pagination__item">
                    <span class="pagination__link pagination__link--disabled" aria-disabled="true" aria-label="@lang('pagination.next')">
                        <span>Volgende</span>
                        <x-icon icon="arrow-right" />
                    </span>
                </li>
            @endif
        </ul>
    </nav>
@endif

