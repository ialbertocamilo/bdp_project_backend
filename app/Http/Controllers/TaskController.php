<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class TaskController extends Controller
{
    /**
     * Obtener todas las tareas con filtros opcionales
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Task::with(['project', 'assignedUser', 'createdBy', 'dependencies']);

            // Filtros
            if ($request->has('project_id')) {
                $query->where('project_id', $request->project_id);
            }

            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            if ($request->has('priority')) {
                $query->where('priority', $request->priority);
            }

            if ($request->has('assigned_to')) {
                $query->where('assigned_to', $request->assigned_to);
            }

            if ($request->has('search')) {
                $query->where(function ($q) use ($request) {
                    $q->where('name', 'like', '%' . $request->search . '%')
                      ->orWhere('description', 'like', '%' . $request->search . '%');
                });
            }

            // Ordenamiento
            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');
            $query->orderBy($sortBy, $sortOrder);

            // Paginación
            $perPage = $request->get('per_page', 15);
            $tasks = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $tasks,
                'message' => 'Tareas obtenidas exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener las tareas: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Crear una nueva tarea
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'project_id' => 'required|exists:projects,id',
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'status' => 'required|in:pending,in_progress,completed,cancelled',
                'priority' => 'required|in:low,medium,high,critical',
                'assigned_to' => 'nullable|exists:users,id',
                'start_date' => 'required|date',
                'end_date' => 'required|date|after_or_equal:start_date',
                'estimated_hours' => 'nullable|numeric|min:0',
                'dependencies' => 'nullable|array',
                'dependencies.*' => 'exists:tasks,id'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Datos de validación incorrectos',
                    'errors' => $validator->errors()
                ], 422);
            }

            DB::beginTransaction();

            $task = Task::create([
                'project_id' => $request->project_id,
                'name' => $request->name,
                'description' => $request->description,
                'status' => $request->status,
                'priority' => $request->priority,
                'assigned_to' => $request->assigned_to,
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
                'estimated_hours' => $request->estimated_hours,
                'created_by' => auth()->id()
            ]);

            // Agregar dependencias si existen
            if ($request->has('dependencies') && is_array($request->dependencies)) {
                $task->dependencies()->attach($request->dependencies);
            }

            DB::commit();

            $task->load(['project', 'assignedUser', 'createdBy', 'dependencies']);

            return response()->json([
                'success' => true,
                'data' => $task,
                'message' => 'Tarea creada exitosamente'
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al crear la tarea: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener una tarea específica
     */
    public function show($id): JsonResponse
    {
        try {
            $task = Task::with([
                'project', 
                'assignedUser', 
                'createdBy', 
                'dependencies',
                'comments.user',
                'attachments.user'
            ])->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => $task,
                'message' => 'Tarea obtenida exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener la tarea: ' . $e->getMessage()
            ], 404);
        }
    }

    /**
     * Actualizar una tarea
     */
    public function update(Request $request, $id): JsonResponse
    {
        try {
            $task = Task::findOrFail($id);

            $validator = Validator::make($request->all(), [
                'name' => 'sometimes|required|string|max:255',
                'description' => 'nullable|string',
                'status' => 'sometimes|required|in:pending,in_progress,completed,cancelled',
                'priority' => 'sometimes|required|in:low,medium,high,critical',
                'assigned_to' => 'nullable|exists:users,id',
                'start_date' => 'sometimes|required|date',
                'end_date' => 'sometimes|required|date|after_or_equal:start_date',
                'estimated_hours' => 'nullable|numeric|min:0',
                'actual_hours' => 'nullable|numeric|min:0',
                'progress_percentage' => 'nullable|integer|min:0|max:100',
                'dependencies' => 'nullable|array',
                'dependencies.*' => 'exists:tasks,id'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Datos de validación incorrectos',
                    'errors' => $validator->errors()
                ], 422);
            }

            DB::beginTransaction();

            $task->update($request->only([
                'name', 'description', 'status', 'priority', 'assigned_to',
                'start_date', 'end_date', 'estimated_hours', 'actual_hours', 'progress_percentage'
            ]));

            // Actualizar dependencias si se proporcionan
            if ($request->has('dependencies')) {
                $task->dependencies()->sync($request->dependencies ?? []);
            }

            // Actualizar fecha de finalización si se marca como completada
            if ($request->status === 'completed' && !$task->completed_at) {
                $task->update(['completed_at' => now()]);
            }

            DB::commit();

            $task->load(['project', 'assignedUser', 'createdBy', 'dependencies']);

            return response()->json([
                'success' => true,
                'data' => $task,
                'message' => 'Tarea actualizada exitosamente'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar la tarea: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Eliminar una tarea
     */
    public function destroy($id): JsonResponse
    {
        try {
            $task = Task::findOrFail($id);
            
            // Verificar si la tarea tiene dependencias
            if ($task->dependentTasks()->count() > 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se puede eliminar la tarea porque otras tareas dependen de ella'
                ], 422);
            }

            $task->delete();

            return response()->json([
                'success' => true,
                'message' => 'Tarea eliminada exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar la tarea: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener tareas para el diagrama de Gantt
     */
    public function ganttData(Request $request): JsonResponse
    {
        try {
            $projectId = $request->get('project_id');
            
            $query = Task::with(['dependencies', 'assignedUser'])
                ->select([
                    'id',
                    'name',
                    'start_date',
                    'end_date',
                    'progress_percentage',
                    'status',
                    'priority',
                    'assigned_to',
                    'project_id'
                ]);

            if ($projectId) {
                $query->where('project_id', $projectId);
            }

            $tasks = $query->get();

            // Formatear datos para el componente Gantt
            $ganttData = $tasks->map(function ($task) {
                return [
                    'id' => $task->id,
                    'text' => $task->name,
                    'start_date' => $task->start_date->format('Y-m-d'),
                    'end_date' => $task->end_date->format('Y-m-d'),
                    'progress' => $task->progress_percentage / 100,
                    'priority' => $task->priority,
                    'status' => $task->status,
                    'assigned_to' => $task->assignedUser ? $task->assignedUser->name : null,
                    'dependencies' => $task->dependencies->pluck('id')->toArray()
                ];
            });

            return response()->json([
                'success' => true,
                'data' => $ganttData,
                'message' => 'Datos del Gantt obtenidos exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener datos del Gantt: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Actualizar el progreso de una tarea
     */
    public function updateProgress(Request $request, $id): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'progress' => 'required|integer|min:0|max:100'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Progreso inválido',
                    'errors' => $validator->errors()
                ], 422);
            }

            $task = Task::findOrFail($id);
            $task->update(['progress_percentage' => $request->progress]);

            // Si el progreso es 100%, marcar como completada
            if ($request->progress == 100) {
                $task->update([
                    'status' => 'completed',
                    'completed_at' => now()
                ]);
            }

            return response()->json([
                'success' => true,
                'data' => $task,
                'message' => 'Progreso actualizado exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar el progreso: ' . $e->getMessage()
            ], 500);
        }
    }
}