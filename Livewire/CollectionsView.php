<?php

namespace App\Modules\Handbook\Livewire;

use App\Modules\Auth\Traits\Authorize;
use App\Modules\Auth\Traits\Limit;
use App\Modules\Handbook\Models\Collection;
use Livewire\Component;

class CollectionsView extends Component
{
    use Authorize, Limit;

    public Collection $collection;

    public function booted(): void
    {
        $this->authorize('admin|view handbook|edit handbook', $this->collection);
        $this->limit();
    }

    public function mount(Collection $collection): void
    {
        $this->collection = $collection;
    }

    public function render()
    {
        return view('handbook::collections_view', [
            'canEdit' => (bool) auth()->user()?->hasRoleOrPermission('admin|edit handbook'),
            'tree' => $this->collection->tree(),
        ])->layout('layout::admin');
    }
}
