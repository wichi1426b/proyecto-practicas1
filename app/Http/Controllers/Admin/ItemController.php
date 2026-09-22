<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Item;
use Illuminate\Http\Request;
class ItemController extends Controller
{
    public function index()
    {
        $items = Item::with('creador.persona')->orderByDesc('id')->get();
        return view('admin.items.index', compact('items'));
    }
    public function create()
    {
        return view('admin.items.create');
    }
    public function store(Request $request)
    {
        $datos = $request->validate([
            'nombre' => ['required', 'string', 'max:100'],
            'descripcion' => ['nullable', 'string', 'max:255'],
            'monto' => ['required', 'numeric', 'min:0.01'],
        ]);
        Item::create([
            'creado_por' => $request->user()->id,
            'nombre' => $datos['nombre'],
            'descripcion' => $datos['descripcion'] ?? null,
            'monto' => $datos['monto'],
            'estado' => 'activo',
        ]);

        return redirect()->route('admin.items.index')->with('mensaje', 'Ítem creado correctamente.');
    }
    public function edit(Item $item)
    {
        return view('admin.items.edit', compact('item'));
    }
    public function update(Request $request, Item $item)
    {
        $datos = $request->validate([
            'nombre' => ['required', 'string', 'max:100'],
            'descripcion' => ['nullable', 'string', 'max:255'],
            'monto' => ['required', 'numeric', 'min:0.01'],
            'estado' => ['required', 'in:activo,inactivo'],
        ]);

        $item->update($datos);

        return redirect()->route('admin.items.index')->with('mensaje', 'Ítem actualizado correctamente.');
    }
}
