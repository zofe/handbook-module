<?php

namespace App\Modules\Wiki;

use App\Modules\Wiki\Models\Document;

/**
 * The wiki as the knowledge of the AI assistant (zofe/ai-module): set
 *
 *     AI_KNOWLEDGE="App\Modules\Wiki\WikiKnowledge"
 *
 * and the assistant reads every document, most recently updated first.
 */
class WikiKnowledge
{
    public function __invoke(): array
    {
        return Document::query()
            ->with('collection')
            ->latest('updated_at')
            ->get()
            ->map(fn (Document $doc) => [
                'title' => $doc->title,
                'file' => $doc->collection?->name . ' / ' . $doc->title,
                'text' => '# ' . $doc->title . "\n\n" . trim((string) $doc->body),
                'updated_at' => $doc->updated_at?->toDateTimeString(),
                'url' => route('wiki.documents.view', $doc),
            ])
            ->all();
    }
}
