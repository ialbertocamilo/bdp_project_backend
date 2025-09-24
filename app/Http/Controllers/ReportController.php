<?php

namespace App\Http\Controllers;

use App\Http\Traits\CacheableTrait;
use App\Models\Project;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\Style\Font;

class ReportController extends Controller
{
    use CacheableTrait;

    public function generateProjectReport(Request $request, $projectId)
    {
        $project = Project::with(['user', 'projectData'])->findOrFail($projectId);
        $format = $request->get('format', 'json');

        $reportData = $this->compileProjectData($project);

        switch ($format) {
            case 'docx':
                return $this->generateDocxReport($reportData);
            case 'pdf':
                return $this->generatePdfReport($reportData);
            default:
                return response()->json($reportData);
        }
    }

    public function generateSummaryReport(Request $request)
    {
        $period = $request->get('period', '30');
        $format = $request->get('format', 'json');
        
        $reportData = $this->remember("summary_report_{$period}", 300, function () use ($period) {
            return $this->compileSummaryData($period);
        });

        switch ($format) {
            case 'docx':
                return $this->generateSummaryDocxReport($reportData);
            case 'pdf':
                return $this->generateSummaryPdfReport($reportData);
            default:
                return response()->json($reportData);
        }
    }

    private function compileProjectData($project)
    {
        $projectData = $project->projectData->first();
        
        return [
            'project' => [
                'name' => $project->name,
                'description' => $project->description,
                'status' => $project->status,
                'priority' => $project->priority,
                'budget' => $project->budget,
                'start_date' => $project->start_date,
                'end_date' => $project->end_date,
                'created_at' => $project->created_at,
                'updated_at' => $project->updated_at
            ],
            'project_manager' => [
                'name' => $project->user->name ?? 'N/A',
                'email' => $project->user->email ?? 'N/A'
            ],
            'details' => $projectData ? [
                'stakeholders' => $projectData->stakeholders ?? [],
                'objectives' => $projectData->objectives ?? [],
                'scope' => $projectData->scope ?? '',
                'assumptions' => $projectData->assumptions ?? [],
                'constraints' => $projectData->constraints ?? [],
                'risks' => $projectData->risks ?? [],
                'deliverables' => $projectData->deliverables ?? [],
                'milestones' => $projectData->milestones ?? [],
                'resources' => $projectData->resources ?? [],
                'communication_plan' => $projectData->communication_plan ?? []
            ] : [],
            'metadata' => [
                'generated_at' => now()->format('Y-m-d H:i:s'),
                'generated_by' => auth()->user()->name ?? 'System'
            ]
        ];
    }

    private function compileSummaryData($period)
    {
        $startDate = now()->subDays($period);
        
        return [
            'period' => [
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => now()->format('Y-m-d'),
                'days' => $period
            ],
            'summary' => [
                'total_projects' => Project::count(),
                'active_projects' => Project::where('status', 'active')->count(),
                'completed_projects' => Project::where('status', 'completed')->count(),
                'overdue_projects' => Project::where('end_date', '<', now())
                    ->where('status', '!=', 'completed')->count(),
                'total_budget' => Project::sum('budget'),
                'active_budget' => Project::where('status', 'active')->sum('budget')
            ],
            'projects' => Project::with(['user:id,name'])
                ->where('updated_at', '>=', $startDate)
                ->orderBy('updated_at', 'desc')
                ->get()
                ->map(function ($project) {
                    return [
                        'name' => $project->name,
                        'status' => $project->status,
                        'priority' => $project->priority,
                        'budget' => $project->budget,
                        'manager' => $project->user->name ?? 'N/A',
                        'end_date' => $project->end_date,
                        'updated_at' => $project->updated_at->format('Y-m-d H:i:s')
                    ];
                }),
            'users' => User::withCount('projects')
                ->where('is_active', true)
                ->orderBy('projects_count', 'desc')
                ->get()
                ->map(function ($user) {
                    return [
                        'name' => $user->name,
                        'email' => $user->email,
                        'projects_count' => $user->projects_count,
                        'last_login' => $user->last_login_at
                    ];
                }),
            'metadata' => [
                'generated_at' => now()->format('Y-m-d H:i:s'),
                'generated_by' => auth()->user()->name ?? 'System'
            ]
        ];
    }

    private function generateDocxReport($data)
    {
        $phpWord = new PhpWord();
        $section = $phpWord->addSection();

        // Title
        $section->addTitle('Reporte de Proyecto - ' . $data['project']['name'], 1);
        
        // Project Information
        $section->addTitle('Información del Proyecto', 2);
        $tableStyle = ['borderSize' => 6, 'borderColor' => '999999'];
        $table = $section->addTable($tableStyle);
        
        $table->addRow();
        $table->addCell(3000)->addText('Nombre:');
        $table->addCell(6000)->addText($data['project']['name']);
        
        $table->addRow();
        $table->addCell(3000)->addText('Estado:');
        $table->addCell(6000)->addText(ucfirst($data['project']['status']));
        
        $table->addRow();
        $table->addCell(3000)->addText('Prioridad:');
        $table->addCell(6000)->addText(ucfirst($data['project']['priority']));
        
        $table->addRow();
        $table->addCell(3000)->addText('Presupuesto:');
        $table->addCell(6000)->addText('$' . number_format($data['project']['budget'], 2));
        
        $table->addRow();
        $table->addCell(3000)->addText('Fecha Inicio:');
        $table->addCell(6000)->addText($data['project']['start_date'] ?? 'N/A');
        
        $table->addRow();
        $table->addCell(3000)->addText('Fecha Fin:');
        $table->addCell(6000)->addText($data['project']['end_date'] ?? 'N/A');

        // Project Manager
        $section->addTitle('Responsable del Proyecto', 2);
        $section->addText('Nombre: ' . $data['project_manager']['name']);
        $section->addText('Email: ' . $data['project_manager']['email']);

        // Project Details
        if (!empty($data['details'])) {
            $section->addTitle('Detalles del Proyecto', 2);
            
            if (!empty($data['details']['objectives'])) {
                $section->addTitle('Objetivos', 3);
                foreach ($data['details']['objectives'] as $objective) {
                    $section->addListItem($objective);
                }
            }
            
            if (!empty($data['details']['deliverables'])) {
                $section->addTitle('Entregables', 3);
                foreach ($data['details']['deliverables'] as $deliverable) {
                    $section->addListItem($deliverable);
                }
            }
            
            if (!empty($data['details']['risks'])) {
                $section->addTitle('Riesgos', 3);
                foreach ($data['details']['risks'] as $risk) {
                    $section->addListItem($risk);
                }
            }
        }

        // Footer
        $section->addTextBreak(2);
        $section->addText('Generado el: ' . $data['metadata']['generated_at']);
        $section->addText('Generado por: ' . $data['metadata']['generated_by']);

        $filename = 'proyecto_' . $data['project']['name'] . '_' . date('Y-m-d') . '.docx';
        $tempFile = storage_path('app/temp/' . $filename);
        
        $writer = IOFactory::createWriter($phpWord, 'Word2007');
        $writer->save($tempFile);

        return response()->download($tempFile)->deleteFileAfterSend(true);
    }

    private function generateSummaryDocxReport($data)
    {
        $phpWord = new PhpWord();
        $section = $phpWord->addSection();

        // Title
        $section->addTitle('Reporte Resumen de Proyectos', 1);
        $section->addText('Período: ' . $data['period']['start_date'] . ' al ' . $data['period']['end_date']);
        
        // Summary
        $section->addTitle('Resumen Ejecutivo', 2);
        $tableStyle = ['borderSize' => 6, 'borderColor' => '999999'];
        $table = $section->addTable($tableStyle);
        
        $table->addRow();
        $table->addCell(4000)->addText('Total de Proyectos:');
        $table->addCell(2000)->addText($data['summary']['total_projects']);
        
        $table->addRow();
        $table->addCell(4000)->addText('Proyectos Activos:');
        $table->addCell(2000)->addText($data['summary']['active_projects']);
        
        $table->addRow();
        $table->addCell(4000)->addText('Proyectos Completados:');
        $table->addCell(2000)->addText($data['summary']['completed_projects']);
        
        $table->addRow();
        $table->addCell(4000)->addText('Proyectos Vencidos:');
        $table->addCell(2000)->addText($data['summary']['overdue_projects']);
        
        $table->addRow();
        $table->addCell(4000)->addText('Presupuesto Total:');
        $table->addCell(2000)->addText('$' . number_format($data['summary']['total_budget'], 2));

        // Projects List
        $section->addTitle('Proyectos Recientes', 2);
        $projectTable = $section->addTable($tableStyle);
        
        $projectTable->addRow();
        $projectTable->addCell(2000)->addText('Nombre');
        $projectTable->addCell(1500)->addText('Estado');
        $projectTable->addCell(1500)->addText('Prioridad');
        $projectTable->addCell(2000)->addText('Responsable');
        
        foreach ($data['projects'] as $project) {
            $projectTable->addRow();
            $projectTable->addCell(2000)->addText($project['name']);
            $projectTable->addCell(1500)->addText(ucfirst($project['status']));
            $projectTable->addCell(1500)->addText(ucfirst($project['priority']));
            $projectTable->addCell(2000)->addText($project['manager']);
        }

        // Footer
        $section->addTextBreak(2);
        $section->addText('Generado el: ' . $data['metadata']['generated_at']);
        $section->addText('Generado por: ' . $data['metadata']['generated_by']);

        $filename = 'resumen_proyectos_' . date('Y-m-d') . '.docx';
        $tempFile = storage_path('app/temp/' . $filename);
        
        $writer = IOFactory::createWriter($phpWord, 'Word2007');
        $writer->save($tempFile);

        return response()->download($tempFile)->deleteFileAfterSend(true);
    }

    private function generatePdfReport($data)
    {
        // Para futuras implementaciones con DomPDF o similar
        return response()->json(['message' => 'PDF generation not implemented yet']);
    }

    private function generateSummaryPdfReport($data)
    {
        // Para futuras implementaciones con DomPDF o similar
        return response()->json(['message' => 'PDF generation not implemented yet']);
    }

    public function getAvailableReports()
    {
        return response()->json([
            'project_reports' => [
                'individual' => 'Reporte Individual de Proyecto',
                'summary' => 'Reporte Resumen de Proyectos',
                'financial' => 'Reporte Financiero',
                'performance' => 'Reporte de Rendimiento'
            ],
            'formats' => ['json', 'docx', 'pdf'],
            'periods' => ['7', '30', '90', '365']
        ]);
    }
}