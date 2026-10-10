# Rapyd Admin — Handbook module

<a href="https://github.com/zofe/handbook-module/actions/workflows/run-tests.yml"><img src="https://github.com/zofe/handbook-module/actions/workflows/run-tests.yml/badge.svg" alt="Tests"></a>

The team handbook for [Rapyd Admin](https://github.com/zofe/rapyd-admin), in the spirit of [Outline](https://www.getoutline.com):
**collections** of documents nested in a tree, **edited in place** (the page is the editor, saved as you type),
stored as **markdown**, with search and the **history** of every document. Plain Laravel + Livewire.

## Install

```bash
composer require zofe/handbook-module
php artisan migrate
php artisan db:seed --class="Zofe\Rapyd\Modules\Auth\Database\Seeders\AuthSeeder"   # creates "view handbook" / "edit handbook"
php artisan db:seed --class="App\Modules\Handbook\Database\Seeders\HandbookSeeder"          # optional: a "Getting started" collection
```

A **Handbook** entry appears in the admin sidebar for `admin` and for whoever holds `view handbook`; `operator` gets both
permissions.

## What it does

| Page | Route | |
|------|-------|-|
| Home | `/handbook` | collections, recently updated documents, search on title and text |
| Collection | `/handbook/collections/view/{id}` | the document tree |
| Document | `/handbook/documents/view/{id}` | the document, editable in place; the tree on the side |
| Move | `/handbook/documents/move/{id}` | collection, parent and position of a document |
| History | `/handbook/documents/history/{id}` | the versions of a document, restore one |

**Editing in place.** Whoever holds `edit handbook` writes directly on the document page:

- markdown shortcuts while typing: `## ` a heading, `- ` a list, `1. ` a numbered list, `[ ] ` a checklist,
  `> ` a quote, ```` ``` ```` a code block, `**bold**`, `_italic_`;
- `/` opens the block menu (headings, lists, checklist, quote, code, table, divider);
- selecting text shows the formatting bubble; on a phone a toolbar stays above the keyboard;
- **New page** creates an untitled page inside the current one and puts the cursor on the title;
  Enter in the title moves to the text;
- the page saves itself after a pause ("Saved" next to the buttons). It sends the version it was loaded at:
  when someone else saved in between, nothing is overwritten and the page asks to reload.

The text is kept as **markdown** (GitHub flavoured: tables, task lists, strikethrough): readable in the database,
in a diff, by the AI module. On display HTML typed in the text is escaped and `javascript:` links are dropped.

- **Permissions**: `view handbook` reads, `edit handbook` writes. Every id coming from the browser (parent,
  collection, revision) is looked up again on the server, and `saveContent` checks `edit handbook` itself.
- **History**: the saves of one person within `HANDBOOK_REVISION_WINDOW` minutes (default 10) are one revision,
  as in Outline; a document keeps `HANDBOOK_REVISIONS` of them (default 50). Restoring adds a revision.
- **Moving**: changing the collection moves the document with all the pages below it; a document never goes
  under itself or one of its own pages. **Deleting** one moves its pages up a level; a collection can be deleted
  only when empty.

### The editor

[TipTap](https://tiptap.dev) (ProseMirror) with [tiptap-markdown](https://github.com/aguingand/tiptap-markdown),
in `resources/js/editor.js`, built into `dist/editor.js` and served by the module at `/handbook/assets/editor.js`:
the application needs no build step and nothing to publish. After changing it:

```bash
npm install
npm run build
```

## The handbook as knowledge of the AI assistant

With [zofe/ai-module](https://github.com/zofe/ai-module) the assistant can read the handbook:

```dotenv
AI_KNOWLEDGE="App\Modules\Handbook\HandbookKnowledge"
```

## Structure

```
handbook-module/
├─ Livewire/       Home, CollectionsView, CollectionsEdit, DocumentsView (editor + saveContent), DocumentsMove,
│                  DocumentsHistory; Concerns/CreatesDocuments (New document / New page)
├─ Views/          the pages, menu, tree (recursive), markdown_style
├─ resources/      js/editor.js, css/editor.css: the editor sources
├─ dist/           the built editor, shipped
├─ Models/         Collection (tree()), Document (SSearch, ancestors, revisions), Revision
├─ Support/        Markdown (safe markdown → HTML), Assets (editor URLs)
├─ Database/       migration (handbook_collections, handbook_documents, handbook_revisions) and the seeder
├─ Lang/           it.json
├─ config.php      layout, menu, permissions, revisions kept
├─ routes.php
├─ HandbookKnowledge.php
└─ HandbookServiceProvider.php   extends RapydModuleServiceProvider
```

Copy the folder to `app/Modules/Handbook` and it keeps working as an app module: the provider steps aside.

## Tests

```bash
composer install
composer test
```
