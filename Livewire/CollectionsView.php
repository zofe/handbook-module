<?php

namespace App\Modules\Wiki\Livewire;

use App\Modules\Auth\Traits\Authorize;
use App\Modules\Auth\Traits\Limit;
use App\Modules\Wiki\Models\Collection;
use Livewire\Component;

class CollectionsView extends Component
{
    use Authorize, Limit;

    public Collection $collection;

    public function booted(): void
    {
        $this->authorize('admin|view wiki|edit wiki', $this->collection);
        $this->limit();
    }

    public function mount(Collection $collection): void
    {
        $this->collection = $collection;
    }

    public function render()
    {
        return view('wiki::collections_view', [
            'canEdit' => (bool) auth()->user()?->hasRoleOrPermission('admin|edit wiki'),
            'tree' => $this->collection->tree(),
        ])->layout('layout::admin');
    }
}
