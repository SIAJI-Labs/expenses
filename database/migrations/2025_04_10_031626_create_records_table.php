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
        if(!Schema::hasTable('records')){
            Schema::create('records', function (Blueprint $table) {
                $table->id();
                $table->uuid();
                $table->string('request_id')->nullable();
                $table->unsignedBigInteger('user_id');
                $table->unsignedBigInteger('category_id')->nullable();
                $table->enum('type', ['expense', 'income', 'transfer'])->default('expense');
                $table->unsignedBigInteger('from_wallet_id');
                $table->unsignedBigInteger('to_wallet_id')->nullable();
                $table->dateTime('timestamp');
                $table->float('amount')->default(0);
                $table->float('extra_amount')->default(0);
                $table->float('extra_percentage')->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->foreign('user_id')
                    ->references('id')
                    ->on((new \App\Models\User())->getTable())
                    ->cascadeOnUpdate()
                    ->cascadeOnDelete();
                $table->foreign('category_id')
                    ->references('id')
                    ->on((new \App\Models\Category())->getTable())
                    ->cascadeOnUpdate()
                    ->cascadeOnDelete();
                $table->foreign('from_wallet_id')
                    ->references('id')
                    ->on((new \App\Models\Wallet())->getTable())
                    ->cascadeOnUpdate()
                    ->cascadeOnDelete();
                $table->foreign('to_wallet_id')
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
        Schema::dropIfExists('records');
    }
};
