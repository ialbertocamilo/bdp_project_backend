<?php

namespace App\Http\Controllers;

use App\Imports\ProjectsImport;
use App\Models\ImportLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ImportController extends Controller
{
    public function importProjects(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,xlsx,xls|max:10240'
        ]);

        $file = $request->file('file');
        $filePath = Storage::disk('local')->putFile('imports', $file);

        try {
            $import = new ProjectsImport(auth()->id());
            $import->import(Storage::disk('local')->path($filePath));

            $results = $import->getResults();

            Storage::disk('local')->delete($filePath);

            return response()->json($results, 200);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    public function getTemplate()
    {
        $template = [];
        $template[] = ['name', 'description'];
        $template[] = ['Sample Project 1', 'Sample Description'];
        $template[] = ['Sample Project 2', 'Another Description'];

        $filename = 'template.csv';
        $path = Storage::disk('local')->path('template.csv');

        $file = fopen($path, 'w');
        foreach ($template as $row) {
            fputcsv($file, $row);
        }
        fclose($file);

        return response()->download($path, $filename)->deleteFileAfterSend(true);
    }

    public function validateFile(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,xlsx,xls'
        ]);

        $file = $request->file('file');
        $filePath = Storage::disk('local')->putFile('imports', $file);

        try {
            $import = new ProjectsImport(auth()->id());
            $import->import(Storage::disk('local')->path($filePath));

            $results = $import->getResults();
            Storage::disk('local')->delete($filePath);

            return response()->json([
                'valid' => count($results['errors']) === 0,
                'imported' => $results['imported'],
                'failed' => $results['failed'],
                'errors' => $results['errors']
            ]);
        } catch (\Exception $e) {
            Storage::disk('local')->delete($filePath);
            return response()->json(['error' => $e->getMessage()], 400);
        }
    }

    public function history()
    {
        $logs = ImportLog::where('user_id', auth()->id())
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json($logs);
    }
}
