<?php

namespace App\Modules\Handbook\Livewire;

use App\Modules\Auth\Traits\Authorize;
use App\Modules\Auth\Traits\Limit;
use App\Modules\Handbook\Livewire\Concerns\CreatesDocuments;
use App\Modules\Handbook\Models\Document;
use Illuminate\Support\Facades\Validator;
use Livewire\Attributes\Renderless;
use Livewire\Component;

/**
 * The document page. A reader gets the rendered markdown; whoever holds "edit handbook" gets the
 * same page as an editor (resources/js/editor.js), saved in place by saveContent().
 */
class DocumentsView extends Component
{
    use Authorize, Limit, CreatesDocuments;

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

    /**
     * Called by the editor after a pause. $version is the updated_at the page started from: when
     * someone else saved in between, nothing is written and the editor says so, rather than one
     * of the two texts disappearing.
     */
    #[Renderless]
    public function saveContent($title, $body, $version): array
    {
        $this->authorize('admin|edit handbook', $this->document);

        $data = Validator::make(compact('title', 'body', 'version'), [
            'title' => 'nullable|string|max:255',
            'body' => 'nullable|string|max:1000000',
            'version' => 'nullable|string|max:40',
        ])->validate();

        $document = Document::findOrFail($this->document->id);
        $userId = auth()->id();

        if ($this->versionOf($document) !== (string) $data['version'] && (string) $document->updated_by !== (string) $userId) {
            return ['conflict' => true, 'by' => $document->editor?->name];
        }

        $document->title = trim((string) $data['title']);
        $document->body = (string) $data['body'];
        $document->updated_by = $userId;
        if ($document->isDirty()) {
            $document->save();
            $document->saveRevision($userId);
        }
        $this->document = $document;

        return ['id' => $document->id, 'version' => $this->versionOf($document), 'label' => $document->label()];
    }

    protected function versionOf(Document $document): string
    {
        return (string) $document->updated_at?->format('Y-m-d H:i:s');
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
        $canEdit = (bool) auth()->user()?->hasRoleOrPermission('admin|edit handbook');

        return view('handbook::documents_view', [
            'tree' => $this->document->collection->tree(),
            'canEdit' => $canEdit,
            'editor' => $canEdit ? [
                'title' => $this->document->title,
                'markdown' => (string) $this->document->body,
                'version' => $this->versionOf($this->document),
                'focusTitle' => request()->boolean('new'),
                'labels' => [
                    'placeholder' => __('Write something, or type / for blocks...'),
                    'linkPrompt' => __('Link address'),
                    'noResults' => __('No results'),
                    'blocks' => [
                        'h1' => __('Big heading'), 'h2' => __('Medium heading'), 'h3' => __('Small heading'),
                        'bullet' => __('Bulleted list'), 'ordered' => __('Numbered list'), 'todo' => __('Todo list'),
                        'quote' => __('Quote'), 'code' => __('Code block'), 'table' => __('Table'), 'divider' => __('Divider'),
                    ],
                    'commands' => [
                        'bold' => __('Bold'), 'italic' => __('Italic'), 'strike' => __('Strikethrough'), 'code' => __('Code'),
                        'link' => __('Link'), 'h2' => __('Heading'), 'quote' => __('Quote'),
                    ],
                ],
            ] : null,
        ])->layout('layout::admin');
    }
}
