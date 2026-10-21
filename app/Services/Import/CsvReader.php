<?php
namespace App\Services\Import;

final class CsvReader implements \IteratorAggregate
{
    private string $path;
    private string $delim = ',';
    private array $headers = [];

    /**
     * @param ?string $delimiter 'auto' (default), ';', ',', "\t"
     */
    public function open(string $path, ?string $delimiter = null): self
    {
        $this->path = $path;
        $fh = fopen($path, 'r');
        if (!$fh) throw new \RuntimeException('No se pudo abrir el archivo');

        $delimiter = $delimiter !== null ? trim((string)$delimiter) : 'auto';
        if ($delimiter === '' || strtolower($delimiter) === 'auto') {
            $probe = fgets($fh);
            $this->delim = (substr_count((string)$probe, ';') > substr_count((string)$probe, ',')) ? ';' : ',';
        } else {
            $this->delim = ($delimiter === '\\t' || $delimiter === "\t") ? "\t" : $delimiter;
        }
        rewind($fh);

        $headers = fgetcsv($fh, 0, $this->delim);
        if (!$headers) { fclose($fh); throw new \RuntimeException('CSV sin encabezados'); }

        // Normalizar encabezados: quitar BOM y espacios
        $headers = array_map(function ($h) {
            $h = (string)$h;
            $h = ltrim($h, "\xEF\xBB\xBF"); // BOM
            return trim($h);
        }, $headers);

        $this->headers = $headers;
        fclose($fh);

        return $this; // ← FALTABA ESTO
    }

    public function headers(): array { return $this->headers; }

    public function getIterator(): \Traversable
    {
        $fh = fopen($this->path, 'r');
        if (!$fh) throw new \RuntimeException('No se pudo abrir el archivo');

        // Saltar encabezados
        fgetcsv($fh, 0, $this->delim);

        $rownum = 1;
        while (($cells = fgetcsv($fh, 0, $this->delim)) !== false) {
            $rownum++;
            if ($cells === [null] || $cells === false) continue;

            // Si el CSV se desalineó (típico: separador ',' + coma decimal sin comillas),
            // intentamos reparar IVA (0,21 / 0,105) cuando se partió en 2 columnas.
            $hcount = count($this->headers);
            $ccount = is_array($cells) ? count($cells) : 0;

            if ($ccount !== $hcount) {
                $ivaIdx = array_search('iva_rate', $this->headers, true);
                if ($ivaIdx !== false && isset($cells[$ivaIdx], $cells[$ivaIdx + 1])) {
                    $a = trim((string)$cells[$ivaIdx]);
                    $b = trim((string)$cells[$ivaIdx + 1]);

                    // Caso típico: "0,21" -> ["0","21"] ; "0,105" -> ["0","105"]
                    if (preg_match('/^\d+$/', $a) && preg_match('/^\d+$/', $b) && $a === '0') {
                        $merged = $a . ',' . $b;
                        array_splice($cells, $ivaIdx, 2, [$merged]);
                        $ccount = count($cells);
                    }
                }
            }

            $parseError = null;
            if ($ccount !== $hcount) {
                $parseError = 'CSV desalineado: revisá separador y decimales. ' .
                    'Si tu archivo usa "," como separador, el IVA debe ir como 0.21 (o "0,21" entre comillas) o exportá con ";".';
            }

            $row = [];
            if ($parseError) {
                $row['_csv_parse_error'] = $parseError;
            }
            foreach ($this->headers as $i=>$h) {
                $row[$h] = array_key_exists($i, $cells) ? (is_string($cells[$i]) ? trim($cells[$i]) : $cells[$i]) : null;
            }
            yield $rownum => $row;
        }
        fclose($fh);
    }
}
