<div>
    @include('handbook::markdown_style')
<div class="row g-3">
    <div class="col-lg-4">
        <x-rpd::card>
            <div class="d-flex align-items-center mb-3">
                <h2 class="h5 flex-grow-1 mb-0">{{ __('History') }}</h2>
                <x-rpd::button :route="['handbook.documents.view', $document->id]" color="outline-dark" size="sm" label="Back" />
            </div>
            @if ($revisions->isEmpty())
                <p class="text-secondary mb-0">{{ __('No revisions yet: the history starts with the next save.') }}</p>
            @else
                <div class="list-group">
                    @foreach ($revisions as $rev)
                        <button type="button" wire:click="show('{{ $rev->id }}')"
                                class="list-group-item list-group-item-action {{ $selected?->id === $rev->id ? 'active' : '' }}">
                            <div class="d-flex justify-content-between gap-2">
                                <span>{{ $rev->created_at?->isoFormat('LLL') }}</span>
                                @if ($loop->first)<span class="badge text-bg-success">{{ __('current') }}</span>@endif
                            </div>
                            <small class="{{ $selected?->id === $rev->id ? '' : 'text-secondary' }}">{{ $rev->user?->name ?? __('unknown') }}</small>
                        </button>
                    @endforeach
                </div>
            @endif
        </x-rpd::card>
    </div>
    <div class="col-lg-8">
        <x-rpd::card>
            @if ($selected)
                <div class="d-flex flex-wrap align-items-start gap-2 mb-3">
                    <h1 class="h3 flex-grow-1 mb-0">{{ $selected->title !== '' ? $selected->title : __('Untitled') }}</h1>
                    @if ($canEdit && $selected->id !== $current?->id)
                        <x-rpd::button click="restore('{{ $selected->id }}')" color="primary" size="sm" icon="rotate-left" label="Restore this version"
                                       confirm="The document goes back to this version; the current one stays in the history." />
                    @endif
                </div>
                <div class="hb-markdown">{!! $selected->html() !!}</div>
            @else
                <h1 class="h3">{{ $document->label() }}</h1>
                <div class="hb-markdown">{!! $document->html() !!}</div>
            @endif
        </x-rpd::card>
    </div>
</div>
</div>
