<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Как заказчики описывают задачи этого тега своими словами — по ним ИИ подбирает теги.
     */
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->text('keywords')->nullable()->after('slug');
        });
    }

    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropColumn('keywords');
        });
    }
};
