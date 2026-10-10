@if(Auth::user()?->hasRoleOrPermission('admin|view wiki|edit wiki'))
<x-rpd::nav-dropdown icon="book" label="Wiki" active="/wiki">
    <x-rpd::nav-link label="Home" route="wiki" type="collapse-item" />
    @if(Auth::user()->hasRoleOrPermission('admin|edit wiki'))
        <x-rpd::nav-link label="New document" route="wiki.documents.edit" type="collapse-item" />
        <x-rpd::nav-link label="New collection" route="wiki.collections.edit" type="collapse-item" />
    @endif
</x-rpd::nav-dropdown>
@endif
