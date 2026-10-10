<?php

namespace App\Modules\Handbook\Livewire;

use App\Modules\Auth\Traits\Authorize;
use App\Modules\Auth\Traits\Limit;
use App\Modules\Handbook\Livewire\Concerns\CreatesDocuments;
use App\Modules\Handbook\Models\Collection;
use App\Modules\Handbook\Models\Document;
use Livewire\Attributes\Url;
use Livewire\Component;

class Home extends Component
{
    use Authorize, Limit, CreatesDocuments;

    #[Url(as: 'q', except: '')]
    public string $search = '';

    public function booted(): void
    {
        $this->authorize('admin|view handbook|edit handbook');
        $this->limit();
    }

    public function render()
    {
        $search = trim($this->search);

        return view('handbook::home', [
            'collections' => Collection::withCount('documents')->orderBy('position')->orderBy('name')->get(),
            'recent' => Document::with('collection')->latest('updated_at')->take(8)->get(),
            'canEdit' => (bool) auth()->user()?->hasRoleOrPermission('admin|edit handbook'),
            'results' => $search === '' ? null
                : Document::ssearch($search)->with('collection')->latest('updated_at')->take(30)->get(),
        ])->layout('layout::admin');
    }
}
