<?php

use App\Modules\Handbook\Livewire\CollectionsEdit;
use App\Modules\Handbook\Livewire\CollectionsView;
use App\Modules\Handbook\Livewire\DocumentsEdit;
use App\Modules\Handbook\Livewire\DocumentsHistory;
use App\Modules\Handbook\Livewire\DocumentsView;
use App\Modules\Handbook\Livewire\Home;
use App\Modules\Handbook\Models\Collection;
use App\Modules\Handbook\Models\Document;
use Illuminate\Support\Facades\Route;

// The crumbs receive the bound model, or its id when the trail is built for another route.
$collection = fn ($c) => $c instanceof Collection ? $c : Collection::find($c);
$document = fn ($d) => $d instanceof Document ? $d : Document::find($d);

Route::get('handbook', Home::class)
    ->middleware(['web', 'auth'])
    ->name('handbook')
    ->crumbs(fn ($crumbs) => $crumbs->push(__('Handbook'), route_lang('handbook')));

Route::get('handbook/collections/view/{collection}', CollectionsView::class)
    ->middleware(['web', 'auth'])
    ->name('handbook.collections.view')
    ->crumbs(function ($crumbs, $c) use ($collection) {
        $c = $collection($c);
        $crumbs->parent('handbook')->push($c?->name ?? __('Collection'), route_lang('handbook.collections.view', $c));
    });

Route::get('handbook/collections/edit/{collection?}', CollectionsEdit::class)
    ->middleware(['web', 'auth'])
    ->name('handbook.collections.edit')
    ->crumbs(function ($crumbs, $c = null) use ($collection) {
        $c = $c ? $collection($c) : null;
        $c
            ? $crumbs->parent('handbook.collections.view', $c)->push(__('Edit collection'), route_lang('handbook.collections.edit', $c))
            : $crumbs->parent('handbook')->push(__('New collection'), route_lang('handbook.collections.edit'));
    });

Route::get('handbook/documents/view/{document}', DocumentsView::class)
    ->middleware(['web', 'auth'])
    ->name('handbook.documents.view')
    ->crumbs(function ($crumbs, $d) use ($document) {
        $d = $document($d);
        $crumbs->parent('handbook.collections.view', $d->collection);
        foreach ($d->ancestors() as $ancestor) {
            $crumbs->push($ancestor->title, route_lang('handbook.documents.view', $ancestor));
        }
        $crumbs->push($d->title, route_lang('handbook.documents.view', $d));
    });

Route::get('handbook/documents/edit/{document?}', DocumentsEdit::class)
    ->middleware(['web', 'auth'])
    ->name('handbook.documents.edit')
    ->crumbs(function ($crumbs, $d = null) use ($document) {
        $d = $d ? $document($d) : null;
        $d
            ? $crumbs->parent('handbook.documents.view', $d)->push(__('Edit'), route_lang('handbook.documents.edit', $d))
            : $crumbs->parent('handbook')->push(__('New document'), route_lang('handbook.documents.edit'));
    });

Route::get('handbook/documents/history/{document}', DocumentsHistory::class)
    ->middleware(['web', 'auth'])
    ->name('handbook.documents.history')
    ->crumbs(function ($crumbs, $d) use ($document) {
        $d = $document($d);
        $crumbs->parent('handbook.documents.view', $d)->push(__('History'), route_lang('handbook.documents.history', $d));
    });
