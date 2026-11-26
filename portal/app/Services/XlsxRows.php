<?php

namespace App\Services;

use Illuminate\Validation\ValidationException;
use SimpleXMLElement;
use ZipArchive;

class XlsxRows
{
    private function xml(ZipArchive $zip, string $name): SimpleXMLElement
    {
        $stat = $zip->statName($name);
        if (! $stat || $stat['size'] > 5000000) {
            throw ValidationException::withMessages(['file' => 'El Excel no contiene una hoja válida o excede el tamaño permitido.']);
        }
        $contents = $zip->getFromName($name);
        if ($contents === false || stripos($contents, '<!DOCTYPE') !== false || stripos($contents, '<!ENTITY') !== false) {
            throw ValidationException::withMessages(['file' => 'El Excel contiene declaraciones XML no permitidas.']);
        }
        $xml = simplexml_load_string($contents, SimpleXMLElement::class, LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING);
        if ($xml === false) {
            throw ValidationException::withMessages(['file' => 'XML de Excel inválido.']);
        }

        return $xml;
    }

    public function read(string $path, int $columns, int $maxRows): array
    {
        $zip = new ZipArchive;
        if ($zip->open($path) !== true) {
            throw ValidationException::withMessages(['file' => 'El archivo XLSX no es válido.']);
        }
        try {
            $strings = [];
            if ($zip->locateName('xl/sharedStrings.xml') !== false) {
                foreach ($this->xml($zip, 'xl/sharedStrings.xml')->si as $item) {
                    $strings[] = implode('', array_map('strval', $item->xpath('.//*[local-name()="t"]')));
                }
            }
            $sheet = $this->xml($zip, 'xl/worksheets/sheet1.xml');
            $rows = [];
            foreach ($sheet->sheetData->row as $row) {
                $values = array_fill(0, $columns, '');
                foreach ($row->c as $cell) {
                    if (! preg_match('/^([A-Z]{1,3})[1-9]\d*$/D', (string) $cell['r'], $match)) {
                        throw ValidationException::withMessages(['file' => 'Una celda de Excel tiene una referencia inválida.']);
                    }
                    $column = 0;
                    foreach (str_split($match[1]) as $letter) {
                        $column = $column * 26 + ord($letter) - 64;
                    }
                    if ($column > $columns) {
                        if ((string) $cell->v !== '' || (string) $cell->is->t !== '' || isset($cell->is) || isset($cell->f)) {
                            $values[] = 'columna adicional';
                        }

                        continue;
                    }
                    if (isset($cell->f)) {
                        throw ValidationException::withMessages(['file' => 'Guarda las fórmulas como valores antes de importar.']);
                    }
                    $type = (string) $cell['t'];
                    $values[$column - 1] = $type === 's' ? ($strings[(int) $cell->v] ?? '') : ($type === 'inlineStr' ? implode('', array_map('strval', $cell->xpath('.//*[local-name()="t"]'))) : (string) $cell->v);
                }
                if (implode('', $values) !== '') {
                    $rows[] = $values;
                }
                if (count($rows) > $maxRows) {
                    break;
                }
            }

            return $rows;
        } finally {
            $zip->close();
        }
    }

    public function xlsx(array $rows, string $sheetName): string
    {
        $zip = new ZipArchive;
        $file = tempnam(sys_get_temp_dir(), 'siclin-xlsx');
        if ($file === false) {
            throw new \RuntimeException('No se pudo preparar el archivo de Excel.');
        }

        try {
            if ($zip->open($file, ZipArchive::OVERWRITE) !== true) {
                throw new \RuntimeException('No se pudo abrir el archivo de Excel.');
            }
            $zip->addFromString('[Content_Types].xml', '<?xml version="1.0"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/></Types>');
            $zip->addFromString('_rels/.rels', '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>');
            $zip->addFromString('xl/workbook.xml', '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets><sheet name="'.htmlspecialchars($sheetName, ENT_XML1 | ENT_QUOTES, 'UTF-8').'" sheetId="1" r:id="rId1"/></sheets></workbook>');
            $zip->addFromString('xl/_rels/workbook.xml.rels', '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/></Relationships>');
            $sheet = '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetData>';
            foreach ($rows as $index => $row) {
                $sheet .= '<row r="'.($index + 1).'">';
                foreach (array_values($row) as $column => $value) {
                    $n = $column + 1;
                    $letters = '';
                    while ($n > 0) {
                        $letters = chr(65 + ($n - 1) % 26).$letters;
                        $n = intdiv($n - 1, 26);
                    }
                    $reference = $letters.($index + 1);
                    $sheet .= is_int($value) || is_float($value) ? '<c r="'.$reference.'"><v>'.$value.'</v></c>' : '<c r="'.$reference.'" t="inlineStr"><is><t>'.htmlspecialchars((string) $value, ENT_XML1 | ENT_QUOTES, 'UTF-8').'</t></is></c>';
                }
                $sheet .= '</row>';
            }
            $zip->addFromString('xl/worksheets/sheet1.xml', $sheet.'</sheetData></worksheet>');
            if (! $zip->close() || ($contents = file_get_contents($file)) === false) {
                throw new \RuntimeException('No se pudo completar el archivo de Excel.');
            }

            return $contents;
        } finally {
            unlink($file);
        }
    }
}
