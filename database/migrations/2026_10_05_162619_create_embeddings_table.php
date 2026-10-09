<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Векторы для поиска по смыслу (теги, заказы). MySQL без векторного типа —
     * вектор хранится JSON-ом, сходство считается в PHP (данных немного).
     */
    public function up(): void
    {
        Schema::create('embeddings', function (Blueprint $table) {
            $table->id();
            $table->string('embeddable_type');
            $table->unsignedBigInteger('embeddable_id');
            // Векторы разных моделей несравнимы — при смене драйвера индекс пересобирается.
            $table->string('model');
            // sha1 исходного текста: не текст изменился — повторно не платим за эмбеддинг.
            $table->char('content_hash', 40);
            $table->json('vector');
            $table->timestamps();

            $table->unique(['embeddable_type', 'embeddable_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('embeddings');
    }
};
