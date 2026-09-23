@props(['paginator'])
<div class="pagination-row">
    <span>{{ $paginator->total() ? $paginator->firstItem() : 0 }}&ndash;{{ $paginator->lastItem() ?? 0 }} of {{ number_format($paginator->total()) }} records</span>
    @if($paginator->hasPages())
    <nav class="pagination-links" aria-label="Pagination">
        @if(!$paginator->onFirstPage())<a href="{{ $paginator->previousPageUrl() }}" data-nav aria-label="Previous page">&larr;</a>@endif
        @php
            // Clamp only the display window: a large page query must not generate thousands of links.
            $center = min(max(1, $paginator->currentPage()), $paginator->lastPage());
            $pages = array_unique(array_merge([1], range(max(1, $center - 2), min($paginator->lastPage(), $center + 2)), [$paginator->lastPage()]));
            sort($pages);
            $previous = 0;
        @endphp
        @foreach($pages as $page)
            @if($page-$previous>1)<span>&hellip;</span>@endif
            <a data-nav href="{{ $paginator->url($page) }}" @class(['current'=>$page===$paginator->currentPage()]) @if($page===$paginator->currentPage()) aria-current="page" @endif>{{ $page }}</a>
            @php $previous=$page; @endphp
        @endforeach
        @if($paginator->hasMorePages())<a href="{{ $paginator->nextPageUrl() }}" data-nav aria-label="Next page">&rarr;</a>@endif
    </nav>
    @endif
</div>
