{{-- The document tree of a collection: $nodes from Collection::tree(), $current the open document id --}}
<ul class="hb-tree list-unstyled mb-0 {{ ($level ?? 0) ? 'ms-3 border-start' : '' }}">
    @foreach ($nodes as $node)
        <li>
            <a href="{{ route_lang('handbook.documents.view', $node['document']->id) }}"
               class="d-block text-truncate text-body rounded px-2 py-1 {{ ($current ?? null) === $node['document']->id ? 'active' : '' }}">
                <span data-hb-title="{{ $node['document']->id }}">{{ $node['document']->label() }}</span>
            </a>
            @if ($node['children'])
                @include('handbook::tree', ['nodes' => $node['children'], 'level' => ($level ?? 0) + 1, 'current' => $current ?? null])
            @endif
        </li>
    @endforeach
</ul>
