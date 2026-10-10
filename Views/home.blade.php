<x-rpd::card>
    <x-rpd::view title="Wiki">
        <x-slot name="buttons">
            @if ($canEdit)
                <x-rpd::button route="wiki.collections.edit" color="outline-dark" label="New collection" />
                <x-rpd::button route="wiki.documents.edit" color="outline-primary" label="New document" />
            @endif
        </x-slot>

        <div class="row mb-4">
            <x-rpd::input col="col-12" model="search" debounce="300" icon="search" placeholder="Search the wiki..." />
        </div>

        @if ($results !== null)
            <h6 class="text-secondary mb-3">{{ trans_choice('{0} Nothing found|{1} :count document|[2,*] :count documents', $results->count(), ['count' => $results->count()]) }}</h6>
            <div class="list-group mb-4">
                @foreach ($results as $doc)
                    <a href="{{ route_lang('wiki.documents.view', $doc->id) }}" class="list-group-item list-group-item-action">
                        <div class="d-flex justify-content-between gap-2">
                            <strong class="text-truncate">{{ $doc->title }}</strong>
                            <small class="text-secondary text-nowrap">{{ $doc->collection?->name }}</small>
                        </div>
                        <small class="text-secondary">{{ \App\Modules\Wiki\Support\Markdown::excerpt($doc->body) }}</small>
                    </a>
                @endforeach
            </div>
        @else
            @if ($collections->isEmpty())
                <div class="text-center text-secondary py-5">
                    <x-rpd::icon name="book" size="2x" />
                    <p class="mt-3 mb-0">{{ __('No collections yet.') }}</p>
                    @if ($canEdit)
                        <p class="small">{{ __('A collection groups the documents of one subject: start with one.') }}</p>
                    @endif
                </div>
            @else
                <div class="row row-cols-1 row-cols-md-2 row-cols-xl-3 g-3 mb-4">
                    @foreach ($collections as $collection)
                        <div class="col">
                            <a href="{{ route_lang('wiki.collections.view', $collection->id) }}" class="card h-100 text-decoration-none text-body">
                                <div class="card-body">
                                    <h5 class="card-title mb-1">
                                        <x-rpd::icon :name="$collection->icon ?: 'folder'" class="text-primary me-1" />
                                        {{ $collection->name }}
                                    </h5>
                                    <p class="card-text text-secondary small mb-2">{{ \Illuminate\Support\Str::limit($collection->description, 120) }}</p>
                                    <small class="text-secondary">{{ trans_choice('{0} no documents|{1} :count document|[2,*] :count documents', $collection->documents_count, ['count' => $collection->documents_count]) }}</small>
                                </div>
                            </a>
                        </div>
                    @endforeach
                </div>
            @endif

            @if ($recent->isNotEmpty())
                <h6 class="text-secondary mb-2">{{ __('Recently updated') }}</h6>
                <div class="list-group list-group-flush">
                    @foreach ($recent as $doc)
                        <a href="{{ route_lang('wiki.documents.view', $doc->id) }}" class="list-group-item list-group-item-action d-flex justify-content-between gap-2 px-1">
                            <span class="text-truncate">{{ $doc->title }} <small class="text-secondary">· {{ $doc->collection?->name }}</small></span>
                            <small class="text-secondary text-nowrap">{{ $doc->updated_at?->diffForHumans() }}</small>
                        </a>
                    @endforeach
                </div>
            @endif
        @endif
    </x-rpd::view>
</x-rpd::card>
