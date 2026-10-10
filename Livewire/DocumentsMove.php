<?php

namespace App\Modules\Handbook\Livewire;

use App\Modules\Auth\Traits\Authorize;
use App\Modules\Auth\Traits\Limit;
use App\Modules\Handbook\Models\Collection;
use App\Modules\Handbook\Models\Document;
use Livewire\Component;

/**
 * Where a document lives: its collection, the document it sits inside, its position among
 * its siblings. Title and text are edited in place on the document page.
 */
class DocumentsMove extends Component
{
    use Authorize, Limit;

    public Document $document;

    // Fields bound with x-rpd:: components; collection and parent are checked again in save().
    protected $rules = [
        'document.collection_id' => 'required|exists:handbook_collections,id',
        'document.parent_id'     => 'nullable|exists:handbook_documents,id',
        'document.position'      => 'nullable|integer|min:0',
    ];

    public function booted(): void
    {
        $this->authorize('admin|edit handbook', $this->document);
        $this->limit();
    }

    public function mount(Document $document): void
    {
        $this->document = $document;
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

        $movedCollection = Document::whereKey($this->document->id)->value('collection_id') !== $this->document->collection_id;

        $this->document->parent_id = $this->document->parent_id ?: null;
        $this->document->position = (int) $this->document->position;
        $this->document->updated_by = auth()->id();
        $this->document->save();

        if ($movedCollection) {
            // the pages below follow it
            Document::whereIn('id', $this->document->descendantIds())->update(['collection_id' => $this->document->collection_id]);
        }

        session()->flash('success', __('Document moved.'));

        return redirect()->to(route_lang('handbook.documents.view', $this->document));
    }

    /** The documents this one may be placed under: same collection, not itself, not below itself. */
    protected function parentOptions()
    {
        if (! $this->document->collection_id) {
            return collect();
        }

        $exclude = [$this->document->id, ...$this->document->descendantIds()];

        return Document::where('collection_id', $this->document->collection_id)
            ->whereNotIn('id', $exclude)
            ->orderBy('title')
            ->get(['id', 'title'])
            ->mapWithKeys(fn (Document $doc) => [$doc->id => $doc->label()]);
    }

    public function render()
    {
        return view('handbook::documents_move', [
            'collections' => Collection::orderBy('position')->orderBy('name')->pluck('name', 'id')->toArray(),
            'parents' => $this->parentOptions()->toArray(),
        ])->layout('layout::admin');
    }
}
