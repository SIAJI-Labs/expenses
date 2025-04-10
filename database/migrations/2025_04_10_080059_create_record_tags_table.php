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
        if(!Schema::hasTable('record_tags')){
            Schema::create('record_tags', function (Blueprint $table) {
                $table->id();
                $table->uuid()->nullable();
                $table->string('request_id')->nullable();
                $table->unsignedBigInteger('record_id');
                $table->unsignedBigInteger('tag_id');
                $table->timestamps();
                $table->softDeletes();

                $table->foreign('record_id')
                    ->references('id')
                    ->on((new \App\Models\Record())->getTable())
                    ->cascadeOnUpdate()
                    ->cascadeOnDelete();
                $table->foreign('tag_id')
                    ->references('id')
                    ->on((new \App\Models\Tag())->getTable())
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
        Schema::dropIfExists('record_tags');
    }
};
