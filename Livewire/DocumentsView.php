<?php

namespace App\Modules\Handbook\Livewire;

use App\Modules\Auth\Traits\Authorize;
use App\Modules\Auth\Traits\Limit;
use App\Modules\Handbook\Models\Document;
use Livewire\Component;

class DocumentsView extends Component
{
    use Authorize, Limit;

    public Document $document;

    public function booted(): void
    {
        $this->authorize('admin|view handbook|edit handbook', $this->document);
        $this->limit();
    }

    public function mount(Document $document): void
    {
        $this->document = $document;
    }

    public function delete()
    {
        $this->authorize('admin|edit handbook', $this->document);

        $collection = $this->document->collection;
        $this->document->deleteKeepingChildren();
        session()->flash('success', __('Document deleted.'));

        return redirect()->to(route_lang('handbook.collections.view', $collection));
    }

    public function render()
    {
        $this->document->load(['collection', 'editor', 'children']);

        return view('handbook::documents_view', [
            'tree' => $this->document->collection->tree(),
            'canEdit' => (bool) auth()->user()?->hasRoleOrPermission('admin|edit handbook'),
        ])->layout('layout::admin');
    }
}
