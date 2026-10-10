<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wiki_collections', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('icon', 50)->nullable();
            $table->integer('position')->default(0);
            $table->timestamps();
        });

        Schema::create('wiki_documents', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('collection_id')->constrained('wiki_collections')->cascadeOnDelete();
            $table->uuid('parent_id')->nullable()->index();
            $table->string('title');
            $table->longText('body')->nullable();
            $table->integer('position')->default(0);
            // users may have integer or uuid keys: a string fits both
            $table->string('created_by', 36)->nullable();
            $table->string('updated_by', 36)->nullable();
            $table->timestamps();
        });

        Schema::create('wiki_revisions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('document_id')->constrained('wiki_documents')->cascadeOnDelete();
            $table->string('title');
            $table->longText('body')->nullable();
            $table->string('user_id', 36)->nullable();
            $table->timestamp('created_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wiki_revisions');
        Schema::dropIfExists('wiki_documents');
        Schema::dropIfExists('wiki_collections');
    }
};
