<div>
    @include('handbook::markdown_style')
<x-rpd::card>
    <x-rpd::view :title="$collection->name">
        <x-slot name="buttons">
            @if ($canEdit)
                <x-rpd::button :route="['handbook.collections.edit', $collection->id]" color="outline-dark" label="Edit" />
                <x-rpd::button :href="route_lang('handbook.documents.edit', ['collection' => $collection->id])" color="outline-primary" label="New document" />
            @endif
        </x-slot>

        @if ($collection->description)
            <p class="text-secondary">{{ $collection->description }}</p>
        @endif

        @if ($tree)
            @include('handbook::tree', ['nodes' => $tree, 'current' => null])
        @else
            <div class="text-center text-secondary py-5">
                <p class="mb-0">{{ __('This collection has no documents yet.') }}</p>
            </div>
        @endif
    </x-rpd::view>
</x-rpd::card>
</div>
