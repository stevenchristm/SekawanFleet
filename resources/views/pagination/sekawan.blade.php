{{-- Pagination SekawanFleet: "Menampilkan x–y dari z" + tombol halaman --}}
@if ($paginator->hasPages() || $paginator->total() > 0)
    <nav role="navigation" aria-label="Navigasi halaman"
         class="flex flex-col items-center justify-between gap-3 border-t border-slate-200 bg-slate-50 px-4 py-3 sm:flex-row sm:px-6">
        <p class="tabular text-xs text-slate-500">
            Menampilkan <span class="font-semibold text-slate-900">{{ $paginator->firstItem() ?? 0 }}–{{ $paginator->lastItem() ?? 0 }}</span>
            dari <span class="font-semibold text-slate-900">{{ number_format($paginator->total(), 0, ',', '.') }}</span> data
        </p>

        @if ($paginator->hasPages())
            <div class="flex items-center gap-1">
                @if ($paginator->onFirstPage())
                    <span class="flex h-8 items-center gap-1 rounded-lg border border-slate-200 bg-white px-2.5 text-xs text-slate-300" aria-disabled="true">
                        <span class="material-symbols-outlined text-base" aria-hidden="true">chevron_left</span><span class="hidden sm:inline">Sebelumnya</span>
                    </span>
                @else
                    <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="flex h-8 items-center gap-1 rounded-lg border border-slate-300 bg-white px-2.5 text-xs text-slate-700 hover:bg-slate-100">
                        <span class="material-symbols-outlined text-base" aria-hidden="true">chevron_left</span><span class="hidden sm:inline">Sebelumnya</span>
                    </a>
                @endif

                <span class="hidden items-center gap-1 sm:flex">
                    @foreach ($elements as $element)
                        @if (is_string($element))
                            <span class="px-1 text-slate-400">{{ $element }}</span>
                        @endif
                        @if (is_array($element))
                            @foreach ($element as $page => $url)
                                @if ($page == $paginator->currentPage())
                                    <span aria-current="page" class="tabular flex h-8 w-8 items-center justify-center rounded-lg bg-brand-700 text-xs font-semibold text-white">{{ $page }}</span>
                                @else
                                    <a href="{{ $url }}" class="tabular flex h-8 w-8 items-center justify-center rounded-lg border border-slate-200 bg-white text-xs font-semibold text-slate-600 hover:bg-slate-100" aria-label="Halaman {{ $page }}">{{ $page }}</a>
                                @endif
                            @endforeach
                        @endif
                    @endforeach
                </span>

                @if ($paginator->hasMorePages())
                    <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="flex h-8 items-center gap-1 rounded-lg border border-slate-300 bg-white px-2.5 text-xs text-slate-700 hover:bg-slate-100">
                        <span class="hidden sm:inline">Berikutnya</span><span class="material-symbols-outlined text-base" aria-hidden="true">chevron_right</span>
                    </a>
                @else
                    <span class="flex h-8 items-center gap-1 rounded-lg border border-slate-200 bg-white px-2.5 text-xs text-slate-300" aria-disabled="true">
                        <span class="hidden sm:inline">Berikutnya</span><span class="material-symbols-outlined text-base" aria-hidden="true">chevron_right</span>
                    </span>
                @endif
            </div>
        @endif
    </nav>
@endif
