<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Город мастера и город заказа. В формах обязателен; nullable — только чтобы миграция
     * встала на уже существующие записи. Без города мастер не видит ни одного заказа.
     */
    public function up(): void
    {
        Schema::table('executor_profiles', function (Blueprint $table) {
            $table->foreignId('city_id')->nullable()->after('user_id')->constrained()->restrictOnDelete();
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('city_id')->nullable()->after('executor_id')->constrained()->restrictOnDelete();
            $table->index(['city_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['city_id', 'status']);
            $table->dropConstrainedForeignId('city_id');
        });

        Schema::table('executor_profiles', function (Blueprint $table) {
            $table->dropConstrainedForeignId('city_id');
        });
    }
};
