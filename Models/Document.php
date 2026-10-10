<?php

namespace App\Modules\Handbook\Models;

use App\Modules\Handbook\Support\Markdown;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Zofe\Rapyd\Traits\SSearch;
use Zofe\Rapyd\Traits\ShortId;

class Document extends Model
{
    use HasUuids, ShortId, SSearch;

    protected $table = 'handbook_documents';

    protected $fillable = ['collection_id', 'parent_id', 'title', 'body', 'position', 'created_by', 'updated_by'];

    protected $attributes = ['position' => 0];

    // Used by the search box (SSearch): title or text.
    public static function ssearchFallback($query, $limit = null)
    {
        return static::query()->where(function ($q) use ($query) {
            $q->where('title', 'like', '%' . $query . '%')
                ->orWhere('body', 'like', '%' . $query . '%');
        });
    }

    public function collection()
    {
        return $this->belongsTo(Collection::class);
    }

    public function parent()
    {
        return $this->belongsTo(Document::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Document::class, 'parent_id')->orderBy('position')->orderBy('title');
    }

    public function revisions()
    {
        return $this->hasMany(Revision::class)->orderByDesc('created_at')->orderByDesc('id');
    }

    public function author()
    {
        return $this->belongsTo(config('auth.providers.users.model'), 'created_by');
    }

    public function editor()
    {
        return $this->belongsTo(config('auth.providers.users.model'), 'updated_by');
    }

    /** The body as safe HTML (see Support\Markdown). */
    public function html(): string
    {
        return Markdown::toHtml($this->body);
    }

    /** Parent, grandparent… up to the root, root first. */
    public function ancestors(): array
    {
        $ancestors = [];
        $seen = [$this->id => true];
        $current = $this->parent;
        while ($current && ! isset($seen[$current->id])) {
            $seen[$current->id] = true;
            array_unshift($ancestors, $current);
            $current = $current->parent;
        }

        return $ancestors;
    }

    /** Ids of the documents below this one, at any depth. */
    public function descendantIds(): array
    {
        $ids = [];
        $level = [$this->id];
        while ($level) {
            $level = static::whereIn('parent_id', $level)->pluck('id')->all();
            $level = array_diff($level, $ids);
            $ids = array_merge($ids, $level);
        }

        return $ids;
    }

    /**
     * Keep the version just saved in the history, then drop the oldest beyond
     * config('handbook.revisions'). A save that changed neither title nor body adds nothing.
     */
    public function saveRevision($userId = null): ?Revision
    {
        $last = $this->revisions()->first();
        if ($last && $last->title === $this->title && $last->body === $this->body) {
            return null;
        }

        $revision = $this->revisions()->create([
            'title' => $this->title,
            'body' => $this->body,
            'user_id' => $userId,
        ]);

        $keep = (int) config('handbook.revisions', 50);
        if ($keep > 0) {
            $old = $this->revisions()->skip($keep)->take(PHP_INT_MAX)->pluck('id');
            Revision::whereIn('id', $old)->delete();
        }

        return $revision;
    }

    /**
     * Delete the document; its children move up to its parent, so nothing
     * below it disappears with it.
     */
    public function deleteKeepingChildren(): void
    {
        static::where('parent_id', $this->id)->update(['parent_id' => $this->parent_id]);
        $this->delete();
    }
}
