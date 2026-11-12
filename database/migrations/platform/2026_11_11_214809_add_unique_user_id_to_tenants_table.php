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
        Schema::connection('mercatura')
            ->table('tenants', function (Blueprint $table) {
                $table->unique('user_id', 'tenants_user_id_unique');
            });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection('mercatura')
            ->table('tenants', function (Blueprint $table) {
                $table->dropUnique('tenants_user_id_unique');
            });
    }
};
