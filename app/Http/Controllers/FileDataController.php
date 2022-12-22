<?php

namespace App\Http\Controllers;

use App\Models\FileData;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use function App\helpers\OkResponse;

class FileDataController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        $request->validate([
            'file' => [
                'required',
            ],
            'project_name_field' => [
                'required',
                'string'
            ],
            'file_name' => ['required']

        ]);


        if ($request->multiple == "false") {
            $step         = $request->step;
            $sub_step     = $request->sub_step;
            $project_name = $request->project_name_field;

            $fileExist = Project::whereUuid($request->uuid)->first()->files()->where(function ($query) use ($step, $sub_step, $project_name) {
                return $query
                    ->whereStep($step)
                    ->whereSubStep($sub_step)
                    ->whereProjectNameField($project_name);
            })->get();
            if ($fileExist) {
                foreach ($fileExist as $value) {
                    Storage::disk('local')->delete($value->route);
                    $value->delete();
                }
            }
        }
        $file  = $request->file('file');
        $today = today()->format('d-m-y');
        $path  = $file->storeAs($today, Str::slug($file->getClientOriginalName()) . '(' . $today . '--' . time() . ').' . $file->getClientOriginalExtension());

        $fileData                     = new FileData($request->all());
        $fileData->uuid               = Str::uuid()->toString();
        $fileData->project_name_field = $request->project_name_field;
        $fileData->realname           = $request->file_name;
        $fileData->route              = $path;
        $fileData->size               = $file->getSize();
        $fileData->multiple           = $request->multiple === "true";
        $response                     = Project::whereUuid($request->uuid)->first()->files()->save($fileData);
        return OkResponse($response);
    }

    /**
     * Display the specified resource.
     *
     * @param \App\Models\FileData $fileData
     *
     * @return \Symfony\Component\HttpFoundation\StreamedResponse
     */
    public function show(string $file_datum)
    {
        $file = FileData::whereUuid($file_datum)->first();

        if ($file) {
            if (Storage::exists($file->route)) {
                header('Content-Type: application/octet-stream');
                return Storage::download($file->route);
//            return json_encode(Storage::disk('local')->size($file->route));
            }
        }
        abort(404, 'Resource doesn`t exist');
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param \App\Models\FileData $fileData
     *
     * @return \Illuminate\Http\Response
     */
    public function edit(FileData $file_datum)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\FileData     $fileData
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, FileData $file_datum)
    {
//        return OkResponse($file_datum);
        $res = $file_datum->update($request->all());
        return OkResponse($res);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param \App\Models\FileData $fileData
     *
     * @return \Illuminate\Http\Response
     */
    public function destroy(string $file_datum)
    {

        $data = FileData::whereUuid($file_datum)->first();

        Storage::disk('local')->delete($data->route);
        $data->delete();
        return response()->json(["message" => "File has been deleted.", "uuid" => $file_datum]);
    }

    public function downloadPublicFile(string $fileName)
    {


//        $route=Storage::disk('public')->($fileName);

        $file = $fileName;
//        return $file;
//        header('Content-Type: application/octet-stream');
        return Storage::disk('public')->download('templates/' . $file);
//        return storage_path('app/public/').$fileName;
    }
}
