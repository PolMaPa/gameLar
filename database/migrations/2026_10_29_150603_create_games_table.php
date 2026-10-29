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
        Schema::create('games', function (Blueprint $table) {
            $table->id();

            $table->string('name', 150);
            $table->text('description');
            $table->string('genre', 50);
            $table->string('platform', 50);

            // Un juego puede no tener fecha de lanzamiento (anunciado, sin fecha).
            $table->date('release_date')->nullable();

            // Nota de 0.0 a 10.0 con un decimal; nula si aún no está valorado.
            $table->decimal('rating', 3, 1)->nullable();

            $table->string('image_url', 2048)->nullable();

            $table->timestamps();

            $table->index('genre');
            $table->index('platform');
            $table->index('release_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('games');
    }
};
