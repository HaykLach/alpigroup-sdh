@if($media && $media->isNotEmpty())
    @foreach($media as $item)
        <button onclick="window.open('{{ $item->getUrl() }}', '_blank')"
                style="--c-50:var(--primary-50);--c-400:var(--primary-400);--c-600:var(--primary-600);"
                class="inline-block fi-badge rounded-md text-xs font-medium ring-1 ring-inset px-4 min-w-[theme(spacing.5)] py-2 tracking-tight fi-color-custom bg-custom-50 text-custom-600 ring-custom-600/10 dark:bg-custom-400/10 dark:text-custom-400 dark:ring-custom-400/30 fi-color-primary w-max"
                >
            {{ $item->name }}
        </button>
    @endforeach
@else
    <div class="fi-ta-icon flex gap-1.5 px-3 py-4">
        <svg style="--c-400:var(--danger-400);--c-500:var(--danger-500);" class="fi-ta-icon-item fi-ta-icon-item-size-lg h-6 w-6 fi-color-custom text-custom-500 dark:text-custom-400 fi-color-danger" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" data-slot="icon">
            <path stroke-linecap="round" stroke-linejoin="round" d="m9.75 9.75 4.5 4.5m0-4.5-4.5 4.5M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"></path>
        </svg>
    </div>
@endif
