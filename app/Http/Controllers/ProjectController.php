<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProjectRequest;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Validator;
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
        $user          = $request->user();
        $data          = $request->validated();
        $project       = new Project($data);
        $project->uuid = Str::uuid()->toString();
//        $project->content = ["gaaaa" => "aea"];

        if ($response=$user->projects()->save($project))
            return OkResponse($response);

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
        $project = Project::findOrFail($id);
        return Response::json(compact('project'));
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
    public function update(ProjectRequest $request, $id)
    {
        $project               = Project::findOrFail($id);
        $data                  = $request->validated();

        $content = $this->editContentProject($request->step_name,$request->substep_name,$project['content'],$request->content);

        return $content;

        $data['content'] = $content;

        if ($project->update($data))
            return OkResponse($project);

        return Response::json(["message" =>'Error.'],402);
    }

    public function editContentProject($step_name,$substep_name,$content,$request)
    {
        if (array_key_exists($step_name,$content)) {
            if(array_key_exists($substep_name,$content[$step_name])) {
                $content[$step_name][$substep_name] = $request[$step_name][$substep_name];
            } else {
                $content[$step_name] = array_merge($content[$step_name],$request[$step_name]);
            }
        }else {
            $content = array_merge($content,$request);
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



}
