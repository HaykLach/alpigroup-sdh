@if($media && $media->isNotEmpty())
    @foreach($media as $item)
        <button onclick="window.open('{{ $item->getUrl() }}', '_blank')"
                style="--c-50:var(--primary-50);--c-400:var(--primary-400);--c-600:var(--primary-600);"
                class="inline-block fi-badge rounded-md text-xs font-medium ring-1 ring-inset px-4 min-w-[theme(spacing.5)] py-2 tracking-tight fi-color-custom bg-custom-50 text-custom-600 ring-custom-600/10 dark:bg-custom-400/10 dark:text-custom-400 dark:ring-custom-400/30 fi-color-primary w-max"
                >
            {{ $item->name }}
        </button>
    @endforeach
@endif
