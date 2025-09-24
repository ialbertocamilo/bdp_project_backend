<?php

namespace App\Imports;

use App\Models\Project;
use App\Models\ProjectData;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithChunkReading;

class ProjectsImport implements ToCollection, WithHeadingRow, WithValidation, WithBatchInserts, WithChunkReading
{
    private $errors = [];
    private $imported = 0;
    private $userId;

    public function __construct($userId = null)
    {
        $this->userId = $userId ?: auth()->id();
    }

    public function collection(Collection $rows)
    {
        foreach ($rows as $index => $row) {
            try {
                $this->processRow($row, $index + 2);
            } catch (\Exception $e) {
                $this->errors[] = "Row " . ($index + 2) . ": " . $e->getMessage();
            }
        }
    }

    private function processRow($row, $rowNumber)
    {
        $uuid = Str::uuid()->toString();
        
        $user = User::find($this->userId);
        if (!$user) {
            throw new \Exception("Invalid user ID");
        }

        $project = Project::create([
            'uuid' => $uuid,
            'name' => $row['project_name'] ?? 'Imported Project ' . $rowNumber,
            'description' => $row['description'] ?? '',
            'user_id' => $this->userId,
            'status' => $this->validateStatus($row['status'] ?? 'active'),
            'priority' => $this->validatePriority($row['priority'] ?? 'medium'),
            'start_date' => $this->parseDate($row['start_date'] ?? null),
            'end_date' => $this->parseDate($row['end_date'] ?? null),
            'budget' => $this->parseDecimal($row['budget'] ?? 0),
        ]);

        ProjectData::create([
            'project_uuid' => $uuid,
            'project_id' => $project->id,
            'stakeholders' => $this->parseJson($row['stakeholders'] ?? '[]'),
            'objectives' => $this->parseJson($row['objectives'] ?? '[]'),
            'scope' => $row['scope'] ?? '',
            'assumptions' => $this->parseJson($row['assumptions'] ?? '[]'),
            'constraints' => $this->parseJson($row['constraints'] ?? '[]'),
            'risks' => $this->parseJson($row['risks'] ?? '[]'),
            'deliverables' => $this->parseJson($row['deliverables'] ?? '[]'),
            'milestones' => $this->parseJson($row['milestones'] ?? '[]'),
            'resources' => $this->parseJson($row['resources'] ?? '[]'),
            'communication_plan' => $this->parseJson($row['communication_plan'] ?? '[]'),
        ]);

        $this->imported++;
    }

    private function validateStatus($status)
    {
        $validStatuses = ['active', 'inactive', 'completed', 'on_hold', 'cancelled'];
        return in_array(strtolower($status), $validStatuses) ? strtolower($status) : 'active';
    }

    private function validatePriority($priority)
    {
        $validPriorities = ['low', 'medium', 'high', 'critical'];
        return in_array(strtolower($priority), $validPriorities) ? strtolower($priority) : 'medium';
    }

    private function parseDate($date)
    {
        if (empty($date)) return null;
        
        try {
            return \Carbon\Carbon::parse($date)->format('Y-m-d');
        } catch (\Exception $e) {
            return null;
        }
    }

    private function parseDecimal($value)
    {
        $cleaned = preg_replace('/[^\d.]/', '', $value);
        return is_numeric($cleaned) ? (float) $cleaned : 0;
    }

    private function parseJson($value)
    {
        if (is_array($value)) return $value;
        
        if (is_string($value) && !empty($value)) {
            $decoded = json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return $decoded;
            }
            
            return explode(',', $value);
        }
        
        return [];
    }

    public function rules(): array
    {
        return [
            'project_name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'status' => 'nullable|string',
            'priority' => 'nullable|string',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'budget' => 'nullable|numeric|min:0',
        ];
    }

    public function customValidationMessages()
    {
        return [
            'project_name.required' => 'Project name is required',
            'end_date.after_or_equal' => 'End date must be after or equal to start date',
            'budget.min' => 'Budget must be a positive number',
        ];
    }

    public function batchSize(): int
    {
        return 100;
    }

    public function chunkSize(): int
    {
        return 100;
    }

    public function getErrors()
    {
        return $this->errors;
    }

    public function getImportedCount()
    {
        return $this->imported;
    }
}