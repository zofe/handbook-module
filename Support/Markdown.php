<?php

namespace App\Modules\Wiki\Support;

use Illuminate\Support\Str;

/**
 * Markdown to HTML for the wiki: GitHub flavoured (tables, task lists,
 * strikethrough, autolinks). Raw HTML in the text is escaped and javascript:
 * or data: links are dropped, so the output is printed with {!! !!} safely:
 * a document is text written by users, never markup.
 */
class Markdown
{
    public static function toHtml(?string $markdown): string
    {
        if ($markdown === null || trim($markdown) === '') {
            return '';
        }

        return (string) Str::markdown($markdown, [
            'html_input' => 'escape',
            'allow_unsafe_links' => false,
            'max_nesting_level' => 50,
        ]);
    }

    /** The text without markdown, for excerpts in lists. */
    public static function excerpt(?string $markdown, int $length = 160): string
    {
        $text = strip_tags(static::toHtml($markdown));

        return Str::limit(trim(preg_replace('/\s+/', ' ', html_entity_decode($text))), $length);
    }
}
