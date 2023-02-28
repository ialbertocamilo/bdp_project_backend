<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProjectRequest;
use App\Models\Project;
use App\Models\ProjectData;
use ArrayObject;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Str;
use function App\helpers\OkResponse;
use Illuminate\Http\Request;

class ProjectController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function __construct()
    {

    }


    public function index()
    {
        $projects = auth()->user()->projects()->with('user')->get();

        $details = ["open" => auth()->user()->projects()->whereEstado(1)->get()->count(), "close" => auth()->user()->projects()->whereEstado(0)->get()->count()];

        return Response::json(compact('projects', 'details'));
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
    public function store(ProjectRequest $request)
    {
        $uuid = Str::uuid()->toString();
        // Crea un registro en projects y crea un registro en project_data
        $user = auth()->user();
        $data = $request->validated();
        $project = new Project($data);
        $project->uuid = $uuid;
        $project->name = $request->name;

        if ($newProject = $user->projects()->save($project)) {
            $project_data = new ProjectData($data);
            $newProject->projectData()->save($project_data);
            $project_data = new ProjectData($data);
            $project_data->substep_name = 'actividades';
            $newProject->projectData()->save($project_data);
            return OkResponse($newProject);
        }
        return Response::json(["message" => 'Error.'], 401);
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($project): JsonResponse
    {
        $project = Project::whereUuid($project)->first();
        if ($project)
            return OkResponse($project, message: "Project exist.");
        return response()->json(['message' => "Project doesn't exist."], 400);
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
        $project = Project::findOrFail($id);
        return Response::json(compact('project'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param int $id
     *
     * @return \Illuminate\Http\Response
     */
    public function update(ProjectRequest $request,string $project)
    {
        try {
            $project = Project::whereUuid($project)->first();
            $data = $request->validated();
            $dataToUpdate = (object)$data['content'];
//            if ($request->substep_name == "registro") {
//                $projectData2 = $project->projectData()->whereStepName($request->step_name)->whereSubstepName('actividades')->first();
//                $project->description = $dataToUpdate->description;
//                $project->name = $dataToUpdate->project_name;
//                if ($projectData2) {// Actualizar actividades segun registro
//                    $newContent = ['content' => [
//                        "description" => $dataToUpdate->description,
//                        "origin" => $dataToUpdate->origin,
//                        "project_type" => $dataToUpdate->project_type,
//                        "project_sector" => $dataToUpdate->project_sector,
//                        "project_name" => $dataToUpdate->project_name,
//                        "economic_activity" => $dataToUpdate->economic_activity,
//                    ]];
//                    $project->projectData()->whereSubstepName('actividades')->update($newContent);
//                }
//                $project->update();
//            }
            $updateProjectData = $this->editContentProject($request->step_name, $request->substep_name, $project->projectData, $data);
            if (!$updateProjectData) {
                $project_data = new ProjectData($data);
                $project->projectData()->save($project_data);
            }
            return OkResponse($updateProjectData);

        } catch (\Throwable $th) {

            return Response::json(["message" => $th->getMessage()], 401);

        }
    }

    public function editContentProject($step_name, $substep_name, $projectData, $request):bool|object
    {
        foreach ($projectData as $data) {
            if ($data['step_name'] == $step_name && $data['substep_name'] == $substep_name) {
                $updateProjectData = ProjectData::findOrFail($data['id']);
                $updateProjectData->update($request);
                return $updateProjectData;
            }
        }
        return false;
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
        //
    }

    public function getAllContents(string $step, string $substep, string $uid)
    {

        $projectData = Project::whereUuid($uid)->first()->projectData()->where(function ($query) use ($step, $substep) {
            return $query->whereStepName($step)->whereSubstepName($substep);
        })->first();
        return OkResponse($projectData, 'Contents are listed');
    }

    public function editTableActivititesImplementation(Request $request, $uid)
    {
        $projectData = Project::whereUuid($uid)->first();

        foreach ($projectData->projectData as $data) {
            if ($data['step_name'] == 'implementacion' && $data['substep_name'] == 'actividades') {
                $updateProjectData = ProjectData::findOrFail($data['id']);
                $updateProjectData->content = $request->all();
                $updateProjectData->update();
                return $updateProjectData;
            }
        }
    }

    public function closeProject($uuid)
    {
        $project = Project::whereUuid($uuid)->first();
        $project->estado = 0;
        $project->update();
        return OkResponse($project, 'Closed successfully', 204);
    }


    public function getProjectStatus(string $uuid)
    {

        $project = Project::whereUuid($uuid)->first();
        $project_data = $project->projectData()->select('id', 'step_name', 'substep_name')->orderBy('id', 'desc')->first();
        $project_data->project_type = $project->projectType()->first()->slug;
        $project_data->project_type_name = $project->projectType()->first()->name;
        return OkResponse($project_data, 'get project status');
    }

}
