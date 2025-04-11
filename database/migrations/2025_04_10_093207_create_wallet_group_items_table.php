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
        if(!Schema::hasTable('wallet_group_items')){
            Schema::create('wallet_group_items', function (Blueprint $table) {
                $table->id();
                $table->uuid()->nullable();
                $table->string('request_id')->nullable();
                $table->unsignedBigInteger('wallet_group_id');
                $table->unsignedBigInteger('wallet_id');
                $table->timestamps();
                $table->softDeletes();

                $table->foreign('wallet_group_id')
                    ->references('id')
                    ->on((new \App\Models\WalletGroup())->getTable())
                    ->cascadeOnUpdate()
                    ->cascadeOnDelete();
                $table->foreign('wallet_id')
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
        Schema::dropIfExists('wallet_group_items');
    }
};
