<?php

namespace App\Modules\Wiki\Livewire;

use App\Modules\Auth\Traits\Authorize;
use App\Modules\Auth\Traits\Limit;
use App\Modules\Wiki\Models\Collection;
use Livewire\Component;

class CollectionsEdit extends Component
{
    use Authorize, Limit;

    public Collection $collection;

    // Fields bound with x-rpd:: components: also what survives between requests on a new model.
    protected $rules = [
        'collection.name'        => 'required|string|max:255',
        'collection.description' => 'nullable|string|max:2000',
        'collection.icon'        => ['nullable', 'string', 'max:50', 'regex:/^[a-z0-9-]+$/'],
        'collection.position'    => 'nullable|integer|min:0',
    ];

    public function booted(): void
    {
        $this->authorize('admin|edit wiki', $this->collection);
        $this->limit();
    }

    public function mount(?Collection $collection = null): void
    {
        $this->collection = $collection ?? new Collection();
    }

    public function save()
    {
        $this->validate();
        $this->collection->position = (int) $this->collection->position;
        $this->collection->save();

        session()->flash('success', __('Collection saved.'));

        return redirect()->to(route_lang('wiki.collections.view', $this->collection));
    }

    /** Only an empty collection goes: documents are never deleted in bulk. */
    public function delete()
    {
        abort_unless($this->collection->exists, 404);
        if ($this->collection->documents()->exists()) {
            $this->addError('collection.name', __('Move or delete its documents first.'));

            return null;
        }

        $this->collection->delete();
        session()->flash('success', __('Collection deleted.'));

        return redirect()->to(route_lang('wiki'));
    }

    public function render()
    {
        return view('wiki::collections_edit')->layout('layout::admin');
    }
}
