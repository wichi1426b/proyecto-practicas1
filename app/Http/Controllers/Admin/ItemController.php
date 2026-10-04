<?php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Carrera;
use App\Models\Item;
use Illuminate\Http\Request;
class ItemController extends Controller
{
    public function index()
    {
        $items = Item::with(['creador.persona', 'carreras'])->orderByDesc('id')->get();
        return view('admin.items.index', compact('items'));
    }
    public function create()
    {
        $carreras = Carrera::orderBy('nombre')->get();
        return view('admin.items.create', compact('carreras'));
    }
    public function store(Request $request)
    {
        $datos = $request->validate([
            'nombre' => ['required', 'string', 'max:100'],
            'descripcion' => ['nullable', 'string', 'max:255'],
            'monto' => ['required', 'numeric', 'min:0.01'],
            'carreras' => ['nullable', 'array'],
            'carreras.*' => ['exists:carreras,id'],
        ]);
        $item = Item::create([
            'creado_por' => $request->user()->id,
            'nombre' => $datos['nombre'],
            'descripcion' => $datos['descripcion'] ?? null,
            'monto' => $datos['monto'],
            'estado' => 'activo',
        ]);
        $item->carreras()->sync($datos['carreras'] ?? []);

        return redirect()->route('admin.items.index')->with('mensaje', 'Ítem creado correctamente.');
    }
    public function edit(Item $item)
    {
        $item->load('carreras');
        $carreras = Carrera::orderBy('nombre')->get();
        return view('admin.items.edit', compact('item', 'carreras'));
    }
    public function update(Request $request, Item $item)
    {
        $datos = $request->validate([
            'nombre' => ['required', 'string', 'max:100'],
            'descripcion' => ['nullable', 'string', 'max:255'],
            'monto' => ['required', 'numeric', 'min:0.01'],
            'estado' => ['required', 'in:activo,inactivo'],
            'carreras' => ['nullable', 'array'],
            'carreras.*' => ['exists:carreras,id'],
        ]);

        $item->update(collect($datos)->except('carreras')->all());
        $item->carreras()->sync($datos['carreras'] ?? []);

        return redirect()->route('admin.items.index')->with('mensaje', 'Ítem actualizado correctamente.');
    }
}
