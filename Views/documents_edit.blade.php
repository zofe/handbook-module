<div>
    @include('handbook::markdown_style')
<x-rpd::card>
    <x-rpd::edit :title="$document->exists ? 'Edit document' : 'New document'">
        <x-slot name="buttons">
            <x-rpd::button :href="$document->exists ? route_lang('handbook.documents.view', $document->id) : route_lang('handbook')" color="outline-dark" label="Back" />
        </x-slot>

        <div class="row">
            <x-rpd::input col="col-12" model="document.title" label="Title" />
        </div>
        <div class="row">
            <x-rpd::select col="col-md-6" model="document.collection_id" :options="$collections" label="Collection" />
            <x-rpd::select col="col-md-6" model="document.parent_id" :options="$parents" label="Inside" placeholder="(top level)" />
        </div>

        {{-- Write | Preview: tabs on a phone, side by side from lg up --}}
        <div class="hb-editor mt-3" x-data="{
                tab: 'write',
                el() { return document.getElementById('hb-body') },
                done(el) { el.focus(); el.dispatchEvent(new Event('input', { bubbles: true })) },
                wrap(before, after, sample) {
                    const el = this.el(), s = el.selectionStart, e = el.selectionEnd;
                    const text = el.value.substring(s, e) || sample;
                    el.setRangeText(before + text + after, s, e, 'select');
                    el.setSelectionRange(s + before.length, s + before.length + text.length);
                    this.done(el);
                },
                line(prefix) {
                    const el = this.el(), s = el.selectionStart, e = el.selectionEnd;
                    const start = el.value.lastIndexOf('\n', s - 1) + 1;
                    const block = el.value.substring(start, e).split('\n').map(l => prefix + l).join('\n');
                    el.setRangeText(block, start, e, 'end');
                    this.done(el);
                }
            }">
            <ul class="nav nav-tabs d-lg-none mb-2">
                <li class="nav-item"><a href="#" class="nav-link" :class="tab === 'write' && 'active'" @click.prevent="tab = 'write'">{{ __('Write') }}</a></li>
                <li class="nav-item"><a href="#" class="nav-link" :class="tab === 'preview' && 'active'" @click.prevent="tab = 'preview'">{{ __('Preview') }}</a></li>
            </ul>
            <div class="row g-3">
                <div class="col-lg-6" :class="tab === 'write' ? '' : 'd-none d-lg-block'">
                    <div class="btn-toolbar gap-1 mb-2" role="toolbar">
                        <div class="btn-group btn-group-sm">
                            <button type="button" class="btn btn-outline-secondary" title="{{ __('Heading') }}" @click="line('## ')"><b>H</b></button>
                            <button type="button" class="btn btn-outline-secondary" title="{{ __('Bold') }}" @click="wrap('**', '**', 'bold')"><b>B</b></button>
                            <button type="button" class="btn btn-outline-secondary" title="{{ __('Italic') }}" @click="wrap('_', '_', 'italic')"><i>I</i></button>
                            <button type="button" class="btn btn-outline-secondary" title="{{ __('Code') }}" @click="wrap('`', '`', 'code')"><x-rpd::icon name="code" /></button>
                            <button type="button" class="btn btn-outline-secondary" title="{{ __('Link') }}" @click="wrap('[', '](https://)', 'text')"><x-rpd::icon name="link" /></button>
                        </div>
                        <div class="btn-group btn-group-sm">
                            <button type="button" class="btn btn-outline-secondary" title="{{ __('List') }}" @click="line('- ')"><x-rpd::icon name="list-ul" /></button>
                            <button type="button" class="btn btn-outline-secondary" title="{{ __('Checklist') }}" @click="line('- [ ] ')"><x-rpd::icon name="list-check" /></button>
                            <button type="button" class="btn btn-outline-secondary" title="{{ __('Quote') }}" @click="line('> ')"><x-rpd::icon name="quote-left" /></button>
                        </div>
                    </div>
                    <x-rpd::textarea model="document.body" id="hb-body" rows="18" debounce="400" placeholder="Write in markdown..." />
                </div>
                <div class="col-lg-6" :class="tab === 'preview' ? '' : 'd-none d-lg-block'">
                    <div class="d-none d-lg-block small text-secondary mb-2 pt-1">{{ __('Preview') }}</div>
                    <div class="hb-preview handbook-markdown border rounded p-3">
                        {!! \App\Modules\Handbook\Support\Markdown::toHtml($document->body) !!}
                    </div>
                </div>
            </div>
        </div>

        <x-slot name="actions">
            <x-rpd::button type="submit" label="Save" />
        </x-slot>
    </x-rpd::edit>
</x-rpd::card>
</div>
