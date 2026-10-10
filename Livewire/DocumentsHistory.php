<?php

namespace App\Modules\Handbook\Livewire;

use App\Modules\Auth\Traits\Authorize;
use App\Modules\Auth\Traits\Limit;
use App\Modules\Handbook\Models\Document;
use Livewire\Attributes\Url;
use Livewire\Component;

class DocumentsHistory extends Component
{
    use Authorize, Limit;

    public Document $document;

    #[Url(as: 'r', except: '')]
    public string $revision = '';

    public function booted(): void
    {
        $this->authorize('admin|view handbook|edit handbook', $this->document);
        $this->limit();
    }

    public function mount(Document $document): void
    {
        $this->document = $document;
    }

    public function show(string $id): void
    {
        $this->revision = $id;
    }

    /** Bring a revision back: it becomes the current text, and a new entry of the history. */
    public function restore(string $id)
    {
        $this->authorize('admin|edit handbook', $this->document);

        // looked up among the revisions of this document only
        $revision = $this->document->revisions()->whereKey($id)->firstOrFail();

        $this->document->title = $revision->title;
        $this->document->body = $revision->body;
        $this->document->updated_by = auth()->id();
        $this->document->save();
        $this->document->saveRevision(auth()->id(), 0);   // a restore is always its own entry

        session()->flash('success', __('Revision restored.'));

        return redirect()->to(route_lang('handbook.documents.view', $this->document));
    }

    public function render()
    {
        $revisions = $this->document->revisions()->with('user')->get();
        $selected = $revisions->firstWhere('id', $this->revision) ?? $revisions->first();

        return view('handbook::documents_history', [
            'revisions' => $revisions,
            'selected' => $selected,
            'current' => $revisions->first(),
            'canEdit' => (bool) auth()->user()?->hasRoleOrPermission('admin|edit handbook'),
        ])->layout('layout::admin');
    }
}
