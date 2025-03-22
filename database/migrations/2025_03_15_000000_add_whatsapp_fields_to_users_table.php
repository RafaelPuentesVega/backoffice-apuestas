<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Asegurarse de que el campo phone existe
        if (!Schema::hasColumn('users', 'phone')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('phone')->nullable()->after('email');
            });
        }

        // Make sure country_code exists (it's already in the fillable array)
        if (!Schema::hasColumn('users', 'country_code')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('country_code')->nullable()->after('phone');
            });
        }

        // Si existe el campo whatsapp_number, migrar datos a phone y eliminarlo
        if (Schema::hasColumn('users', 'whatsapp_number')) {
            // Migrar datos de whatsapp_number a phone si phone está vacío
            DB::statement('UPDATE users SET phone = whatsapp_number WHERE phone IS NULL AND whatsapp_number IS NOT NULL');
            
            // Eliminar la columna whatsapp_number
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('whatsapp_number');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No hacemos nada en down, ya que no queremos revertir la eliminación de whatsapp_number
    }
};
