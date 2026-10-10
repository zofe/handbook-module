<?php

namespace App\Modules\Handbook\Models;

use App\Modules\Handbook\Support\Markdown;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Zofe\Rapyd\Traits\ShortId;

class Revision extends Model
{
    use HasUuids, ShortId;

    protected $table = 'handbook_revisions';

    const UPDATED_AT = null;

    protected $fillable = ['document_id', 'title', 'body', 'user_id'];

    public function document()
    {
        return $this->belongsTo(Document::class);
    }

    public function user()
    {
        return $this->belongsTo(config('auth.providers.users.model'), 'user_id');
    }

    public function html(): string
    {
        return Markdown::toHtml($this->body);
    }
}
