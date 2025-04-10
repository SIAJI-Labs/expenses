<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $tableView = 'wallet_balances';

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Check if the view exists using raw SQL
        $viewExists = DB::select("SHOW TABLES LIKE '{$this->tableView}'");

        if (empty($viewExists)) {
            $tableName = [
                'wallet' => (new \App\Models\Wallet())->getTable(),
                'record' => (new \App\Models\Record())->getTable()
            ];

            DB::statement("
                CREATE VIEW {$this->tableView} AS
                SELECT 
                    w.id AS id,
                    pw.id AS parent_id,
                    w.name AS name,
                    CONCAT_WS(' - ', pw.name, w.name) AS formatted_name,
                    COALESCE(w.initial_balance, 0) +
                        -- Income: Add amount (received by wallet)
                        COALESCE(SUM(CASE WHEN r.type = 'income' AND r.to_wallet_id = w.id THEN r.amount ELSE 0 END), 0) -
                        -- Expense: Subtract (amount + extra_amount) (spent by wallet)
                        COALESCE(SUM(CASE WHEN r.type = 'expense' AND r.from_wallet_id = w.id THEN (r.amount + r.extra_amount) ELSE 0 END), 0) -
                        -- Transfer: Subtract (amount + extra_amount) for the sending wallet (from_wallet)
                        COALESCE(SUM(CASE WHEN r.type = 'transfer' AND r.from_wallet_id = w.id THEN (r.amount + r.extra_amount) ELSE 0 END), 0) +
                        -- Transfer: Add amount for the receiving wallet (to_wallet)
                        COALESCE(SUM(CASE WHEN r.type = 'transfer' AND r.to_wallet_id = w.id THEN r.amount ELSE 0 END), 0)
                    AS balance
                FROM 
                    {$tableName['wallet']} w
                LEFT JOIN 
                    {$tableName['wallet']} pw ON w.parent_id = pw.id
                LEFT JOIN 
                    {$tableName['record']} r ON (r.from_wallet_id = w.id OR r.to_wallet_id = w.id)
                        AND r.deleted_at IS NULL
                GROUP BY 
                    w.id, pw.id, w.name, pw.name, w.initial_balance
            ");
        }
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("DROP VIEW IF EXISTS ".$this->tableView);
    }
};
