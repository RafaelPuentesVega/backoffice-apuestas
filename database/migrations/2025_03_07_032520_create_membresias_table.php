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
        Schema::create('membresias', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->decimal('precio', 10, 2);
            $table->decimal('comision_directa', 10, 2)->default(0); // Comisión directa al sponsor
            $table->decimal('porcentaje_rendimiento', 5, 2)->default(0); // % de rendimiento para el usuario
            $table->decimal('porcentaje_comision_sponsor', 5, 2)->default(0); // % del rendimiento que se da al sponsor
            $table->timestamps();
        });
    }
    
    public function down()
    {
        Schema::dropIfExists('membresias');
    }
    
};
