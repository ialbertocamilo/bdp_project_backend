<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProjectRequest;
use App\Models\Project;
use App\Models\ProjectData;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Str;
use function App\helpers\OkResponse;

class ProjectController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        $projects = Project::all();
        return Response::json(compact('projects'));
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
        $user          = $request->user();
        $data          = $request->validated();
        $project       = new Project($data);
        $project->uuid = $uuid;
        $project->name = 'bdp' . '-' . today()->format('d-m-y') . '-' . $uuid;

        if ($newProject = $user->projects()->save($project)) {
            $project_data = new ProjectData($data);
            $newProject->projectData()->save($project_data);
            return OkResponse($newProject);
        }


        return Response::json(["message" => 'Error.'], 402);
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($project)
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
     * @param int                      $id
     *
     * @return \Illuminate\Http\Response
     */
    public function update(ProjectRequest $request, $project)
    {
        $project       = Project::whereUuid($project)->first();
        $data          = $request->validated();
        $project_data  = new ProjectData($data);
        if ($project->projectData()->save($project_data)){
            return OkResponse($project_data);
        }

        return Response::json(["message" => 'Error.'], 402);
    }

    public function editContentProject($step_name, $substep_name, $content, $request)
    {
        if (array_key_exists($step_name, $content)) {
            if (array_key_exists($substep_name, $content[$step_name])) {
                $content[$step_name][$substep_name] = $request[$step_name][$substep_name];
            } else {
                $content[$step_name] = array_merge($content[$step_name], $request[$step_name]);
            }
        } else {
            $content = array_merge($content, $request);
        }

        return $content;
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

    public function getAllContents(string $uid){
        $projectData=Project::whereUuid($uid)->first()->projectData()->get();
        return OkResponse($projectData,'All contents listed');
    }


}
