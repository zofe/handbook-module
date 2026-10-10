<?php

namespace App\Modules\Handbook\Livewire;

use App\Modules\Auth\Traits\Authorize;
use App\Modules\Auth\Traits\Limit;
use App\Modules\Handbook\Models\Collection;
use App\Modules\Handbook\Models\Document;
use Livewire\Component;

class DocumentsEdit extends Component
{
    use Authorize, Limit;

    public Document $document;

    // Fields bound with x-rpd:: components: also what survives between requests on a new model.
    protected $rules = [
        'document.title'         => 'required|string|max:255',
        'document.collection_id' => 'required|exists:handbook_collections,id',
        'document.parent_id'     => 'nullable|exists:handbook_documents,id',
        'document.body'          => 'nullable|string|max:1000000',
        'document.position'      => 'nullable|integer|min:0',
    ];

    public function booted(): void
    {
        $this->authorize('admin|edit handbook', $this->document);
        $this->limit();
    }

    /**
     * A new document starts where the link was: ?parent=<id> (a child page, same
     * collection) or ?collection=<id>; both are looked up, never trusted as they are.
     */
    public function mount(?Document $document = null): void
    {
        if ($document?->exists) {
            $this->document = $document;

            return;
        }

        $this->document = new Document();
        $parentId = request()->query('parent');
        $collectionId = request()->query('collection');
        if (is_string($parentId) && $parent = Document::find($parentId)) {
            $this->document->parent_id = $parent->id;
            $this->document->collection_id = $parent->collection_id;
        } elseif (is_string($collectionId) && $collection = Collection::find($collectionId)) {
            $this->document->collection_id = $collection->id;
        } elseif ($collection = Collection::orderBy('position')->orderBy('name')->first()) {
            $this->document->collection_id = $collection->id;
        }
    }

    public function updatedDocumentCollectionId(): void
    {
        $this->document->parent_id = null;
    }

    public function save()
    {
        $this->validate();

        if ($this->document->parent_id && ! $this->parentOptions()->has($this->document->parent_id)) {
            $this->addError('document.parent_id', __('Pick a document of the same collection, not this one or one below it.'));

            return null;
        }

        $movedCollection = $this->document->exists
            && Document::whereKey($this->document->id)->value('collection_id') !== $this->document->collection_id;
        $userId = auth()->id();

        $this->document->parent_id = $this->document->parent_id ?: null;
        $this->document->position = (int) $this->document->position;
        $this->document->created_by ??= $userId;
        $this->document->updated_by = $userId;
        $this->document->save();

        if ($movedCollection) {
            // the pages below follow it
            Document::whereIn('id', $this->document->descendantIds())->update(['collection_id' => $this->document->collection_id]);
        }

        $this->document->saveRevision($userId);
        session()->flash('success', __('Document saved.'));

        return redirect()->to(route_lang('handbook.documents.view', $this->document));
    }

    /** The documents this one may be placed under: same collection, not itself, not below itself. */
    protected function parentOptions()
    {
        if (! $this->document->collection_id) {
            return collect();
        }

        $exclude = $this->document->exists ? [$this->document->id, ...$this->document->descendantIds()] : [];

        return Document::where('collection_id', $this->document->collection_id)
            ->whereNotIn('id', $exclude)
            ->orderBy('title')
            ->pluck('title', 'id');
    }

    public function render()
    {
        return view('handbook::documents_edit', [
            'collections' => Collection::orderBy('position')->orderBy('name')->pluck('name', 'id')->toArray(),
            'parents' => $this->parentOptions()->toArray(),
        ])->layout('layout::admin');
    }
}
