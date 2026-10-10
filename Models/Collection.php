<?php

namespace App\Modules\Handbook\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Zofe\Rapyd\Traits\ShortId;

class Collection extends Model
{
    use HasUuids, ShortId;

    protected $table = 'handbook_collections';

    protected $fillable = ['name', 'description', 'icon', 'position'];

    protected $attributes = ['position' => 0];

    public function documents()
    {
        return $this->hasMany(Document::class);
    }

    /**
     * Every document of the collection as a tree: a list of
     * ['document' => Document, 'children' => [...]], siblings by position then title.
     */
    public function tree(): array
    {
        $byParent = $this->documents()
            ->select(['id', 'collection_id', 'parent_id', 'title', 'position'])
            ->orderBy('position')->orderBy('title')
            ->get()
            ->groupBy(fn ($doc) => (string) $doc->parent_id);

        $build = function (string $parentId) use (&$build, $byParent): array {
            return ($byParent[$parentId] ?? collect())
                ->map(fn ($doc) => ['document' => $doc, 'children' => $build($doc->id)])
                ->all();
        };

        return $build('');
    }
}
