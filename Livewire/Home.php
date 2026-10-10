<?php

namespace App\Modules\Wiki\Livewire;

use App\Modules\Auth\Traits\Authorize;
use App\Modules\Auth\Traits\Limit;
use App\Modules\Wiki\Models\Collection;
use App\Modules\Wiki\Models\Document;
use Livewire\Attributes\Url;
use Livewire\Component;

class Home extends Component
{
    use Authorize, Limit;

    #[Url(as: 'q', except: '')]
    public string $search = '';

    public function booted(): void
    {
        $this->authorize('admin|view wiki|edit wiki');
        $this->limit();
    }

    public function render()
    {
        $search = trim($this->search);

        return view('wiki::home', [
            'collections' => Collection::withCount('documents')->orderBy('position')->orderBy('name')->get(),
            'recent' => Document::with('collection')->latest('updated_at')->take(8)->get(),
            'canEdit' => (bool) auth()->user()?->hasRoleOrPermission('admin|edit wiki'),
            'results' => $search === '' ? null
                : Document::ssearch($search)->with('collection')->latest('updated_at')->take(30)->get(),
        ])->layout('layout::admin');
    }
}
