<?php

namespace App\Modules\Handbook\Livewire\Concerns;

use App\Modules\Handbook\Models\Collection;
use App\Modules\Handbook\Models\Document;

/**
 * "New document" / "New page", as in Outline: the page is created at once, untitled, and opens
 * in the editor with the cursor on the title. Collection and parent come from the browser, so
 * they are looked up again; the parent decides the collection.
 */
trait CreatesDocuments
{
    public function newDocument(?string $collectionId = null, ?string $parentId = null)
    {
        $this->authorize('admin|edit handbook');

        $parent = $parentId ? Document::find($parentId) : null;
        $collection = $parent?->collection
            ?? ($collectionId ? Collection::find($collectionId) : null)
            ?? Collection::orderBy('position')->orderBy('name')->first();

        if (! $collection) {
            return redirect()->to(route_lang('handbook.collections.edit'));
        }

        $document = Document::create([
            'collection_id' => $collection->id,
            'parent_id' => $parent?->id,
            'title' => '',
            'body' => '',
            'position' => (int) Document::where('collection_id', $collection->id)->where('parent_id', $parent?->id)->max('position') + 1,
            'created_by' => auth()->id(),
            'updated_by' => auth()->id(),
        ]);

        return redirect()->to(route_lang('handbook.documents.view', $document) . '?new=1');
    }
}
