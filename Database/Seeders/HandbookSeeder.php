<?php

namespace App\Modules\Handbook\Database\Seeders;

use App\Modules\Handbook\Models\Collection;
use App\Modules\Handbook\Models\Document;
use Illuminate\Database\Seeder;

/**
 * A first collection to look at: how the handbook works, written in the handbook.
 *
 *     php artisan db:seed --class="App\Modules\Handbook\Database\Seeders\HandbookSeeder"
 *
 * It adds the collection only when there is none with the same name.
 */
class HandbookSeeder extends Seeder
{
    public function run(): void
    {
        if (Collection::where('name', 'Getting started')->exists()) {
            return;
        }

        $collection = Collection::create([
            'name' => 'Getting started',
            'description' => 'How this handbook works: collections, pages inside pages, markdown and history.',
            'icon' => 'rocket',
            'position' => 0,
        ]);

        $welcome = $this->page($collection, null, 0, 'Welcome to the handbook', <<<'MD'
The handbook keeps what the team knows in one place, written in **markdown**.

- A **collection** groups the documents of one subject: *Handbook*, *Projects*, *Customers*.
- A **document** can hold other documents: the tree on the side is the table of contents.
- Every save is kept in the **history**, and any version can be restored.

> Start from the collection on the side, or press **New page** to write one under this.
MD);

        $this->page($collection, $welcome->id, 0, 'Writing in markdown', <<<'MD'
## Text

Write **bold**, _italic_, ~~strikethrough~~ and `inline code`. Links are [plain markdown](https://commonmark.org/help/).

## Lists

1. numbered
2. lists
   - and nested ones

- [x] task lists
- [ ] with checkboxes

## Tables

| Shortcut | Result |
|----------|--------|
| `**b**`  | **b**  |
| `_i_`    | _i_    |

## Code

```php
Route::get('handbook', Home::class)->name('handbook');
```

HTML typed in a document is shown as text, never run: <b>this</b> stays as it is.
MD);

        $this->page($collection, $welcome->id, 1, 'Organizing documents', <<<'MD'
Open a document and press **New page** to write one inside it; in the form, **Inside** moves a document
under another one, **Collection** moves it, together with the pages below it, to another collection.

Deleting a document never deletes the pages below it: they move up one level.
MD);
    }

    protected function page(Collection $collection, ?string $parentId, int $position, string $title, string $body): Document
    {
        $doc = Document::create([
            'collection_id' => $collection->id,
            'parent_id' => $parentId,
            'position' => $position,
            'title' => $title,
            'body' => $body,
        ]);
        $doc->saveRevision();

        return $doc;
    }
}
