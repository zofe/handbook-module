<div>
    @include('wiki::markdown_style')
<div class="row g-3">
    <div class="col-lg-3 order-2 order-lg-1">
        <x-rpd::card>
            <details class="d-lg-none">
                <summary class="fw-semibold">{{ $document->collection->name }}</summary>
                <div class="mt-2">@include('wiki::tree', ['nodes' => $tree, 'current' => $document->id])</div>
            </details>
            <div class="d-none d-lg-block">
                <a href="{{ route_lang('wiki.collections.view', $document->collection_id) }}" class="d-block fw-semibold text-body text-decoration-none mb-2">
                    <x-rpd::icon :name="$document->collection->icon ?: 'folder'" class="text-primary me-1" />{{ $document->collection->name }}
                </a>
                @include('wiki::tree', ['nodes' => $tree, 'current' => $document->id])
            </div>
        </x-rpd::card>
    </div>

    <div class="col-lg-9 order-1 order-lg-2">
        <x-rpd::card>
            <div class="d-flex flex-wrap align-items-start gap-2 mb-1">
                <h1 class="h3 flex-grow-1 mb-0">{{ $document->title }}</h1>
                <div class="d-flex flex-wrap gap-1">
                    @if ($canEdit)
                        <x-rpd::button :route="['wiki.documents.edit', $document->id]" color="primary" size="sm" icon="pen" label="Edit" />
                        <x-rpd::button :href="route_lang('wiki.documents.edit', ['parent' => $document->id])" color="outline-primary" size="sm" icon="plus" label="New page" />
                    @endif
                    <x-rpd::button :route="['wiki.documents.history', $document->id]" color="outline-dark" size="sm" icon="clock-rotate-left" label="History" />
                    @if ($canEdit)
                        <x-rpd::button click="delete" color="outline-danger" size="sm" icon="trash" label="Delete"
                                       confirm="Delete this document? The pages below it move up one level." />
                    @endif
                </div>
            </div>
            <p class="small text-secondary mb-4">
                {{ __('Updated') }} {{ $document->updated_at?->diffForHumans() }}@if ($document->editor) · {{ $document->editor->name }}@endif
            </p>

            @if (trim((string) $document->body) === '')
                <p class="text-secondary fst-italic">{{ __('This document is empty.') }}</p>
            @else
                <div class="wiki-markdown">{!! $document->html() !!}</div>
            @endif

            @if ($document->children->isNotEmpty())
                <hr>
                <h6 class="text-secondary">{{ __('Pages in this document') }}</h6>
                <div class="list-group list-group-flush">
                    @foreach ($document->children as $child)
                        <a href="{{ route_lang('wiki.documents.view', $child->id) }}" class="list-group-item list-group-item-action px-1">
                            <x-rpd::icon name="file-lines" class="text-secondary me-1" />{{ $child->title }}
                        </a>
                    @endforeach
                </div>
            @endif
        </x-rpd::card>
    </div>
</div>
</div>
