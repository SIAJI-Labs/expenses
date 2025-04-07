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
        if(!Schema::hasTable('wallets')){
            Schema::create('wallets', function (Blueprint $table) {
                $table->id();
                $table->uuid();
                $table->string('request_id')->nullable();
                $table->unsignedBigInteger('user_id');
                $table->unsignedBigInteger('parent_id')->nullable();
                $table->string('name');
                $table->float('initial_balance')->default(0);
                $table->smallInteger('order')->default(0)->nullable()->comment('Order of the group, based on parent_id');
                $table->smallInteger('order_main')->default(0)->nullable()->comment('Order of all data');
                $table->timestamps();
                $table->softDeletes();

                $table->foreign('user_id')
                    ->references('id')
                    ->on((new \App\Models\User())->getTable())
                    ->cascadeOnUpdate()
                    ->cascadeOnDelete();
                $table->foreign('parent_id')
                    ->references('id')
                    ->on((new \App\Models\Wallet())->getTable())
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
        Schema::dropIfExists('wallets');
    }
};
