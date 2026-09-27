<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Диалог заказчика и исполнителя — один на заказ, создаётся при выборе исполнителя.
     */
    public function up(): void
    {
        Schema::create('conversations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('executor_id')->constrained('users')->cascadeOnDelete();
            // Для сортировки списка чатов без подзапроса к сообщениям.
            $table->timestamp('last_message_at')->nullable();
            $table->timestamps();

            $table->index(['customer_id', 'last_message_at']);
            $table->index(['executor_id', 'last_message_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('conversations');
    }
};
