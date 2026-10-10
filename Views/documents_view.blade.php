<div>
    @include('handbook::markdown_style')
    @if ($editor)
        @once
            <link rel="stylesheet" href="{{ \App\Modules\Handbook\Support\Assets::url('editor.css') }}">
            <script src="{{ \App\Modules\Handbook\Support\Assets::url('editor.js') }}"></script>
        @endonce
    @endif

    <div class="row g-3">
        <div class="col-lg-3 order-2 order-lg-1">
            <x-rpd::card>
                <details class="d-lg-none">
                    <summary class="fw-semibold">{{ $document->collection->name }}</summary>
                    <div class="mt-2">@include('handbook::tree', ['nodes' => $tree, 'current' => $document->id])</div>
                </details>
                <div class="d-none d-lg-block">
                    <a href="{{ route_lang('handbook.collections.view', $document->collection_id) }}" class="d-block fw-semibold text-body text-decoration-none mb-2">
                        <x-rpd::icon :name="$document->collection->icon ?: 'folder'" class="text-primary me-1" />{{ $document->collection->name }}
                    </a>
                    @include('handbook::tree', ['nodes' => $tree, 'current' => $document->id])
                </div>
            </x-rpd::card>
        </div>

        <div class="col-lg-9 order-1 order-lg-2">
            <x-rpd::card>
                <div @if ($editor) x-data="handbookEditor(@js($editor))" @endif>
                    <div class="d-flex flex-wrap align-items-center gap-1 mb-3">
                        <div class="hb-meta flex-grow-1 small text-secondary">
                            {{ __('Updated') }} {{ $document->updated_at?->diffForHumans() }}@if ($document->editor) · {{ $document->editor->name }}@endif
                        </div>
                        @if ($editor)
                            <span class="hb-status me-1" aria-live="polite">
                                <span x-show="status === 'saved'" class="text-success"><x-rpd::icon name="check" />{{ __('Saved') }}</span>
                                <span x-show="status === 'unsaved' || status === 'saving'" x-cloak class="text-secondary">{{ __('Saving...') }}</span>
                                <span x-show="status === 'error'" x-cloak class="text-danger" role="button" @click="save()">{{ __('Not saved, retry') }}</span>
                            </span>
                            <x-rpd::button click="newDocument(null, '{{ $document->id }}')" color="outline-primary" size="sm" icon="plus" title="{{ __('New page') }}">
                                <span class="d-none d-sm-inline">{{ __('New page') }}</span>
                            </x-rpd::button>
                            <x-rpd::button :route="['handbook.documents.move', $document->id]" color="outline-dark" size="sm" icon="folder-tree" title="{{ __('Move') }}">
                                <span class="d-none d-sm-inline">{{ __('Move') }}</span>
                            </x-rpd::button>
                        @endif
                        <x-rpd::button :route="['handbook.documents.history', $document->id]" color="outline-dark" size="sm" icon="clock-rotate-left" title="{{ __('History') }}">
                            <span class="d-none d-sm-inline">{{ __('History') }}</span>
                        </x-rpd::button>
                        @if ($canEdit)
                            <x-rpd::button click="delete" color="outline-danger" size="sm" icon="trash" title="{{ __('Delete') }}"
                                           confirm="Delete this document? The pages below it move up one level." />
                        @endif
                    </div>

                    @if ($editor)
                        <div x-show="status === 'conflict'" x-cloak>
                            <div class="alert alert-warning d-flex align-items-center gap-2">
                                <span class="flex-grow-1">{{ __('Someone else saved this document while you were writing: your last changes are not saved.') }}</span>
                                <button type="button" class="btn btn-sm btn-warning" @click="window.location.reload()">{{ __('Reload') }}</button>
                            </div>
                        </div>

                        <textarea class="hb-title mb-3" rows="1" x-ref="title" x-model="title" maxlength="255"
                                  placeholder="{{ __('Untitled') }}" aria-label="{{ __('Title') }}"
                                  x-init="fit($el)" @input="fit($el); changed()" @keydown.enter.prevent="toText()"></textarea>
                        <div wire:ignore x-ref="editor"></div>

                        <div class="hb-mobile-toolbar d-lg-none" role="toolbar" aria-label="{{ __('Formatting') }}">
                            @foreach ([['h2', 'heading', 'Heading'], ['bold', 'bold', 'Bold'], ['italic', 'italic', 'Italic'], ['bullet', 'list-ul', 'List'], ['todo', 'list-check', 'Checklist'], ['quote', 'quote-left', 'Quote'], ['codeblock', 'code', 'Code'], ['link', 'link', 'Link'], ['undo', 'rotate-left', 'Undo']] as [$cmd, $icon, $label])
                                <button type="button" class="btn btn-sm btn-outline-secondary" title="{{ __($label) }}" @mousedown.prevent @click="run('{{ $cmd }}')"><x-rpd::icon :name="$icon" /></button>
                            @endforeach
                        </div>
                    @else
                        <h1 class="h2 mb-4">{{ $document->label() }}</h1>
                        @if (trim((string) $document->body) === '')
                            <p class="text-secondary fst-italic">{{ __('This document is empty.') }}</p>
                        @else
                            <div class="hb-markdown">{!! $document->html() !!}</div>
                        @endif
                    @endif

                    @if ($document->children->isNotEmpty())
                        <hr>
                        <h6 class="text-secondary">{{ __('Pages in this document') }}</h6>
                        <div class="list-group list-group-flush">
                            @foreach ($document->children as $child)
                                <a href="{{ route_lang('handbook.documents.view', $child->id) }}" class="list-group-item list-group-item-action px-1">
                                    <x-rpd::icon name="file-lines" class="text-secondary me-1" /><span data-hb-title="{{ $child->id }}">{{ $child->label() }}</span>
                                </a>
                            @endforeach
                        </div>
                    @endif
                </div>
            </x-rpd::card>
        </div>
    </div>
</div>
