<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('comisiones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('sponsor_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('membresia_id')->nullable()->constrained('membresias')->onDelete('set null');
            $table->decimal('monto', 10, 2);
            $table->enum('tipo', ['directa', 'rendimiento']); // Indica si es directa o por rendimiento
            $table->timestamps();
        });
    }
    
    public function down()
    {
        Schema::dropIfExists('comisiones');
    }
    
};
