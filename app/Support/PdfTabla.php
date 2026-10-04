<?php

namespace App\Support;

class PdfTabla
{
    private const ANCHO = 842;   
    private const ALTO = 595;
    private const MARGEN = 36;

    private const ANCHOS = [
        278, 278, 355, 556, 556, 889, 667, 191, 333, 333, 389, 584, 278, 333, 278, 278,
        556, 556, 556, 556, 556, 556, 556, 556, 556, 556, 278, 278, 584, 584, 584, 556,
        1015, 667, 667, 722, 722, 667, 611, 778, 722, 278, 500, 667, 556, 833, 722, 778,
        667, 778, 722, 667, 611, 722, 667, 944, 667, 667, 611, 278, 278, 278, 469, 556,
        333, 556, 556, 500, 556, 556, 278, 556, 556, 222, 222, 500, 222, 833, 556, 556,
        556, 556, 333, 500, 278, 556, 500, 722, 500, 500, 500, 334, 260, 334, 584,
    ];

    private array $paginas = [];
    private string $actual = '';
    private float $y = 0;

    public function __construct(private string $titulo, private array $subtitulos = [])
    {
    }

    /**
     * @param array 
     * @param array 
     * @param array 
     */
    public function generar(array $columnas, array $filas, array $resumen = []): string
    {
        $this->nuevaPagina();

        if ($resumen) {
            foreach ($resumen as $etiqueta => $valor) {
                $this->texto(self::MARGEN, $this->y, $etiqueta . ': ', 10, true);
                $this->texto(self::MARGEN + $this->anchoTexto($etiqueta . ': ', 10, true) + 3, $this->y, (string) $valor, 10);
                $this->y -= 14;
            }
            $this->y -= 6;
        }

        $anchoUtil = self::ANCHO - 2 * self::MARGEN;
        $pesoTotal = array_sum(array_map(fn ($c) => $c['peso'] ?? 1, $columnas)) ?: 1;
        $anchos = array_map(fn ($c) => $anchoUtil * ($c['peso'] ?? 1) / $pesoTotal, $columnas);

        $this->encabezadoTabla($columnas, $anchos);

        if (! $filas) {
            $this->texto(self::MARGEN + 4, $this->y - 12, 'Sin registros para el periodo seleccionado.', 9);
            $this->y -= 18;
        }

        foreach ($filas as $i => $fila) {
            if ($this->y - 16 < self::MARGEN + 20) {
                $this->nuevaPagina();
                $this->encabezadoTabla($columnas, $anchos);
            }

            if ($i % 2 === 1) {
                $this->actual .= sprintf("0.95 0.95 0.95 rg %.2F %.2F %.2F 16 re f 0 0 0 rg\n", self::MARGEN, $this->y - 16, $anchoUtil);
            }

            $x = self::MARGEN;
            foreach (array_values($fila) as $j => $valor) {
                $this->celda($x, $this->y - 11.5, $anchos[$j], (string) $valor, 8.5, false, $columnas[$j]['alinear'] ?? 'L');
                $x += $anchos[$j];
            }

            $this->y -= 16;
        }

        $this->cerrarPagina();

        return $this->documento();
    }

    private function nuevaPagina(): void
    {
        if ($this->actual !== '') {
            $this->cerrarPagina();
        }

        $this->actual = '';
        $this->y = self::ALTO - self::MARGEN;

        $this->texto(self::MARGEN, $this->y - 4, 'UPDS - Sistema de Cobros', 9, true);
        $this->texto(self::ANCHO - self::MARGEN - 120, $this->y - 4, 'Generado: ' . now()->format('d/m/Y H:i'), 8);
        $this->y -= 26;
        $this->texto(self::MARGEN, $this->y, $this->titulo, 15, true);
        $this->y -= 16;

        foreach ($this->subtitulos as $subtitulo) {
            $this->texto(self::MARGEN, $this->y, $subtitulo, 9.5);
            $this->y -= 13;
        }

        $this->actual .= sprintf("0.6 G 0.5 w %d %.2F m %d %.2F l S 0 G\n", self::MARGEN, $this->y, self::ANCHO - self::MARGEN, $this->y);
        $this->y -= 14;
    }

    private function cerrarPagina(): void
    {
        $numero = count($this->paginas) + 1;
        $this->texto(self::ANCHO / 2 - 20, self::MARGEN - 16, 'Página ' . $numero, 8);
        $this->paginas[] = $this->actual;
        $this->actual = '';
    }

    private function encabezadoTabla(array $columnas, array $anchos): void
    {
        $this->actual .= sprintf("0.85 0.88 0.95 rg %.2F %.2F %.2F 18 re f 0 0 0 rg\n", self::MARGEN, $this->y - 18, array_sum($anchos));

        $x = self::MARGEN;
        foreach ($columnas as $j => $columna) {
            $this->celda($x, $this->y - 12.5, $anchos[$j], $columna['titulo'], 8.5, true, $columna['alinear'] ?? 'L');
            $x += $anchos[$j];
        }

        $this->y -= 18;
    }

    private function celda(float $x, float $y, float $ancho, string $valor, float $tam, bool $negrita, string $alinear): void
    {
        $disponible = $ancho - 6;

        if ($this->anchoTexto($valor, $tam, $negrita) > $disponible) {
            while ($valor !== '' && $this->anchoTexto($valor . '...', $tam, $negrita) > $disponible) {
                $valor = mb_substr($valor, 0, -1);
            }
            $valor .= '...';
        }

        $posX = $alinear === 'R'
            ? $x + $ancho - 3 - $this->anchoTexto($valor, $tam, $negrita)
            : $x + 3;

        $this->texto($posX, $y, $valor, $tam, $negrita);
    }

    private function texto(float $x, float $y, string $texto, float $tam, bool $negrita = false): void
    {
        $fuente = $negrita ? 'F2' : 'F1';
        $this->actual .= sprintf("BT /%s %.1F Tf %.2F %.2F Td (%s) Tj ET\n", $fuente, $tam, $x, $y, $this->escapar($texto));
    }

    private function anchoTexto(string $texto, float $tam, bool $negrita): float
    {
        $total = 0;

        foreach (str_split($this->codificar($texto)) as $caracter) {
            $codigo = ord($caracter);
            $ancho = self::ANCHOS[$codigo - 32] ?? 556;
            $total += $negrita ? $ancho * 1.06 : $ancho;
        }

        return $total * $tam / 1000;
    }

    private function codificar(string $texto): string
    {
        $convertido = @iconv('UTF-8', 'CP1252//TRANSLIT', $texto);

        return $convertido === false ? $texto : $convertido;
    }

    private function escapar(string $texto): string
    {
        return strtr($this->codificar($texto), ['\\' => '\\\\', '(' => '\\(', ')' => '\\)', "\r" => '', "\n" => ' ']);
    }

    private function documento(): string
    {
        $objetos = [];
        $objetos[1] = '<< /Type /Catalog /Pages 2 0 R >>';
        $objetos[3] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica /Encoding /WinAnsiEncoding >>';
        $objetos[4] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold /Encoding /WinAnsiEncoding >>';

        $kids = [];
        $siguiente = 5;

        foreach ($this->paginas as $contenido) {
            $idPagina = $siguiente++;
            $idContenido = $siguiente++;
            $kids[] = "$idPagina 0 R";

            $objetos[$idPagina] = sprintf(
                '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 %d %d] /Resources << /Font << /F1 3 0 R /F2 4 0 R >> >> /Contents %d 0 R >>',
                self::ANCHO, self::ALTO, $idContenido
            );
            $objetos[$idContenido] = "<< /Length " . strlen($contenido) . " >>\nstream\n" . $contenido . "endstream";
        }

        $objetos[2] = '<< /Type /Pages /Kids [' . implode(' ', $kids) . '] /Count ' . count($kids) . ' >>';
        ksort($objetos);

        $pdf = "%PDF-1.4\n%\xE2\xE3\xCF\xD3\n";
        $offsets = [];

        foreach ($objetos as $id => $objeto) {
            $offsets[$id] = strlen($pdf);
            $pdf .= "$id 0 obj\n$objeto\nendobj\n";
        }

        $inicioXref = strlen($pdf);
        $total = max(array_keys($objetos)) + 1;
        $pdf .= "xref\n0 $total\n0000000000 65535 f \n";

        for ($i = 1; $i < $total; $i++) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$i] ?? 0);
        }

        $pdf .= "trailer\n<< /Size $total /Root 1 0 R >>\nstartxref\n$inicioXref\n%%EOF";

        return $pdf;
    }
}
