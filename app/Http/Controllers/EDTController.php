<?php

namespace App\Http\Controllers;

use App\Models\EDT;
use App\Models\Project;
use Illuminate\Http\Request;
use ArrayObject;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Str;
use function App\helpers\OkResponse;

class EDTController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        $edt = EDT::all();
        return Response::json(compact('edt'));
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
        if (EDT::create($request))
            return Response::json(["message" => 'Saved succesfully.'],201);

        return Response::json(["message" =>'Error.'],402);
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     *
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $project       = Project::whereUuid($id)->first();
        $edt = EDT::where('project_id',$project->id)->get();
        return Response::json(compact('edt'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id
     *
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param int                      $id
     *
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $project)
    {
 
            $project       = Project::whereUuid($project)->first();
            $totalrows=$request->totalrows;
            for ($i = 1; $i <= $totalrows; $i++) {
                $edt = EDT::create([
                    'level'=> $request['Nivel EDT'.$i],
                    'type' =>  $request['Tipo'.$i],
                    'description' => $request['Descripcion'.$i],
                    'project_id'=> $project->id
                ]);
            }
            return Response::json(compact('request'));

        
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     *
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $edt    = EDT::find($id);
        $edt->delete();
        return Response::json(["message" =>'Eliminacion exitosa.'],201);
    }
}
