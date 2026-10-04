<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Carrera;
use App\Models\Estudiante;
use App\Models\Persona;
use App\Support\Xlsx;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class EstudianteController extends Controller
{
    private const COLUMNAS = [
        'ci' => ['ci', 'carnet', 'carnet de identidad', 'cedula', 'documento'],
        'nombre1' => ['nombre 1', 'nombre1', 'primer nombre', 'nombre'],
        'nombre2' => ['nombre 2', 'nombre2', 'segundo nombre'],
        'ap_paterno' => ['apellido paterno', 'ap paterno', 'paterno', 'ap_paterno'],
        'ap_materno' => ['apellido materno', 'ap materno', 'materno', 'ap_materno'],
        'carrera' => ['carrera', 'area', 'carrera/area'],
    ];

    public function index(Request $request)
    {
        $buscar = trim((string) $request->input('buscar'));

        $estudiantes = Estudiante::with(['persona', 'carrera'])
            ->when($buscar, function ($q) use ($buscar) {
                $q->whereHas('persona', fn ($p) => $p->where('ci', 'like', "%{$buscar}%")
                    ->orWhere('nombre', 'like', "%{$buscar}%")
                    ->orWhere('ap_paterno', 'like', "%{$buscar}%")
                    ->orWhere('ap_materno', 'like', "%{$buscar}%"));
            })
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        return view('admin.estudiantes.index', compact('estudiantes', 'buscar'));
    }

    public function plantilla()
    {
        $ruta = tempnam(sys_get_temp_dir(), 'xlsx');

        Xlsx::escribir($ruta, 'Estudiantes',
            ['CI', 'Nombre 1', 'Nombre 2', 'Apellido paterno', 'Apellido materno', 'Carrera'],
            [['12345678', 'Juan', 'Carlos', 'Pérez', 'Gómez', 'Medicina']]
        );

        return response()->download($ruta, 'plantilla_estudiantes.xlsx')->deleteFileAfterSend();
    }

    public function importar(Request $request)
    {
        $request->validate([
            'archivo' => ['required', 'file', 'mimes:xlsx,csv,txt', 'max:5120'],
        ], [
            'archivo.mimes' => 'El archivo debe ser Excel (.xlsx) o CSV.',
        ]);

        $archivo = $request->file('archivo');

        try {
            $filas = strtolower($archivo->getClientOriginalExtension()) === 'xlsx'
                ? Xlsx::leer($archivo->getRealPath())
                : $this->leerCsv($archivo->getRealPath());
        } catch (\Throwable $e) {
            return back()->withErrors(['archivo' => 'No se pudo leer el archivo: ' . $e->getMessage()]);
        }

        $filas = array_values(array_filter($filas, fn ($f) => implode('', $f) !== ''));

        if (count($filas) < 2) {
            return back()->withErrors(['archivo' => 'El archivo no tiene datos. La primera fila debe ser el encabezado.']);
        }

        $mapa = $this->mapearEncabezados(array_shift($filas));
        $faltantes = array_diff(['ci', 'nombre1', 'ap_paterno', 'carrera'], array_keys($mapa));

        if ($faltantes) {
            return back()->withErrors(['archivo' => 'Faltan columnas obligatorias en el encabezado: ' . implode(', ', $faltantes)
                . '. Descarga la plantilla para ver el formato.']);
        }

        $creados = 0;
        $actualizados = 0;
        $errores = [];
        $carreras = Carrera::all()->keyBy(fn ($c) => $this->normalizar($c->nombre));

        foreach ($filas as $i => $fila) {
            $numeroFila = $i + 2;
            $valor = fn ($campo) => isset($mapa[$campo]) ? trim($fila[$mapa[$campo]] ?? '') : '';

            $ci = preg_replace('/\s+/', '', $valor('ci'));
            $nombre = trim($valor('nombre1') . ' ' . $valor('nombre2'));
            $paterno = $valor('ap_paterno');
            $materno = $valor('ap_materno');
            $nombreCarrera = $valor('carrera');

            if ($ci === '' || $valor('nombre1') === '' || $paterno === '' || $nombreCarrera === '') {
                $errores[] = "Fila {$numeroFila}: faltan datos obligatorios (CI, nombre 1, apellido paterno o carrera).";
                continue;
            }

            if (mb_strlen($ci) > 20) {
                $errores[] = "Fila {$numeroFila}: el CI '{$ci}' es demasiado largo.";
                continue;
            }

            DB::transaction(function () use ($ci, $nombre, $paterno, $materno, $nombreCarrera, &$carreras, &$creados, &$actualizados) {
                $clave = $this->normalizar($nombreCarrera);

                if (! isset($carreras[$clave])) {
                    $carreras[$clave] = Carrera::create(['nombre' => $nombreCarrera]);
                }

                Persona::updateOrCreate(['ci' => $ci], [
                    'nombre' => $nombre,
                    'ap_paterno' => $paterno,
                    'ap_materno' => $materno !== '' ? $materno : null,
                ]);

                $estudiante = Estudiante::where('persona_ci', $ci)->first();

                if ($estudiante) {
                    $estudiante->update(['carrera_id' => $carreras[$clave]->id]);
                    $actualizados++;
                } else {
                    Estudiante::create([
                        'persona_ci' => $ci,
                        'carrera_id' => $carreras[$clave]->id,
                        'estado' => 'activo',
                    ]);
                    $creados++;
                }
            });
        }

        $mensaje = "Importación finalizada: {$creados} estudiante(s) registrado(s), {$actualizados} actualizado(s).";

        if ($errores) {
            $mensaje .= ' ' . count($errores) . ' fila(s) con errores.';
        }

        return redirect()->route('admin.estudiantes.index')
            ->with('mensaje', $mensaje)
            ->with('erroresImportacion', $errores);
    }

    private function mapearEncabezados(array $encabezados): array
    {
        $mapa = [];

        foreach ($encabezados as $indice => $encabezado) {
            $normalizado = $this->normalizar($encabezado);

            foreach (self::COLUMNAS as $campo => $alias) {
                if (! isset($mapa[$campo]) && in_array($normalizado, $alias, true)) {
                    $mapa[$campo] = $indice;
                    break;
                }
            }
        }

        return $mapa;
    }

    private function normalizar(string $texto): string
    {
        return trim(preg_replace('/\s+/', ' ', str_replace(['_', '.'], ' ', Str::lower(Str::ascii($texto)))));
    }

    private function leerCsv(string $ruta): array
    {
        $contenido = file_get_contents($ruta);
        $contenido = preg_replace('/^\xEF\xBB\xBF/', '', $contenido);

        if (! mb_check_encoding($contenido, 'UTF-8')) {
            $contenido = mb_convert_encoding($contenido, 'UTF-8', 'Windows-1252');
        }

        $lineas = preg_split('/\r\n|\r|\n/', $contenido);
        $separador = substr_count($lineas[0] ?? '', ';') > substr_count($lineas[0] ?? '', ',') ? ';' : ',';

        return array_map(fn ($linea) => array_map('trim', str_getcsv($linea, $separador)), $lineas);
    }
}
