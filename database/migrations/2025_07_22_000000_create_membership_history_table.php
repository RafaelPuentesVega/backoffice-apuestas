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
        Schema::create('membership_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('membresia_id')->constrained('membresias');
            $table->decimal('precio_pagado', 10, 2);
            $table->string('status')->default('activa'); // activa, cancelada, expirada
            $table->string('payment_method')->nullable(); // método de pago utilizado
            $table->string('payment_reference')->nullable(); // referencia del pago
            $table->string('receipt_image')->nullable(); // ruta a la imagen del comprobante
            $table->timestamp('activated_at'); // fecha de activación
            $table->timestamp('expires_at')->nullable(); // fecha de expiración
            $table->timestamp('canceled_at')->nullable(); // fecha de cancelación (si aplica)
            $table->text('notes')->nullable(); // notas adicionales
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('membership_history');
    }
}; 