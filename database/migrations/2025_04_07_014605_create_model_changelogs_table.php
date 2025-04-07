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
        if(!Schema::hasTable('model_changelogs')){
            Schema::create('model_changelogs', function (Blueprint $table) {
                $table->id();
                $table->uuid();
                $table->string('request_id')->nullable();
                $table->string('model_class');
                $table->unsignedBigInteger('model_id');
                $table->string('actor_model')->nullable();
                $table->unsignedBigInteger('actor_id')->nullable();
                $table->string('message')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('model_changelogs');
    }
};
