<?php

namespace App\Modules\Handbook\Tests\Feature;

use App\Modules\Handbook\Database\Seeders\HandbookSeeder;
use App\Modules\Handbook\Models\Collection;
use App\Modules\Handbook\Models\Document;
use App\Modules\Handbook\Support\Markdown;
use App\Modules\Handbook\Tests\Models\User;
use App\Modules\Handbook\Tests\TestCase;
use App\Modules\Handbook\HandbookKnowledge;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Livewire\Livewire;
use Spatie\Permission\Models\Permission;

class HandbookTest extends TestCase
{
    use DatabaseMigrations;

    protected Collection $collection;

    protected Document $welcome;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(HandbookSeeder::class);
        $this->collection = Collection::firstOrFail();
        $this->welcome = Document::where('title', 'Welcome to the handbook')->firstOrFail();
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
        $this->assertSame('Welcome to the handbook', $tree[0]['document']->title);
        $this->assertSame(['Writing in markdown', 'Organizing documents'], array_map(fn ($n) => $n['document']->title, $tree[0]['children']));

        $this->seed(HandbookSeeder::class);
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
        $this->get(route('handbook'))->assertRedirect();

        Livewire::actingAs($this->userWith())->test('handbook::home')->assertForbidden();
        Livewire::actingAs($this->userWith('view handbook'))->test('handbook::documents-move', ['document' => $this->welcome])->assertForbidden();
    }

    public function test_a_reader_sees_the_handbook_without_edit_buttons()
    {
        $reader = $this->userWith('view handbook');

        Livewire::actingAs($reader)->test('handbook::home')
            ->assertSee('Getting started')
            ->assertSee('Welcome to the handbook')
            ->assertDontSee('New document');

        Livewire::actingAs($reader)->test('handbook::documents-view', ['document' => $this->welcome])
            ->assertSee('Welcome to the handbook')
            ->assertSee('Writing in markdown')              // the tree and the child pages
            ->assertSeeHtml('<strong>markdown</strong>')
            ->assertDontSee(route('handbook.documents.move', $this->welcome))   // no Move button
            ->call('delete')
            ->assertForbidden();
    }

    public function test_search_finds_title_and_text()
    {
        Livewire::actingAs($this->userWith('view handbook'))->test('handbook::home')
            ->set('search', 'strikethrough')
            ->assertSee('Writing in markdown')
            ->assertDontSee('Organizing documents');
    }

    protected function version(Document $document): string
    {
        return $document->fresh()->updated_at->format('Y-m-d H:i:s');
    }

    public function test_new_page_is_created_untitled_inside_its_parent_and_opens_in_the_editor()
    {
        $editor = $this->userWith('view handbook', 'edit handbook');

        Livewire::actingAs($editor)->test('handbook::documents-view', ['document' => $this->welcome])
            ->call('newDocument', null, $this->welcome->id)
            ->assertRedirectContains('?new=1');

        $doc = Document::where('parent_id', $this->welcome->id)->latest('created_at')->orderByDesc('id')->firstOrFail();
        $this->assertSame('', $doc->title);
        $this->assertSame('Untitled', $doc->label());
        $this->assertSame($this->collection->id, $doc->collection_id);
        $this->assertSame($editor->id, $doc->created_by);

        Livewire::actingAs($this->userWith('view handbook'))->test('handbook::home')
            ->call('newDocument')
            ->assertForbidden();
    }

    public function test_the_editor_saves_title_and_text_in_place()
    {
        $editor = $this->userWith('view handbook', 'edit handbook');

        $result = Livewire::actingAs($editor)->test('handbook::documents-view', ['document' => $this->welcome])
            ->assertSee('handbookEditor', false)
            ->call('saveContent', 'Welcome aboard', "## Hello\n\n- [ ] read this", $this->version($this->welcome))
            ->assertHasNoErrors()
            ->effects['returns'][0];

        $this->assertSame('Welcome aboard', $result['label']);
        $doc = $this->welcome->fresh();
        $this->assertSame('Welcome aboard', $doc->title);
        $this->assertSame("## Hello\n\n- [ ] read this", $doc->body);
        $this->assertSame($editor->id, $doc->updated_by);
        $this->assertSame($result['version'], $this->version($doc));
    }

    public function test_a_reader_cannot_save_and_gets_no_editor()
    {
        $reader = $this->userWith('view handbook');

        Livewire::actingAs($reader)->test('handbook::documents-view', ['document' => $this->welcome])
            ->assertDontSee('handbookEditor', false)
            ->call('saveContent', 'Hacked', 'x', $this->version($this->welcome))
            ->assertForbidden();

        $this->assertSame('Welcome to the handbook', $this->welcome->fresh()->title);
    }

    public function test_a_save_based_on_an_old_version_of_someone_else_is_refused()
    {
        $ada = $this->userWith('edit handbook');
        $bob = $this->userWith('edit handbook');
        $loaded = $this->version($this->welcome);

        $this->travel(5)->seconds();
        Livewire::actingAs($bob)->test('handbook::documents-view', ['document' => $this->welcome])
            ->call('saveContent', 'Bob', 'by Bob', $loaded);

        $result = Livewire::actingAs($ada)->test('handbook::documents-view', ['document' => $this->welcome])
            ->call('saveContent', 'Ada', 'by Ada', $loaded)
            ->effects['returns'][0];

        $this->assertTrue($result['conflict']);
        $this->assertSame('by Bob', $this->welcome->fresh()->body);
    }

    public function test_the_saves_of_a_few_minutes_are_one_revision()
    {
        $editor = $this->userWith('edit handbook');
        $before = $this->welcome->revisions()->count();
        $component = Livewire::actingAs($editor)->test('handbook::documents-view', ['document' => $this->welcome]);

        $component->call('saveContent', 'One', 'first', $this->version($this->welcome));
        $this->travel(1)->minutes();
        $component->call('saveContent', 'One', 'second', $this->version($this->welcome));
        $this->assertSame($before + 1, $this->welcome->revisions()->count());
        $this->assertSame('second', $this->welcome->revisions()->first()->body);

        $this->travel(11)->minutes();
        $component->call('saveContent', 'One', 'third', $this->version($this->welcome));
        $this->assertSame($before + 2, $this->welcome->revisions()->count());
    }

    public function test_the_move_form_refuses_a_parent_below_the_document_or_elsewhere()
    {
        $editor = $this->userWith('edit handbook');
        $child = Document::where('title', 'Writing in markdown')->firstOrFail();

        Livewire::actingAs($editor)->test('handbook::documents-move', ['document' => $this->welcome])
            ->set('document.parent_id', $child->id)
            ->call('save')
            ->assertHasErrors('document.parent_id');

        $other = Collection::create(['name' => 'Other']);
        $foreign = Document::create(['collection_id' => $other->id, 'title' => 'Elsewhere']);

        Livewire::actingAs($editor)->test('handbook::documents-move', ['document' => $child])
            ->set('document.parent_id', $foreign->id)
            ->call('save')
            ->assertHasErrors('document.parent_id');

        $this->assertSame($this->welcome->id, $child->fresh()->parent_id);

        Livewire::actingAs($this->userWith('view handbook'))->test('handbook::documents-move', ['document' => $child])
            ->assertForbidden();
    }

    public function test_moving_a_document_to_another_collection_takes_its_pages_along()
    {
        $other = Collection::create(['name' => 'Handbook']);

        Livewire::actingAs($this->userWith('edit handbook'))->test('handbook::documents-move', ['document' => $this->welcome])
            ->set('document.collection_id', $other->id)
            ->assertSet('document.parent_id', null)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame(3, $other->documents()->count());
        $this->assertSame(0, $this->collection->documents()->count());
    }

    public function test_the_editor_assets_are_served_by_the_module()
    {
        $this->get(route('handbook.asset', 'editor.js'))->assertOk()->assertHeader('Content-Type', 'application/javascript; charset=utf-8');
        $this->get(route('handbook.asset', 'editor.css'))->assertOk();
        $this->get(route('handbook.asset', 'composer.json'))->assertNotFound();
    }

    public function test_deleting_a_document_moves_its_pages_up()
    {
        Livewire::actingAs($this->userWith('view handbook', 'edit handbook'))
            ->test('handbook::documents-view', ['document' => $this->welcome])
            ->call('delete')
            ->assertRedirect(route('handbook.collections.view', $this->collection));

        $this->assertNull(Document::find($this->welcome->id));
        $this->assertSame(2, Document::whereNull('parent_id')->count());
    }

    public function test_a_revision_can_be_restored_and_only_of_this_document()
    {
        $editor = $this->userWith('view handbook', 'edit handbook');
        $first = $this->welcome->revisions()->first();

        $this->welcome->update(['body' => 'Rewritten']);
        $this->welcome->saveRevision($editor->id);
        $this->assertSame(2, $this->welcome->revisions()->count());

        $foreign = Document::where('title', 'Organizing documents')->firstOrFail()->revisions()->first();
        Livewire::actingAs($editor)->test('handbook::documents-history', ['document' => $this->welcome])
            ->call('restore', $foreign->id)
            ->assertNotFound();

        Livewire::actingAs($editor)->test('handbook::documents-history', ['document' => $this->welcome])
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
        $editor = $this->userWith('edit handbook');

        Livewire::actingAs($editor)->test('handbook::collections-edit', ['collection' => $this->collection])
            ->call('delete')
            ->assertHasErrors('collection.name');

        $empty = Collection::create(['name' => 'Empty']);
        Livewire::actingAs($editor)->test('handbook::collections-edit', ['collection' => $empty])
            ->call('delete')
            ->assertRedirect(route('handbook'));
        $this->assertNull(Collection::find($empty->id));
    }

    public function test_a_collection_is_created_and_its_icon_is_a_plain_name()
    {
        $editor = $this->userWith('edit handbook');

        Livewire::actingAs($editor)->test('handbook::collections-edit')
            ->set('collection.name', 'Handbook')
            ->set('collection.icon', 'book" onmouseover="x')
            ->call('save')
            ->assertHasErrors('collection.icon')
            ->set('collection.icon', 'book')
            ->call('save')
            ->assertHasNoErrors()
            ->assertRedirect();

        $this->assertDatabaseHas('handbook_collections', ['name' => 'Handbook', 'icon' => 'book']);
    }

    public function test_the_handbook_is_knowledge_for_the_ai_module()
    {
        $docs = (new HandbookKnowledge)();

        $this->assertCount(3, $docs);
        $this->assertStringStartsWith('# ', $docs[0]['text']);
        $this->assertContains('Writing in markdown', array_column($docs, 'title'));
    }

    public function test_permissions_and_menu_are_registered()
    {
        $this->assertContains('view handbook', config('auth.permissions'));
        $this->assertContains('handbook::menu', config('rapyd.menus.admin'));
    }
}
