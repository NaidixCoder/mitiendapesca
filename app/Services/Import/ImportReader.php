<?php
// DEPRECATED (Etapa 2): archivo marcado como no referenciado en la auditoría actual.
// No borrar todavía. Revisar en limpieza final (post-freeze) con smoke test completo.
// Marcado: 2025-12-25

namespace App\Services\Import;

use ZipArchive;

class ImportReader
{
    public static function detectCsvDelimiter(string $sample): string
    {
        $c = substr_count($sample, ';') > substr_count($sample, ',') ? ';' : ',';
        return $c ?: ',';
    }

    public static function preview(string $path, array $opts = []): array
    {
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        if ($ext === 'csv') {
            $fh = @fopen($path, 'r');
            if (!$fh) return ['error'=>'No se pudo abrir el archivo'];
            $first = fgets($fh);
            $delim = self::detectCsvDelimiter($first);
            rewind($fh);
            $headers = null; $rows=[]; $count=0;
            while (($row = fgetcsv($fh, 0, $delim)) !== false && $count < 5) {
                if ($headers === null) { $headers = array_map(fn($h)=> trim((string)$h), $row); continue; }
                $rows[] = $row; $count++;
            }
            fclose($fh);
            return ['type'=>'csv','headers'=>$headers ?: [], 'rows'=>$rows, 'sheets'=>[]];
        } elseif ($ext === 'xlsx') {
            if (class_exists('PhpOffice\\PhpSpreadsheet\\IOFactory')) {
                try {
                    $sheetName = $opts['xlsx_sheet'] ?? null;
                    $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader('Xlsx');
                    $reader->setReadDataOnly(true);
                    $spread = $reader->load($path);
                    $sheets = [];
                    foreach ($spread->getSheetNames() as $i=>$n) { $sheets[] = ['index'=>$i,'name'=>$n]; }
                    $sheet = $sheetName ? $spread->getSheetByName($sheetName) : $spread->getSheet(0);
                    $rows = $sheet->toArray(null, true, true, true);
                    $headers = [];
                    if (!empty($rows)) {
                        $first = array_shift($rows);
                        foreach ($first as $v) $headers[] = trim((string)$v);
                    }
                    $data = [];
                    $count = 0;
                    foreach ($rows as $r) { $data[] = array_values($r); $count++; if ($count>=5) break; }
                    return ['type'=>'xlsx','headers'=>$headers,'rows'=>$data,'sheets'=>$sheets];
                } catch (\Throwable $e) { return ['error'=>'Error leyendo XLSX: '.$e->getMessage()]; }
            } else { return ['error'=>'Para leer XLSX instalá PhpSpreadsheet: composer require phpoffice/phpspreadsheet']; }
        } elseif ($ext === 'xls') {
            if (class_exists('PhpOffice\\PhpSpreadsheet\\IOFactory')) {
                try {
                    $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader('Xls');
                    $reader->setReadDataOnly(true);
                    $spread = $reader->load($path);
                    $sheets = [];
                    foreach ($spread->getSheetNames() as $i=>$n) { $sheets[] = ['index'=>$i,'name'=>$n]; }
                    $sheet = $spread->getSheet(0);
                    $rows = $sheet->toArray(null, true, true, true);
                    $headers = [];
                    if (!empty($rows)) {
                        $first = array_shift($rows);
                        foreach ($first as $v) $headers[] = trim((string)$v);
                    }
                    $data = [];
                    $count = 0;
                    foreach ($rows as $r) { $data[] = array_values($r); $count++; if ($count>=5) break; }
                    return ['type'=>'xls','headers'=>$headers,'rows'=>$data,'sheets'=>$sheets];
                } catch (\Throwable $e) { return ['error'=>'Error leyendo XLS: '.$e->getMessage()]; }
            } else { return ['error'=>'Para leer XLS instalá PhpSpreadsheet: composer require phpoffice/phpspreadsheet']; }
            if (class_exists('PhpOffice\\PhpSpreadsheet\\IOFactory')) {
                try {
                    $sheetName = $opts['xlsx_sheet'] ?? null;
                    $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader('Xlsx');
                    $reader->setReadDataOnly(true);
                    $spread = $reader->load($path);
                    $sheets = [];
                    foreach ($spread->getSheetNames() as $i=>$n) { $sheets[] = ['index'=>$i,'name'=>$n]; }
                    $sheet = $sheetName ? $spread->getSheetByName($sheetName) : $spread->getSheet(0);
                    $rows = $sheet->toArray(null, true, true, true);
                    // Build headers from first row, data from next 5
                    $headers = [];
                    if (!empty($rows)) {
                        $first = array_shift($rows);
                        foreach ($first as $v) $headers[] = trim((string)$v);
                    }
                    $data = [];
                    $count = 0;
                    foreach ($rows as $r) {
                        $data[] = array_values($r);
                        $count++; if ($count>=5) break;
                    }
                    return ['type'=>'xlsx','headers'=>$headers,'rows'=>$data,'sheets'=>$sheets];
                } catch (\Throwable $e) {
                    return ['error'=>'Error leyendo XLSX: '.$e->getMessage()];
                }
            } else {
                return ['error'=>'Para leer XLSX instalá PhpSpreadsheet: composer require phpoffice/phpspreadsheet'];
            }
        }
        return ['error'=>'Formato no soportado'];
    }

    public static function ensureCsv(string $path, array $opts=[]): array
    {
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        if ($ext === 'csv') return ['ok'=>true,'path'=>$path,'type'=>'csv'];
        if ($ext === 'xlsx') {
            if (!class_exists('PhpOffice\\PhpSpreadsheet\\IOFactory')) {
                return ['ok'=>false,'error'=>'XLSX requiere PhpSpreadsheet. Convertí a CSV o instalá la librería.'];
            }
            try {
                $sheetName = $opts['xlsx_sheet'] ?? null;
                $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReader('Xlsx');
                $reader->setReadDataOnly(true);
                if ($sheetName) $reader->setLoadSheetsOnly([$sheetName]);
                $spread = $reader->load($path);
                $tmp = sys_get_temp_dir().'/import_'.bin2hex(random_bytes(6)).'.csv';
                $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spread, 'Csv');
                $writer->setDelimiter(',');
                $writer->setEnclosure('"');
                $writer->setLineEnding("\r\n");
                $writer->setSheetIndex(0);
                $writer->save($tmp);
                return ['ok'=>true, 'path'=>$tmp, 'type'=>'csv'];
            } catch (\Throwable $e) {
                return ['ok'=>false,'error'=>'Error convirtiendo XLSX a CSV: '.$e->getMessage()];
            }
        }
        return ['ok'=>false,'error'=>'Formato no soportado'];
    }
}

