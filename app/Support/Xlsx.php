<?php

namespace App\Support;

use RuntimeException;
use ZipArchive;


class Xlsx
{

    public static function leer(string $ruta): array
    {
        $zip = new ZipArchive();

        if ($zip->open($ruta) !== true) {
            throw new RuntimeException('No se pudo abrir el archivo Excel.');
        }

        $compartidos = [];
        $xmlCompartidos = $zip->getFromName('xl/sharedStrings.xml');

        if ($xmlCompartidos !== false) {
            $sst = simplexml_load_string($xmlCompartidos);

            foreach ($sst->si as $si) {
                if (isset($si->t)) {
                    $compartidos[] = (string) $si->t;
                } else {
                    $texto = '';
                    foreach ($si->r as $r) {
                        $texto .= (string) $r->t;
                    }
                    $compartidos[] = $texto;
                }
            }
        }

        $xmlHoja = $zip->getFromName('xl/worksheets/sheet1.xml');
        $zip->close();

        if ($xmlHoja === false) {
            throw new RuntimeException('El archivo Excel no tiene hojas legibles.');
        }

        $hoja = simplexml_load_string($xmlHoja);
        $filas = [];

        foreach ($hoja->sheetData->row as $row) {
            $fila = [];

            foreach ($row->c as $c) {
                $indice = self::indiceColumna(preg_replace('/\d+/', '', (string) $c['r']));
                $tipo = (string) $c['t'];

                $valor = match ($tipo) {
                    's' => $compartidos[(int) $c->v] ?? '',
                    'inlineStr' => (string) $c->is->t,
                    default => (string) $c->v,
                };

                if ($tipo === '' && is_numeric($valor) && floor((float) $valor) == (float) $valor) {
                    $valor = number_format((float) $valor, 0, '', '');
                }

                $fila[$indice] = trim($valor);
            }

            if ($fila) {
                $max = max(array_keys($fila));
                $filas[] = array_replace(array_fill(0, $max + 1, ''), $fila);
            }
        }

        return $filas;
    }

    public static function escribir(string $ruta, string $nombreHoja, array $encabezados, array $filas, array $titulo = []): void
    {
        $xmlFilas = '';
        $numFila = 1;

        foreach ($titulo as $linea) {
            $xmlFilas .= '<row r="' . $numFila . '">' . self::celda(0, $numFila, $linea, 2) . '</row>';
            $numFila++;
        }

        if ($titulo) {
            $numFila++;
        }

        $xmlFilas .= '<row r="' . $numFila . '">';
        foreach (array_values($encabezados) as $i => $encabezado) {
            $xmlFilas .= self::celda($i, $numFila, $encabezado, 1);
        }
        $xmlFilas .= '</row>';
        $numFila++;

        foreach ($filas as $fila) {
            $xmlFilas .= '<row r="' . $numFila . '">';
            foreach (array_values($fila) as $i => $valor) {
                $xmlFilas .= self::celda($i, $numFila, $valor, 0);
            }
            $xmlFilas .= '</row>';
            $numFila++;
        }

        $columnas = '';
        foreach (array_values($encabezados) as $i => $encabezado) {
            $columnas .= '<col min="' . ($i + 1) . '" max="' . ($i + 1) . '" width="' . max(12, mb_strlen($encabezado) + 4) . '" customWidth="1"/>';
        }

        $hoja = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<cols>' . $columnas . '</cols>'
            . '<sheetData>' . $xmlFilas . '</sheetData></worksheet>';

        $estilos = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<fonts count="3"><font><sz val="11"/><name val="Calibri"/></font>'
            . '<font><b/><sz val="11"/><name val="Calibri"/></font>'
            . '<font><b/><sz val="14"/><name val="Calibri"/></font></fonts>'
            . '<fills count="3"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill>'
            . '<fill><patternFill patternType="solid"><fgColor rgb="FFD9E1F2"/></patternFill></fill></fills>'
            . '<borders count="1"><border/></borders>'
            . '<cellStyleXfs count="1"><xf/></cellStyleXfs>'
            . '<cellXfs count="3"><xf fontId="0"/><xf fontId="1" fillId="2" applyFont="1" applyFill="1"/><xf fontId="2" applyFont="1"/></cellXfs>'
            . '</styleSheet>';

        $nombreHoja = htmlspecialchars(mb_substr($nombreHoja, 0, 31), ENT_XML1);

        $zip = new ZipArchive();
        if ($zip->open($ruta, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('No se pudo generar el archivo Excel.');
        }

        $zip->addFromString('[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            . '<Default Extension="xml" ContentType="application/xml"/>'
            . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            . '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
            . '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            . '</Types>');
        $zip->addFromString('_rels/.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            . '</Relationships>');
        $zip->addFromString('xl/workbook.xml', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<sheets><sheet name="' . $nombreHoja . '" sheetId="1" r:id="rId1"/></sheets></workbook>');
        $zip->addFromString('xl/_rels/workbook.xml.rels', '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
            . '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
            . '</Relationships>');
        $zip->addFromString('xl/styles.xml', $estilos);
        $zip->addFromString('xl/worksheets/sheet1.xml', $hoja);
        $zip->close();
    }

    private static function celda(int $columna, int $fila, $valor, int $estilo): string
    {
        $ref = self::letraColumna($columna) . $fila;
        $s = $estilo ? ' s="' . $estilo . '"' : '';

        if (is_int($valor) || is_float($valor)) {
            return '<c r="' . $ref . '"' . $s . '><v>' . $valor . '</v></c>';
        }

        $texto = htmlspecialchars((string) $valor, ENT_XML1 | ENT_QUOTES, 'UTF-8');

        return '<c r="' . $ref . '"' . $s . ' t="inlineStr"><is><t xml:space="preserve">' . $texto . '</t></is></c>';
    }

    private static function letraColumna(int $indice): string
    {
        $letra = '';
        $indice++;

        while ($indice > 0) {
            $resto = ($indice - 1) % 26;
            $letra = chr(65 + $resto) . $letra;
            $indice = intdiv($indice - 1, 26);
        }

        return $letra;
    }

    private static function indiceColumna(string $letras): int
    {
        $indice = 0;

        foreach (str_split(strtoupper($letras)) as $letra) {
            $indice = $indice * 26 + (ord($letra) - 64);
        }

        return $indice - 1;
    }
}
