<?php

use App\Modules\Wiki\Livewire\CollectionsEdit;
use App\Modules\Wiki\Livewire\CollectionsView;
use App\Modules\Wiki\Livewire\DocumentsEdit;
use App\Modules\Wiki\Livewire\DocumentsHistory;
use App\Modules\Wiki\Livewire\DocumentsView;
use App\Modules\Wiki\Livewire\Home;
use App\Modules\Wiki\Models\Collection;
use App\Modules\Wiki\Models\Document;
use Illuminate\Support\Facades\Route;

// The crumbs receive the bound model, or its id when the trail is built for another route.
$collection = fn ($c) => $c instanceof Collection ? $c : Collection::find($c);
$document = fn ($d) => $d instanceof Document ? $d : Document::find($d);

Route::get('wiki', Home::class)
    ->middleware(['web', 'auth'])
    ->name('wiki')
    ->crumbs(fn ($crumbs) => $crumbs->push(__('Wiki'), route_lang('wiki')));

Route::get('wiki/collections/view/{collection}', CollectionsView::class)
    ->middleware(['web', 'auth'])
    ->name('wiki.collections.view')
    ->crumbs(function ($crumbs, $c) use ($collection) {
        $c = $collection($c);
        $crumbs->parent('wiki')->push($c?->name ?? __('Collection'), route_lang('wiki.collections.view', $c));
    });

Route::get('wiki/collections/edit/{collection?}', CollectionsEdit::class)
    ->middleware(['web', 'auth'])
    ->name('wiki.collections.edit')
    ->crumbs(function ($crumbs, $c = null) use ($collection) {
        $c = $c ? $collection($c) : null;
        $c
            ? $crumbs->parent('wiki.collections.view', $c)->push(__('Edit collection'), route_lang('wiki.collections.edit', $c))
            : $crumbs->parent('wiki')->push(__('New collection'), route_lang('wiki.collections.edit'));
    });

Route::get('wiki/documents/view/{document}', DocumentsView::class)
    ->middleware(['web', 'auth'])
    ->name('wiki.documents.view')
    ->crumbs(function ($crumbs, $d) use ($document) {
        $d = $document($d);
        $crumbs->parent('wiki.collections.view', $d->collection);
        foreach ($d->ancestors() as $ancestor) {
            $crumbs->push($ancestor->title, route_lang('wiki.documents.view', $ancestor));
        }
        $crumbs->push($d->title, route_lang('wiki.documents.view', $d));
    });

Route::get('wiki/documents/edit/{document?}', DocumentsEdit::class)
    ->middleware(['web', 'auth'])
    ->name('wiki.documents.edit')
    ->crumbs(function ($crumbs, $d = null) use ($document) {
        $d = $d ? $document($d) : null;
        $d
            ? $crumbs->parent('wiki.documents.view', $d)->push(__('Edit'), route_lang('wiki.documents.edit', $d))
            : $crumbs->parent('wiki')->push(__('New document'), route_lang('wiki.documents.edit'));
    });

Route::get('wiki/documents/history/{document}', DocumentsHistory::class)
    ->middleware(['web', 'auth'])
    ->name('wiki.documents.history')
    ->crumbs(function ($crumbs, $d) use ($document) {
        $d = $document($d);
        $crumbs->parent('wiki.documents.view', $d)->push(__('History'), route_lang('wiki.documents.history', $d));
    });
