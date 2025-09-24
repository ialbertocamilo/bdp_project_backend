<?php

namespace App\Http\Controllers;

use App\Http\Traits\CacheableTrait;
use App\Models\Project;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardController extends Controller
{
    use CacheableTrait;

    public function getMetrics(Request $request)
    {
        $period = $request->get('period', '30'); // days
        
        $metrics = $this->remember("dashboard_metrics_{$period}", 300, function () use ($period) {
            $startDate = Carbon::now()->subDays($period);
            
            return [
                'overview' => $this->getOverviewMetrics($startDate),
                'projects' => $this->getProjectMetrics($startDate),
                'performance' => $this->getPerformanceMetrics($startDate),
                'financial' => $this->getFinancialMetrics($startDate),
                'timeline' => $this->getTimelineMetrics($startDate, $period)
            ];
        });

        return response()->json($metrics);
    }

    private function getOverviewMetrics($startDate)
    {
        $totalProjects = Project::count();
        $activeProjects = Project::where('status', 'active')->count();
        $completedProjects = Project::where('status', 'completed')->count();
        $newProjects = Project::where('created_at', '>=', $startDate)->count();
        $totalUsers = User::where('is_active', true)->count();

        return [
            'total_projects' => $totalProjects,
            'active_projects' => $activeProjects,
            'completed_projects' => $completedProjects,
            'new_projects' => $newProjects,
            'total_users' => $totalUsers,
            'completion_rate' => $totalProjects > 0 ? round(($completedProjects / $totalProjects) * 100, 2) : 0
        ];
    }

    private function getProjectMetrics($startDate)
    {
        $statusDistribution = Project::select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->get()
            ->pluck('count', 'status')
            ->toArray();

        $priorityDistribution = Project::select('priority', DB::raw('count(*) as count'))
            ->groupBy('priority')
            ->get()
            ->pluck('count', 'priority')
            ->toArray();

        $recentActivity = Project::with('user:id,name')
            ->where('updated_at', '>=', $startDate)
            ->orderBy('updated_at', 'desc')
            ->limit(10)
            ->get()
            ->map(function ($project) {
                return [
                    'id' => $project->id,
                    'name' => $project->name,
                    'status' => $project->status,
                    'user' => $project->user->name ?? 'Unknown',
                    'updated_at' => $project->updated_at->format('Y-m-d H:i:s')
                ];
            });

        return [
            'status_distribution' => $statusDistribution,
            'priority_distribution' => $priorityDistribution,
            'recent_activity' => $recentActivity
        ];
    }

    private function getPerformanceMetrics($startDate)
    {
        $overdue = Project::where('end_date', '<', Carbon::now())
            ->where('status', '!=', 'completed')
            ->count();

        $dueSoon = Project::whereBetween('end_date', [
            Carbon::now(),
            Carbon::now()->addDays(7)
        ])->where('status', '!=', 'completed')->count();

        $onTrack = Project::where('end_date', '>', Carbon::now()->addDays(7))
            ->where('status', 'active')
            ->count();

        return [
            'overdue_projects' => $overdue,
            'due_soon' => $dueSoon,
            'on_track' => $onTrack,
            'health_score' => $this->calculateHealthScore($overdue, $dueSoon, $onTrack)
        ];
    }

    private function getFinancialMetrics($startDate)
    {
        $totalBudget = Project::sum('budget') ?: 0;
        $activeBudget = Project::where('status', 'active')->sum('budget') ?: 0;
        $completedBudget = Project::where('status', 'completed')->sum('budget') ?: 0;
        $newBudget = Project::where('created_at', '>=', $startDate)->sum('budget') ?: 0;

        $budgetByStatus = Project::select('status', DB::raw('sum(budget) as total'))
            ->groupBy('status')
            ->get()
            ->pluck('total', 'status')
            ->toArray();

        return [
            'total_budget' => $totalBudget,
            'active_budget' => $activeBudget,
            'completed_budget' => $completedBudget,
            'new_budget' => $newBudget,
            'budget_utilization' => $totalBudget > 0 ? round(($activeBudget / $totalBudget) * 100, 2) : 0,
            'budget_by_status' => $budgetByStatus
        ];
    }

    private function getTimelineMetrics($startDate, $period)
    {
        $projectsCreated = [];
        $projectsCompleted = [];
        
        for ($i = $period - 1; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i)->format('Y-m-d');
            
            $created = Project::whereDate('created_at', $date)->count();
            $completed = Project::whereDate('updated_at', $date)
                ->where('status', 'completed')
                ->count();
            
            $projectsCreated[] = ['date' => $date, 'count' => $created];
            $projectsCompleted[] = ['date' => $date, 'count' => $completed];
        }

        return [
            'projects_created' => $projectsCreated,
            'projects_completed' => $projectsCompleted
        ];
    }

    private function calculateHealthScore($overdue, $dueSoon, $onTrack)
    {
        $total = $overdue + $dueSoon + $onTrack;
        
        if ($total === 0) return 100;
        
        $score = (($onTrack * 100) + ($dueSoon * 50) + ($overdue * 0)) / $total;
        
        return round($score, 2);
    }

    public function getProjectsByStatus()
    {
        $data = $this->remember('projects_by_status', 600, function () {
            return Project::select('status', DB::raw('count(*) as count'))
                ->groupBy('status')
                ->get();
        });

        return response()->json($data);
    }

    public function getBudgetAnalysis()
    {
        $data = $this->remember('budget_analysis', 600, function () {
            return [
                'by_status' => Project::select('status', DB::raw('sum(budget) as total'))
                    ->groupBy('status')
                    ->get(),
                'by_priority' => Project::select('priority', DB::raw('sum(budget) as total'))
                    ->groupBy('priority')
                    ->get(),
                'monthly_trend' => $this->getMonthlyBudgetTrend()
            ];
        });

        return response()->json($data);
    }

    private function getMonthlyBudgetTrend()
    {
        return Project::select(
            DB::raw('DATE_FORMAT(created_at, "%Y-%m") as month'),
            DB::raw('sum(budget) as total')
        )
        ->where('created_at', '>=', Carbon::now()->subMonths(12))
        ->groupBy('month')
        ->orderBy('month')
        ->get();
    }

    public function getUserActivity()
    {
        $data = $this->remember('user_activity', 300, function () {
            return User::select('users.id', 'users.name', 'users.last_login_at')
                ->selectRaw('count(projects.id) as project_count')
                ->leftJoin('projects', 'users.id', '=', 'projects.user_id')
                ->where('users.is_active', true)
                ->groupBy('users.id', 'users.name', 'users.last_login_at')
                ->orderBy('project_count', 'desc')
                ->limit(10)
                ->get();
        });

        return response()->json($data);
    }

    public function exportDashboard(Request $request)
    {
        $format = $request->get('format', 'json');
        $metrics = $this->getMetrics($request)->getData();

        if ($format === 'csv') {
            return $this->exportToCsv($metrics);
        }

        return response()->json($metrics);
    }

    private function exportToCsv($data)
    {
        $filename = 'dashboard_metrics_' . date('Y-m-d') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() use ($data) {
            $file = fopen('php://output', 'w');
            
            // Overview metrics
            fputcsv($file, ['Metric', 'Value']);
            fputcsv($file, ['Total Projects', $data->overview['total_projects']]);
            fputcsv($file, ['Active Projects', $data->overview['active_projects']]);
            fputcsv($file, ['Completed Projects', $data->overview['completed_projects']]);
            fputcsv($file, ['Completion Rate', $data->overview['completion_rate'] . '%']);
            
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}