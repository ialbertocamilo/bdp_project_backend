<?php

namespace App\Imports;

use App\Models\Project;
use App\Models\ProjectData;
use App\Models\ImportLog;
use Illuminate\Support\Str;
use Carbon\Carbon;

class ProjectsImport
{
    protected $userId;
    protected $importLog;
    protected $errors = [];
    protected $importedCount = 0;
    protected $totalRows = 0;

    public function __construct($userId)
    {
        $this->userId = $userId;
    }

    public function import($filePath)
    {
        $this->importLog = ImportLog::create([
            'user_id' => $this->userId,
            'filename' => basename($filePath),
            'status' => 'processing',
            'started_at' => now()
        ]);

        try {
            $rows = $this->parseFile($filePath);
            $this->totalRows = count($rows) - 1;

            foreach ($rows as $index => $row) {
                if ($index === 0) continue;

                try {
                    $this->importRow($row, $index);
                } catch (\Exception $e) {
                    $this->errors[] = [
                        'row' => $index,
                        'error' => $e->getMessage()
                    ];
                }
            }

            $this->importLog->update([
                'status' => count($this->errors) > 0 ? 'completed_with_errors' : 'completed',
                'imported_rows' => $this->importedCount,
                'failed_rows' => count($this->errors),
                'total_rows' => $this->totalRows,
                'errors' => $this->errors,
                'completed_at' => now()
            ]);
        } catch (\Exception $e) {
            $this->importLog->update([
                'status' => 'failed',
                'errors' => [['error' => $e->getMessage()]],
                'completed_at' => now()
            ]);
        }

        return $this;
    }

    private function parseFile($filePath)
    {
        $ext = pathinfo($filePath, PATHINFO_EXTENSION);

        if (strtolower($ext) === 'csv') {
            return $this->parseCSV($filePath);
        } elseif (in_array(strtolower($ext), ['xlsx', 'xls'])) {
            return $this->parseExcel($filePath);
        }

        throw new \Exception('Formato de archivo no soportado');
    }

    private function parseCSV($filePath)
    {
        $rows = [];
        if (($handle = fopen($filePath, 'r')) !== false) {
            while (($row = fgetcsv($handle)) !== false) {
                $rows[] = $row;
            }
            fclose($handle);
        }
        return $rows;
    }

    private function parseExcel($filePath)
    {
        if (!extension_loaded('zip')) {
            throw new \Exception('Extensión ZIP no disponible para leer Excel');
        }

        try {
            $zip = new \ZipArchive();
            if (!$zip->open($filePath)) {
                throw new \Exception('No se puede abrir el archivo Excel');
            }

            $xmlData = $zip->getFromName('xl/worksheets/sheet1.xml');
            $zip->close();

            $rows = [];
            $xml = simplexml_load_string($xmlData);

            foreach ($xml->sheetData->row as $row) {
                $rowData = [];
                foreach ($row->c as $cell) {
                    $rowData[] = (string)$cell->v ?? '';
                }
                $rows[] = $rowData;
            }

            return $rows;
        } catch (\Exception $e) {
            throw new \Exception('Error procesando Excel: ' . $e->getMessage());
        }
    }

    private function importRow($row, $index)
    {
        $data = [
            'name' => $row[0] ?? null,
            'description' => $row[1] ?? null,
            'project_type_id' => 1,
        ];

        if (empty($data['name'])) {
            throw new \Exception('Campo "name" requerido');
        }

        $uuid = Str::uuid()->toString();
        $project = new Project($data);
        $project->uuid = $uuid;
        $project->user_id = $this->userId;

        if ($project->save()) {
            ProjectData::create([
                'project_id' => $project->id,
                'step_name' => 'inicio',
                'substep_name' => 'principal',
                'content' => json_encode([])
            ]);

            ProjectData::create([
                'project_id' => $project->id,
                'step_name' => 'desarrollo',
                'substep_name' => 'actividades',
                'content' => json_encode([])
            ]);

            $this->importedCount++;
        } else {
            throw new \Exception('Error guardando proyecto');
        }
    }

    public function getResults()
    {
        return [
            'imported' => $this->importedCount,
            'failed' => count($this->errors),
            'errors' => $this->errors
        ];
    }
}
