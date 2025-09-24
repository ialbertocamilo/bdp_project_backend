<?php

namespace App\Http\Controllers;

use App\Models\TaskComment;
use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class TaskCommentController extends Controller
{
    /**
     * Obtener comentarios de una tarea
     */
    public function index($taskId): JsonResponse
    {
        try {
            $task = Task::findOrFail($taskId);
            
            $comments = TaskComment::with('user')
                ->where('task_id', $taskId)
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $comments,
                'message' => 'Comentarios obtenidos exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener los comentarios: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Crear un nuevo comentario
     */
    public function store(Request $request, $taskId): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'comment' => 'required|string',
                'is_internal' => 'boolean'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Datos de validación incorrectos',
                    'errors' => $validator->errors()
                ], 422);
            }

            $task = Task::findOrFail($taskId);

            $comment = TaskComment::create([
                'task_id' => $taskId,
                'user_id' => auth()->id(),
                'comment' => $request->comment,
                'is_internal' => $request->get('is_internal', false)
            ]);

            $comment->load('user');

            return response()->json([
                'success' => true,
                'data' => $comment,
                'message' => 'Comentario creado exitosamente'
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al crear el comentario: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Actualizar un comentario
     */
    public function update(Request $request, $taskId, $commentId): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'comment' => 'required|string',
                'is_internal' => 'boolean'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Datos de validación incorrectos',
                    'errors' => $validator->errors()
                ], 422);
            }

            $comment = TaskComment::where('task_id', $taskId)
                ->where('id', $commentId)
                ->where('user_id', auth()->id())
                ->firstOrFail();

            $comment->update([
                'comment' => $request->comment,
                'is_internal' => $request->get('is_internal', $comment->is_internal)
            ]);

            $comment->load('user');

            return response()->json([
                'success' => true,
                'data' => $comment,
                'message' => 'Comentario actualizado exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar el comentario: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Eliminar un comentario
     */
    public function destroy($taskId, $commentId): JsonResponse
    {
        try {
            $comment = TaskComment::where('task_id', $taskId)
                ->where('id', $commentId)
                ->where('user_id', auth()->id())
                ->firstOrFail();

            $comment->delete();

            return response()->json([
                'success' => true,
                'message' => 'Comentario eliminado exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar el comentario: ' . $e->getMessage()
            ], 500);
        }
    }
}