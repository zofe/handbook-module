@if(Auth::user()?->hasRoleOrPermission('admin|view handbook|edit handbook'))
<x-rpd::nav-dropdown icon="book" label="Handbook" active="/handbook">
    <x-rpd::nav-link label="Home" route="handbook" type="collapse-item" />
    @if(Auth::user()->hasRoleOrPermission('admin|edit handbook'))
        <x-rpd::nav-link label="New document" route="handbook.documents.edit" type="collapse-item" />
        <x-rpd::nav-link label="New collection" route="handbook.collections.edit" type="collapse-item" />
    @endif
</x-rpd::nav-dropdown>
@endif
