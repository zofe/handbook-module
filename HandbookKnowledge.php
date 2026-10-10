<?php

namespace App\Modules\Handbook;

use App\Modules\Handbook\Models\Document;

/**
 * The handbook as the knowledge of the AI assistant (zofe/ai-module): set
 *
 *     AI_KNOWLEDGE="App\Modules\Handbook\HandbookKnowledge"
 *
 * and the assistant reads every document, most recently updated first.
 */
class HandbookKnowledge
{
    public function __invoke(): array
    {
        return Document::query()
            ->with('collection')
            ->latest('updated_at')
            ->get()
            ->map(fn (Document $doc) => [
                'title' => $doc->label(),
                'file' => $doc->collection?->name . ' / ' . $doc->label(),
                'text' => '# ' . $doc->label() . "\n\n" . trim((string) $doc->body),
                'updated_at' => $doc->updated_at?->toDateTimeString(),
                'url' => route('handbook.documents.view', $doc),
            ])
            ->all();
    }
}
