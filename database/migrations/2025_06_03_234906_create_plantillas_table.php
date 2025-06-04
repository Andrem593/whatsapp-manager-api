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
        Schema::create('plantillas', function (Blueprint $table) {
            $table->id();
            $table->string('whatsapp_id')->nullable(); // ID de Facebook/WhatsApp
            $table->string('name');                     // Nombre de plantilla
            $table->string('language');                 // Idioma (ej: es_MX)
            $table->string('category');                 // Categoría (ej: MARKETING)
            $table->enum('status', ['PENDING', 'APPROVED', 'REJECTED'])->default('PENDING'); // Estado
            $table->enum('parameter_format', ['POSITIONAL', 'STRUCTURED'])->default('POSITIONAL');
            $table->json('components');                 // Componentes como el HEADER, BODY, BUTTONS
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plantillas');
    }
};
