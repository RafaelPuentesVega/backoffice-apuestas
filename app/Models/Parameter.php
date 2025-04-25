<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Parameter extends Model
{
    use HasFactory;

    protected $fillable = ['key', 'value', 'description', 'group'];
    
    /**
     * Obtiene un parámetro por su clave
     *
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public static function get(string $key, $default = null)
    {
        // Intentar obtener del caché primero
        return Cache::remember('parameter_' . $key, 3600, function () use ($key, $default) {
            $parameter = self::where('key', $key)->first();
            
            if (!$parameter) {
                return $default;
            }
            
            // Si es un JSON, decodificarlo
            if (self::isJson($parameter->value)) {
                return json_decode($parameter->value, true);
            }
            
            return $parameter->value;
        });
    }
    
    /**
     * Establece un valor para un parámetro
     *
     * @param string $key
     * @param mixed $value
     * @param string|null $description
     * @param string $group
     * @return bool
     */
    public static function set(string $key, $value, ?string $description = null, string $group = 'general')
    {
        // Si el valor es un array o un objeto, convertirlo a JSON
        if (is_array($value) || is_object($value)) {
            $value = json_encode($value);
        }
        
        $parameter = self::updateOrCreate(
            ['key' => $key],
            [
                'value' => $value,
                'description' => $description,
                'group' => $group
            ]
        );
        
        // Limpiar el caché
        Cache::forget('parameter_' . $key);
        
        return (bool) $parameter;
    }
    
    /**
     * Verifica si una cadena es un JSON válido
     *
     * @param string $string
     * @return bool
     */
    private static function isJson($string)
    {
        json_decode($string);
        return json_last_error() === JSON_ERROR_NONE;
    }
} 