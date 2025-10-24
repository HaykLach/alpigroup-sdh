
<x-dynamic-component
    :component="$getFieldWrapperView()"
    :field="$field"
>
    <div x-data="{ state: $wire.$entangle('{{ $getStatePath() }}') }">
        @foreach($images as $image)
            <img src="{{ $image->getUrl() }}" alt="{{ $name }}" class="inline-block h-40 mr-1 mb-2">
        @endforeach

    </div>
</x-dynamic-component>
