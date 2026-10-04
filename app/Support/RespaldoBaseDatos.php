<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;


class RespaldoBaseDatos
{
    public static function directorio(): string
    {
        $directorio = storage_path('app/backups');
        File::ensureDirectoryExists($directorio);

        return $directorio;
    }

    public static function generar(): string
    {
        $pdo = DB::connection()->getPdo();
        $baseDatos = DB::connection()->getDatabaseName();
        $nombre = 'backup_' . now()->format('Y-m-d_His') . '.sql';
        $ruta = self::directorio() . DIRECTORY_SEPARATOR . $nombre;

        $archivo = fopen($ruta, 'w');

        fwrite($archivo, "-- Copia de seguridad de `{$baseDatos}`\n");
        fwrite($archivo, '-- Generada: ' . now()->format('Y-m-d H:i:s') . "\n\n");
        fwrite($archivo, "SET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS=0;\n\n");

        $tablas = array_map(fn ($fila) => array_values((array) $fila)[0], DB::select('SHOW FULL TABLES WHERE Table_type = "BASE TABLE"'));

        foreach ($tablas as $tabla) {
            $crear = (array) DB::selectOne("SHOW CREATE TABLE `{$tabla}`");

            fwrite($archivo, "DROP TABLE IF EXISTS `{$tabla}`;\n");
            fwrite($archivo, $crear['Create Table'] . ";\n\n");

            $lote = [];
            foreach (DB::table($tabla)->cursor() as $fila) {
                $valores = array_map(
                    fn ($valor) => $valor === null ? 'NULL' : $pdo->quote((string) $valor),
                    (array) $fila
                );
                $lote[] = '(' . implode(',', $valores) . ')';

                if (count($lote) === 200) {
                    fwrite($archivo, "INSERT INTO `{$tabla}` VALUES\n" . implode(",\n", $lote) . ";\n");
                    $lote = [];
                }
            }

            if ($lote) {
                fwrite($archivo, "INSERT INTO `{$tabla}` VALUES\n" . implode(",\n", $lote) . ";\n");
            }

            fwrite($archivo, "\n");
        }

        fwrite($archivo, "SET FOREIGN_KEY_CHECKS=1;\n");
        fclose($archivo);

        return $nombre;
    }

    public static function listar(): array
    {
        return collect(File::files(self::directorio()))
            ->filter(fn ($f) => $f->getExtension() === 'sql')
            ->sortByDesc(fn ($f) => $f->getMTime())
            ->map(fn ($f) => [
                'nombre' => $f->getFilename(),
                'tamano' => $f->getSize(),
                'fecha' => \Illuminate\Support\Carbon::createFromTimestamp($f->getMTime()),
            ])
            ->values()
            ->all();
    }

    public static function ruta(string $nombre): ?string
    {
        if (! preg_match('/^backup_[\d\-_]+\.sql$/', $nombre)) {
            return null;
        }

        $ruta = self::directorio() . DIRECTORY_SEPARATOR . $nombre;

        return is_file($ruta) ? $ruta : null;
    }
}
