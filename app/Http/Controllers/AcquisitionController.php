<?php

namespace App\Http\Controllers;

use App\Models\EDT;
use App\Models\Acquisition;
use App\Models\Project;
use Illuminate\Http\Request;
use ArrayObject;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Str;
use function App\helpers\OkResponse;

class AcquisitionController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        $edt = Acquisition::all();
        return Response::json(compact('budget'));
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
      

        $acquisition = Acquisition::with('componentEDT')->whereHas('componentEDT', function ($q) use ($id) {
              $q->whereHas('project', function ($q) use ($id) {
                $q->where('uuid', $id);
              });
            })->get();
            
        $componentEDT = EDT::whereHas('project', function ($q) use ($id) {
            $q->where('uuid', $id);
          })->get();
            
            
        return Response::json(compact('acquisition', 'componentEDT'));
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
 
            
            $totalrows=$request->totalrows;
            for ($i = 1; $i <= $totalrows; $i++) {
                $edt = Acquisition::create([
                    'acquisition'=> $request['Adquisicion'.$i],
                    'modality' =>  $request['Modalidad'.$i],
                    'date_ini' => $request['Inicio'.$i],
                    'date_end' => $request['Fin'.$i],
                    'amount' => $request['Presupuesto USD'.$i],
                    'edt_id'=> $request['Componente EDT'.$i],
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
