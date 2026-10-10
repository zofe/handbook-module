# Rapyd Admin — Handbook module

<a href="https://github.com/zofe/handbook-module/actions/workflows/run-tests.yml"><img src="https://github.com/zofe/handbook-module/actions/workflows/run-tests.yml/badge.svg" alt="Tests"></a>

A knowledge base for [Rapyd Admin](https://github.com/zofe/rapyd-admin) in the spirit of [Outline](https://www.getoutline.com):
**collections** of **markdown documents** nested in a tree, a writing view with live preview, search, and the
**history** of every document with one-click restore. Plain Laravel + Livewire, built from `x-rpd::` components.

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
| Document | `/handbook/documents/view/{id}` | rendered markdown, the tree on the side, the pages inside it |
| Editor | `/handbook/documents/edit/{id?}` | title, collection, parent, markdown with a toolbar; *Write / Preview* tabs on a phone, side by side on a desktop |
| History | `/handbook/documents/history/{id}` | every saved version, restore one |

- **Markdown** is GitHub flavoured (tables, task lists, strikethrough). HTML typed in a document is escaped and
  `javascript:` links are dropped: a document is text, never markup.
- **Permissions**: `view handbook` reads, `edit handbook` writes (collections, documents, restoring a revision). Every id
  that comes from the browser (parent, collection, revision) is looked up again on the server.
- **Moving**: *Inside* puts a document under another one of the same collection (never under itself or its own
  pages); changing *Collection* moves the document with all the pages below it.
- **Deleting** a document moves its pages up one level; a collection can be deleted only when empty.
- **History**: each save that changes title or text adds a revision; `HANDBOOK_REVISIONS` (default 50) caps how many
  a document keeps.

## The handbook as knowledge of the AI assistant

With [zofe/ai-module](https://github.com/zofe/ai-module) the assistant can read the handbook:

```dotenv
AI_KNOWLEDGE="App\Modules\Handbook\HandbookKnowledge"
```

## Structure

```
handbook-module/
├─ Livewire/       Home, CollectionsView, CollectionsEdit, DocumentsView, DocumentsEdit, DocumentsHistory
├─ Views/          the pages, menu, tree (recursive), markdown_style
├─ Models/         Collection (tree()), Document (SSearch, ancestors, revisions), Revision
├─ Support/        Markdown: safe markdown → HTML
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
