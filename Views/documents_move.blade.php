<div>
<x-rpd::card>
    <x-rpd::edit title="Move document">
        <x-slot name="buttons">
            <x-rpd::button :route="['handbook.documents.view', $document->id]" color="outline-dark" label="Back" />
        </x-slot>

        <p class="text-secondary">{{ $document->label() }}</p>
        <div class="row">
            <x-rpd::select col="col-md-5" model="document.collection_id" :options="$collections" label="Collection" />
            <x-rpd::select col="col-md-5" model="document.parent_id" :options="$parents" label="Inside" placeholder="(top level)" />
            <x-rpd::input col="col-md-2" type="number" model="document.position" label="Position" />
        </div>
        <p class="small text-secondary mt-2">{{ __('The pages inside this document move with it.') }}</p>

        <x-slot name="actions">
            <x-rpd::button type="submit" label="Save" />
        </x-slot>
    </x-rpd::edit>
</x-rpd::card>
</div>
