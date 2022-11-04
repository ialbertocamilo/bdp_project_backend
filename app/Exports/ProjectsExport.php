<?php

namespace App\Exports;

use App\Models\Project;
use Maatwebsite\Excel\Concerns\FromView;
use Illuminate\Contracts\View\View;

class ProjectsExport implements FromView
{
    /**
    * @return \Illuminate\Support\Collection
    */

    public function view(): View
    {
        $projects = Project::get();
        $resultado = [];

        foreach ($projects as $value) {
            $resultado[] = [
                'DESCRIPCION'=> $value->description,
                'TIPO' => $value->projectType->name,
                'FASE'=> $this->getFase($value->projectData),
                'ORIGEN'=> $this->getOrigen($value->projectData),
                'FECHA_REGISTRO' => date_format($value->created_at,"d/m/Y H:i:s"),
                'DURACION' => $this->getDuracion($value->projectData),
                'PRESUPUESTO_USD' => $this->getCost($value->projectData),
            ];
        }

        return view('excel.projects', [
            'projects' => $resultado
        ]);
    }

    private function getFase($project_data)
    {
        $step_name = '';
        foreach ($project_data as $key => $value) {
            $step_name = $value->step_name;
        }

        return $step_name;
    }

    private function getOrigen($project_data)
    {
        foreach ($project_data as $key => $value) {
            if($value->step_name=='evaluacion' && $value->substep_name = 'registro'){
                if ($value->content['origin'] == 'a') {
                    return 'Persona natural';
                }
                if ($value->content['origin'] == 'b') {
                    return 'Persona jurídica';
                }
                if ($value->content['origin'] == 'c') {
                    return 'BDP';
                }
            }
        }
    }

    private function getDuracion($project_data)
    {
        foreach ($project_data as $key => $value) {
            if($value->step_name=='evaluacion' && $value->substep_name = 'registro'){
                return $value->content['duration'];
            }
        }
    }

    private function getCost($project_data)
    {
        foreach ($project_data as $key => $value) {
            if($value->step_name=='evaluacion' && $value->substep_name = 'registro'){
                return $value->content['total_cost'];
            }
        }
    }
}
