<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('expense_items')) {
            return;
        }

        Schema::table('expense_items', function (Blueprint $table) {
            foreach (Schema::getColumnListing('expense_items') as $column) {
                if (! in_array($column, [
                    'id', 'expense_id', 'raw_material_id', 'description', 'unit', 'qty',
                    'unit_cost', 'total_cost', 'notes', 'item_price', 'created_at', 'updated_at'
                ])) {
                    if ($column !== 'item_price') {
                        $table->dropColumn($column);
                    }
                }
            }
            if (! Schema::hasColumn('expense_items', 'item_price')) {
                $table->decimal('item_price', 18, 2)->nullable()->after('qty');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('expense_items')) {
            return;
        }

        Schema::table('expense_items', function (Blueprint $table) {
            if (Schema::hasColumn('expense_items', 'item_price')) {
                $table->dropColumn('item_price');
            }
        });
    }
};
