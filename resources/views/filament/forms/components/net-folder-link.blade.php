<x-dynamic-component
    :component="$getFieldWrapperView()"
    :field="$field"
>
    @php

    @endphp
    <div x-data="{ state: $wire.$entangle('{{ $getStatePath() }}') }">
        <code style="--c-50:var(--primary-50);--c-400:var(--primary-400);--c-600:var(--primary-600);" class="inline-block fi-badge rounded-md text-xs font-medium ring-1 ring-inset px-4 min-w-[theme(spacing.5)] py-2.5 tracking-tight fi-color-custom bg-custom-50 ring-custom-600/10 dark:bg-custom-400/10 dark:ring-custom-400/30 fi-color-primary w-max">
            @if (!empty($path))
                {{ $path }}
            @else
                {{ __('kein Netzlaufwerk Ordner vorhanden') }}
            @endif
        </code>
    </div>
</x-dynamic-component>
