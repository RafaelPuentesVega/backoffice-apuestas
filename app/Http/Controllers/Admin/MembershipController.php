<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Membresia;
use Illuminate\Http\Request;
use Inertia\Inertia;

class MembershipController extends Controller
{
    /**
     * Muestra el listado de membresías
     */
    public function index()
    {
        $membresias = Membresia::orderBy('precio')->get();
        
        return Inertia::render('admin/membership/Index', [
            'membresias' => $membresias
        ]);
    }
    
    /**
     * Muestra el formulario para crear una nueva membresía
     */
    public function create()
    {
        return Inertia::render('admin/membership/Create');
    }
    
    /**
     * Guarda una nueva membresía en la base de datos
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:100',
            'precio' => 'required|numeric|min:0',
            'comision_directa' => 'required|numeric|min:0',
            'porcentaje_rendimiento' => 'required|numeric|min:0|max:100',
            'porcentaje_comision_sponsor' => 'required|numeric|min:0|max:100',
        ]);
        
        Membresia::create($validated);
        
        return redirect()->route('admin.memberships.index')
            ->with('success', 'Membresía creada correctamente');
    }
    
    /**
     * Muestra el formulario para editar una membresía existente
     */
    public function edit(Membresia $membership)
    {
        return Inertia::render('admin/membership/Edit', [
            'membresia' => $membership
        ]);
    }
    
    /**
     * Actualiza una membresía existente
     */
    public function update(Request $request, Membresia $membership)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:100',
            'precio' => 'required|numeric|min:0',
            'comision_directa' => 'required|numeric|min:0',
            'porcentaje_rendimiento' => 'required|numeric|min:0|max:100',
            'porcentaje_comision_sponsor' => 'required|numeric|min:0|max:100',
        ]);
        
        $membership->update($validated);
        
        return redirect()->route('admin.memberships.index')
            ->with('success', 'Membresía actualizada correctamente');
    }
    
    /**
     * Elimina una membresía
     */
    public function destroy(Membresia $membership)
    {
        // Verificar si hay usuarios con esta membresía antes de eliminar
        if ($membership->users()->count() > 0) {
            return back()->with('error', 'No se puede eliminar la membresía porque hay usuarios que la están utilizando');
        }
        
        $membership->delete();
        
        return back()->with('success', 'Membresía eliminada correctamente');
    }
} 