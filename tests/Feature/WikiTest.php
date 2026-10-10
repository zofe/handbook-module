<?php

namespace App\Modules\Wiki\Tests\Feature;

use App\Modules\Wiki\Database\Seeders\WikiSeeder;
use App\Modules\Wiki\Models\Collection;
use App\Modules\Wiki\Models\Document;
use App\Modules\Wiki\Support\Markdown;
use App\Modules\Wiki\Tests\Models\User;
use App\Modules\Wiki\Tests\TestCase;
use App\Modules\Wiki\WikiKnowledge;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

class WikiTest extends TestCase
{
    use DatabaseMigrations;

    protected Collection $collection;

    protected Document $welcome;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(WikiSeeder::class);
        $this->collection = Collection::firstOrFail();
        $this->welcome = Document::where('title', 'Welcome to the wiki')->firstOrFail();
    }

    protected function userWith(string ...$permissions): User
    {
        $user = User::create(['name' => 'Ada', 'email' => uniqid() . '@example.com', 'password' => 'x']);
        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission);
            $user->givePermissionTo($permission);
        }

        return $user;
    }

    public function test_the_seeder_builds_a_tree_and_runs_once()
    {
        $tree = $this->collection->tree();

        $this->assertCount(1, $tree);
        $this->assertSame('Welcome to the wiki', $tree[0]['document']->title);
        $this->assertSame(['Writing in markdown', 'Organizing documents'], array_map(fn ($n) => $n['document']->title, $tree[0]['children']));

        $this->seed(WikiSeeder::class);
        $this->assertSame(1, Collection::count());
    }

    public function test_markdown_is_rendered_and_html_is_escaped()
    {
        $html = Markdown::toHtml("## Title\n\n**bold** <script>alert(1)</script> [x](javascript:alert(1))\n\n| a |\n|---|\n| b |");

        $this->assertStringContainsString('<h2>Title</h2>', $html);
        $this->assertStringContainsString('<strong>bold</strong>', $html);
        $this->assertStringContainsString('<table>', $html);
        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringNotContainsString('javascript:', $html);
    }

    public function test_pages_need_a_login_and_a_permission()
    {
        $this->get(route('wiki'))->assertRedirect();

        Livewire::actingAs($this->userWith())->test('wiki::home')->assertForbidden();
        Livewire::actingAs($this->userWith('view wiki'))->test('wiki::documents-edit')->assertForbidden();
    }

    public function test_a_reader_sees_the_wiki_without_edit_buttons()
    {
        $reader = $this->userWith('view wiki');

        Livewire::actingAs($reader)->test('wiki::home')
            ->assertSee('Getting started')
            ->assertSee('Welcome to the wiki')
            ->assertDontSee('New document');

        Livewire::actingAs($reader)->test('wiki::documents-view', ['document' => $this->welcome])
            ->assertSee('Welcome to the wiki')
            ->assertSee('Writing in markdown')              // the tree and the child pages
            ->assertSeeHtml('<strong>markdown</strong>')
            ->assertDontSee(route('wiki.documents.edit', $this->welcome))   // no Edit button
            ->call('delete')
            ->assertForbidden();
    }

    public function test_search_finds_title_and_text()
    {
        Livewire::actingAs($this->userWith('view wiki'))->test('wiki::home')
            ->set('search', 'strikethrough')
            ->assertSee('Writing in markdown')
            ->assertDontSee('Organizing documents');
    }

    public function test_an_editor_creates_a_page_inside_another_and_it_gets_a_revision()
    {
        $editor = $this->userWith('view wiki', 'edit wiki');

        Livewire::actingAs($editor)
            ->withQueryParams(['parent' => $this->welcome->id])
            ->test('wiki::documents-edit')
            ->assertSet('document.collection_id', $this->collection->id)
            ->assertSet('document.parent_id', $this->welcome->id)
            ->set('document.title', 'Release checklist')
            ->set('document.body', "- [ ] tests green")
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect();

        $doc = Document::where('title', 'Release checklist')->firstOrFail();
        $this->assertSame($this->welcome->id, $doc->parent_id);
        $this->assertSame($editor->id, $doc->created_by);
        $this->assertSame(1, $doc->revisions()->count());
    }

    public function test_the_form_validates_and_refuses_a_parent_below_the_document()
    {
        $editor = $this->userWith('edit wiki');
        $child = Document::where('title', 'Writing in markdown')->firstOrFail();

        Livewire::actingAs($editor)->test('wiki::documents-edit')
            ->set('document.title', '')
            ->call('save')
            ->assertHasErrors(['document.title' => 'required']);

        Livewire::actingAs($editor)->test('wiki::documents-edit', ['document' => $this->welcome])
            ->set('document.parent_id', $child->id)
            ->call('save')
            ->assertHasErrors('document.parent_id');

        $other = Collection::create(['name' => 'Other']);
        $foreign = Document::create(['collection_id' => $other->id, 'title' => 'Elsewhere']);

        Livewire::actingAs($editor)->test('wiki::documents-edit', ['document' => $child])
            ->set('document.parent_id', $foreign->id)
            ->call('save')
            ->assertHasErrors('document.parent_id');

        $this->assertSame($this->welcome->id, $child->fresh()->parent_id);
    }

    public function test_moving_a_document_to_another_collection_takes_its_pages_along()
    {
        $other = Collection::create(['name' => 'Handbook']);

        Livewire::actingAs($this->userWith('edit wiki'))->test('wiki::documents-edit', ['document' => $this->welcome])
            ->set('document.collection_id', $other->id)
            ->assertSet('document.parent_id', null)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame(3, $other->documents()->count());
        $this->assertSame(0, $this->collection->documents()->count());
    }

    public function test_deleting_a_document_moves_its_pages_up()
    {
        Livewire::actingAs($this->userWith('view wiki', 'edit wiki'))
            ->test('wiki::documents-view', ['document' => $this->welcome])
            ->call('delete')
            ->assertRedirect(route('wiki.collections.view', $this->collection));

        $this->assertNull(Document::find($this->welcome->id));
        $this->assertSame(2, Document::whereNull('parent_id')->count());
    }

    public function test_a_revision_can_be_restored_and_only_of_this_document()
    {
        $editor = $this->userWith('view wiki', 'edit wiki');
        $first = $this->welcome->revisions()->first();

        $this->welcome->update(['body' => 'Rewritten']);
        $this->welcome->saveRevision($editor->id);
        $this->assertSame(2, $this->welcome->revisions()->count());

        $foreign = Document::where('title', 'Organizing documents')->firstOrFail()->revisions()->first();
        Livewire::actingAs($editor)->test('wiki::documents-history', ['document' => $this->welcome])
            ->call('restore', $foreign->id)
            ->assertNotFound();

        Livewire::actingAs($editor)->test('wiki::documents-history', ['document' => $this->welcome])
            ->assertSee('Rewritten')
            ->call('show', $first->id)
            ->assertSee('Restore this version')
            ->call('restore', $first->id)
            ->assertRedirect();

        $this->assertSame($first->body, $this->welcome->fresh()->body);
        $this->assertSame(3, $this->welcome->revisions()->count());
    }

    public function test_only_an_empty_collection_can_be_deleted()
    {
        $editor = $this->userWith('edit wiki');

        Livewire::actingAs($editor)->test('wiki::collections-edit', ['collection' => $this->collection])
            ->call('delete')
            ->assertHasErrors('collection.name');

        $empty = Collection::create(['name' => 'Empty']);
        Livewire::actingAs($editor)->test('wiki::collections-edit', ['collection' => $empty])
            ->call('delete')
            ->assertRedirect(route('wiki'));
        $this->assertNull(Collection::find($empty->id));
    }

    public function test_a_collection_is_created_and_its_icon_is_a_plain_name()
    {
        $editor = $this->userWith('edit wiki');

        Livewire::actingAs($editor)->test('wiki::collections-edit')
            ->set('collection.name', 'Handbook')
            ->set('collection.icon', 'book" onmouseover="x')
            ->call('save')
            ->assertHasErrors('collection.icon')
            ->set('collection.icon', 'book')
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect();

        $this->assertDatabaseHas('wiki_collections', ['name' => 'Handbook', 'icon' => 'book']);
    }

    public function test_the_wiki_is_knowledge_for_the_ai_module()
    {
        $docs = (new WikiKnowledge)();

        $this->assertCount(3, $docs);
        $this->assertStringStartsWith('# ', $docs[0]['text']);
        $this->assertContains('Writing in markdown', array_column($docs, 'title'));
    }

    public function test_permissions_and_menu_are_registered()
    {
        $this->assertContains('view wiki', config('auth.permissions'));
        $this->assertContains('wiki::menu', config('rapyd.menus.admin'));
    }
}
