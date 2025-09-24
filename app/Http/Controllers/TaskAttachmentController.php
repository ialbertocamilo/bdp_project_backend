<?php

namespace App\Http\Controllers;

use App\Models\TaskAttachment;
use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class TaskAttachmentController extends Controller
{
    /**
     * Obtener archivos adjuntos de una tarea
     */
    public function index($taskId): JsonResponse
    {
        try {
            $task = Task::findOrFail($taskId);
            
            $attachments = TaskAttachment::with('user')
                ->where('task_id', $taskId)
                ->orderBy('created_at', 'desc')
                ->get();

            return response()->json([
                'success' => true,
                'data' => $attachments,
                'message' => 'Archivos adjuntos obtenidos exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener los archivos adjuntos: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Subir un archivo adjunto
     */
    public function store(Request $request, $taskId): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'file' => 'required|file|max:10240', // 10MB máximo
                'description' => 'nullable|string|max:255'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Datos de validación incorrectos',
                    'errors' => $validator->errors()
                ], 422);
            }

            $task = Task::findOrFail($taskId);
            $file = $request->file('file');

            // Generar nombre único para el archivo
            $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
            $filePath = 'task-attachments/' . $taskId . '/' . $filename;

            // Subir archivo
            $path = $file->storeAs('task-attachments/' . $taskId, $filename, 'public');

            $attachment = TaskAttachment::create([
                'task_id' => $taskId,
                'user_id' => auth()->id(),
                'filename' => $filename,
                'original_filename' => $file->getClientOriginalName(),
                'file_path' => $path,
                'file_size' => $file->getSize(),
                'mime_type' => $file->getMimeType(),
                'description' => $request->description
            ]);

            $attachment->load('user');

            return response()->json([
                'success' => true,
                'data' => $attachment,
                'message' => 'Archivo subido exitosamente'
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al subir el archivo: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Descargar un archivo adjunto
     */
    public function download($taskId, $attachmentId)
    {
        try {
            $attachment = TaskAttachment::where('task_id', $taskId)
                ->where('id', $attachmentId)
                ->firstOrFail();

            if (!Storage::disk('public')->exists($attachment->file_path)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Archivo no encontrado'
                ], 404);
            }

            return Storage::disk('public')->download(
                $attachment->file_path,
                $attachment->original_filename
            );

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al descargar el archivo: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Actualizar descripción de un archivo adjunto
     */
    public function update(Request $request, $taskId, $attachmentId): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'description' => 'nullable|string|max:255'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Datos de validación incorrectos',
                    'errors' => $validator->errors()
                ], 422);
            }

            $attachment = TaskAttachment::where('task_id', $taskId)
                ->where('id', $attachmentId)
                ->where('user_id', auth()->id())
                ->firstOrFail();

            $attachment->update([
                'description' => $request->description
            ]);

            $attachment->load('user');

            return response()->json([
                'success' => true,
                'data' => $attachment,
                'message' => 'Descripción actualizada exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar la descripción: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Eliminar un archivo adjunto
     */
    public function destroy($taskId, $attachmentId): JsonResponse
    {
        try {
            $attachment = TaskAttachment::where('task_id', $taskId)
                ->where('id', $attachmentId)
                ->where('user_id', auth()->id())
                ->firstOrFail();

            // Eliminar archivo físico
            if (Storage::disk('public')->exists($attachment->file_path)) {
                Storage::disk('public')->delete($attachment->file_path);
            }

            $attachment->delete();

            return response()->json([
                'success' => true,
                'message' => 'Archivo eliminado exitosamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar el archivo: ' . $e->getMessage()
            ], 500);
        }
    }
}