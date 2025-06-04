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
        Schema::create('envios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plantilla_id')->constrained()->cascadeOnDelete();
            $table->enum('estado', ['pendiente', 'enviando', 'completado', 'fallido'])->default('pendiente');
            $table->integer('total')->default(0);
            $table->integer('enviados')->default(0);
            $table->integer('fallidos')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('envios');
    }
};
