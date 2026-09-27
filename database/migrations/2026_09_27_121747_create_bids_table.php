<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bids', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('executor_id')->constrained('users')->cascadeOnDelete();
            $table->unsignedInteger('offer_price');
            $table->text('approach_description');
            $table->unsignedSmallInteger('duration_days');
            $table->string('status', 20)->index();
            $table->timestamps();

            // Одно предложение на заказ от одного исполнителя.
            $table->unique(['order_id', 'executor_id']);
            $table->index(['executor_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bids');
    }
};
