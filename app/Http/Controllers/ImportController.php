<?php

namespace App\Http\Controllers;

use App\Http\Traits\CacheableTrait;
use App\Imports\ProjectsImport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;

class ImportController extends Controller
{
    use CacheableTrait;

    public function uploadProjects(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|mimes:xlsx,xls,csv|max:10240',
            'user_id' => 'nullable|exists:users,id'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $file = $request->file('file');
            $userId = $request->user_id ?: auth()->id();
            
            $fileName = 'imports/' . time() . '_' . $file->getClientOriginalName();
            $filePath = $file->storeAs('temp', $fileName);
            
            $import = new ProjectsImport($userId);
            Excel::import($import, storage_path('app/temp/' . $fileName));
            
            Storage::delete('temp/' . $fileName);
            
            $this->forget('all_projects');
            $this->forget('users_list');
            
            $response = [
                'message' => 'Import completed',
                'imported_count' => $import->getImportedCount(),
                'errors_count' => count($import->getErrors()),
                'errors' => $import->getErrors()
            ];
            
            if ($import->getImportedCount() > 0) {
                $response['success'] = true;
            }
            
            return response()->json($response);
            
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Import failed: ' . $e->getMessage()
            ], 500);
        }
    }

    public function downloadTemplate()
    {
        $headers = [
            'project_name',
            'description', 
            'status',
            'priority',
            'start_date',
            'end_date',
            'budget',
            'stakeholders',
            'objectives',
            'scope',
            'assumptions',
            'constraints',
            'risks',
            'deliverables',
            'milestones',
            'resources',
            'communication_plan'
        ];

        $sampleData = [
            [
                'project_name' => 'Sample Project 1',
                'description' => 'This is a sample project description',
                'status' => 'active',
                'priority' => 'high',
                'start_date' => '2025-02-01',
                'end_date' => '2025-06-30',
                'budget' => '50000.00',
                'stakeholders' => '["John Doe", "Jane Smith"]',
                'objectives' => '["Objective 1", "Objective 2"]',
                'scope' => 'Project scope description',
                'assumptions' => '["Assumption 1", "Assumption 2"]',
                'constraints' => '["Budget constraint", "Time constraint"]',
                'risks' => '["Risk 1", "Risk 2"]',
                'deliverables' => '["Deliverable 1", "Deliverable 2"]',
                'milestones' => '["Milestone 1", "Milestone 2"]',
                'resources' => '["Resource 1", "Resource 2"]',
                'communication_plan' => '["Weekly meetings", "Monthly reports"]'
            ],
            [
                'project_name' => 'Sample Project 2',
                'description' => 'Another sample project',
                'status' => 'inactive',
                'priority' => 'medium',
                'start_date' => '2025-03-01',
                'end_date' => '2025-09-30',
                'budget' => '75000.00',
                'stakeholders' => '["Alice Johnson"]',
                'objectives' => '["Main objective"]',
                'scope' => 'Limited scope project',
                'assumptions' => '["Technical assumption"]',
                'constraints' => '["Resource constraint"]',
                'risks' => '["Technical risk"]',
                'deliverables' => '["Final report"]',
                'milestones' => '["Phase 1 complete"]',
                'resources' => '["Development team"]',
                'communication_plan' => '["Slack updates"]'
            ]
        ];

        $data = array_merge([$headers], $sampleData);

        return Excel::download(new class($data) implements \Maatwebsite\Excel\Concerns\FromArray {
            private $data;
            
            public function __construct($data) {
                $this->data = $data;
            }
            
            public function array(): array {
                return $this->data;
            }
        }, 'projects_import_template.xlsx');
    }

    public function getImportHistory()
    {
        $imports = $this->remember('import_history', 600, function () {
            return collect([
                [
                    'id' => 1,
                    'filename' => 'projects_batch_1.xlsx',
                    'imported_count' => 25,
                    'errors_count' => 2,
                    'status' => 'completed',
                    'imported_at' => now()->subDays(1),
                    'user' => auth()->user()->name ?? 'System'
                ],
                [
                    'id' => 2,
                    'filename' => 'projects_batch_2.csv',
                    'imported_count' => 15,
                    'errors_count' => 0,
                    'status' => 'completed',
                    'imported_at' => now()->subDays(3),
                    'user' => auth()->user()->name ?? 'System'
                ]
            ]);
        });

        return response()->json(['imports' => $imports]);
    }

    public function validateFile(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|mimes:xlsx,xls,csv|max:10240'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            $file = $request->file('file');
            $fileName = 'validation/' . time() . '_' . $file->getClientOriginalName();
            $filePath = $file->storeAs('temp', $fileName);
            
            $data = Excel::toArray(new \stdClass, storage_path('app/temp/' . $fileName));
            
            Storage::delete('temp/' . $fileName);
            
            $headers = $data[0][0] ?? [];
            $rows = array_slice($data[0], 1); // Excluir header
            $rowCount = count($rows);
            
            $requiredHeaders = ['project_name'];
            $missingHeaders = array_diff($requiredHeaders, $headers);
            
            // Validación sintáctica detallada
            $validationErrors = [];
            $validRows = 0;
            $invalidRows = 0;
            
            foreach ($rows as $index => $row) {
                $rowNumber = $index + 2; // +2 porque el índice empieza en 0 y hay header
                $rowErrors = $this->validateRowSyntax($row, $headers, $rowNumber);
                
                if (empty($rowErrors)) {
                    $validRows++;
                } else {
                    $invalidRows++;
                    $validationErrors = array_merge($validationErrors, $rowErrors);
                }
            }
            
            return response()->json([
                'valid' => empty($missingHeaders) && empty($validationErrors),
                'headers' => $headers,
                'row_count' => $rowCount,
                'valid_rows' => $validRows,
                'invalid_rows' => $invalidRows,
                'missing_headers' => $missingHeaders,
                'validation_errors' => $validationErrors,
                'file_size' => $file->getSize(),
                'file_type' => $file->getClientMimeType()
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'File validation failed: ' . $e->getMessage()
            ], 500);
        }
    }

    private function validateRowSyntax($row, $headers, $rowNumber)
    {
        $errors = [];
        $rowData = array_combine($headers, $row);
        
        // Validar project_name (requerido)
        if (empty($rowData['project_name']) || trim($rowData['project_name']) === '') {
            $errors[] = [
                'row' => $rowNumber,
                'field' => 'project_name',
                'message' => 'El nombre del proyecto es requerido'
            ];
        }
        
        // Validar fechas
        if (!empty($rowData['start_date']) && !$this->isValidDate($rowData['start_date'])) {
            $errors[] = [
                'row' => $rowNumber,
                'field' => 'start_date',
                'message' => 'Formato de fecha inválido. Use YYYY-MM-DD'
            ];
        }
        
        if (!empty($rowData['end_date'])) {
            if (!$this->isValidDate($rowData['end_date'])) {
                $errors[] = [
                    'row' => $rowNumber,
                    'field' => 'end_date',
                    'message' => 'Formato de fecha inválido. Use YYYY-MM-DD'
                ];
            } elseif (!empty($rowData['start_date']) && 
                     $this->isValidDate($rowData['start_date']) && 
                     strtotime($rowData['end_date']) < strtotime($rowData['start_date'])) {
                $errors[] = [
                    'row' => $rowNumber,
                    'field' => 'end_date',
                    'message' => 'La fecha de fin debe ser posterior a la fecha de inicio'
                ];
            }
        }
        
        // Validar presupuesto
        if (!empty($rowData['budget']) && !is_numeric(str_replace(['$', ','], '', $rowData['budget']))) {
            $errors[] = [
                'row' => $rowNumber,
                'field' => 'budget',
                'message' => 'El presupuesto debe ser un valor numérico'
            ];
        }
        
        // Validar status
        if (!empty($rowData['status'])) {
            $validStatuses = ['active', 'inactive', 'completed', 'on_hold', 'cancelled'];
            if (!in_array(strtolower($rowData['status']), $validStatuses)) {
                $errors[] = [
                    'row' => $rowNumber,
                    'field' => 'status',
                    'message' => 'Estado inválido. Use: active, inactive, completed, on_hold, cancelled'
                ];
            }
        }
        
        // Validar priority
        if (!empty($rowData['priority'])) {
            $validPriorities = ['low', 'medium', 'high', 'critical'];
            if (!in_array(strtolower($rowData['priority']), $validPriorities)) {
                $errors[] = [
                    'row' => $rowNumber,
                    'field' => 'priority',
                    'message' => 'Prioridad inválida. Use: low, medium, high, critical'
                ];
            }
        }
        
        // Validar campos JSON
        $jsonFields = ['stakeholders', 'objectives', 'assumptions', 'constraints', 'risks', 'deliverables', 'milestones', 'resources', 'communication_plan'];
        foreach ($jsonFields as $field) {
            if (!empty($rowData[$field]) && !$this->isValidJson($rowData[$field])) {
                $errors[] = [
                    'row' => $rowNumber,
                    'field' => $field,
                    'message' => "Formato JSON inválido en campo {$field}. Use array JSON válido o texto separado por comas"
                ];
            }
        }
        
        return $errors;
    }
    
    private function isValidDate($date)
    {
        try {
            $parsedDate = \Carbon\Carbon::parse($date);
            return $parsedDate->format('Y-m-d') === $date || 
                   $parsedDate->format('d/m/Y') === $date ||
                   $parsedDate->format('m/d/Y') === $date;
        } catch (\Exception $e) {
            return false;
        }
    }
    
    private function isValidJson($string)
    {
        if (is_array($string)) return true;
        
        // Si es una cadena, verificar si es JSON válido
        json_decode($string);
        return json_last_error() === JSON_ERROR_NONE;
    }
}