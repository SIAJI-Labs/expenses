<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if(!Schema::hasTable('model_changelog_items')){
            Schema::create('model_changelog_items', function (Blueprint $table) {
                $table->id();
                $table->uuid();
                $table->string('request_id')->nullable();
                $table->unsignedBigInteger('model_changelog_id');
                $table->string('column');
                $table->longText('original')->nullable();
                $table->longText('changed')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->foreign('model_changelog_id')
                    ->references('id')
                    ->on((new \App\Models\ModelChangelog())->getTable())
                    ->cascadeOnUpdate()
                    ->cascadeOnDelete();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('model_changelog_items');
    }
};
