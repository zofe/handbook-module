<x-rpd::card>
    <x-rpd::edit :title="$collection->exists ? 'Edit collection' : 'New collection'">
        <x-slot name="buttons">
            <x-rpd::button :href="$collection->exists ? route_lang('handbook.collections.view', $collection->id) : route_lang('handbook')" color="outline-dark" label="Back" />
        </x-slot>

        <div class="row">
            <x-rpd::input col="col-md-8" model="collection.name" label="Name" />
            <x-rpd::input col="col-md-2 col-6" model="collection.icon" label="Icon" placeholder="book" help="a Font Awesome icon name" />
            <x-rpd::input col="col-md-2 col-6" type="number" model="collection.position" label="Position" />
        </div>
        <div class="row">
            <x-rpd::textarea col="col-12" model="collection.description" label="Description" rows="3" />
        </div>

        <x-slot name="actions">
            <x-rpd::button type="submit" label="Save" />
            @if ($collection->exists)
                <x-rpd::button click="delete" color="outline-danger" label="Delete" confirm="Delete this collection? Only an empty collection can be deleted." />
            @endif
        </x-slot>
    </x-rpd::edit>
</x-rpd::card>
