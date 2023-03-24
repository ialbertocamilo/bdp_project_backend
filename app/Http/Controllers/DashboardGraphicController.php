<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ProjectData;
use App\Models\ProjectType;
use DateTime;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardGraphicController extends Controller
{
    public $meses;
    public $mesesPos;

    public function __construct()
    {
        $this->meses = [
            '01' => 'Enero',
            '02' => 'Febrero',
            '03' => 'Marzo',
            '04' => 'Abril',
            '05' => 'Mayo',
            '06' => 'Junio',
            '07' => 'Julio',
            '08' => 'Agosto',
            '09' => 'Septiembre',
            '10' => 'Octubre',
            '11' => 'Noviembre',
            '12' => 'Diciembre'
        ];

        for ($i=3; $i >= 0 ; $i--)
        {
            $this->mesesPos[] = [
                'name' => $this->meses[date('m', strtotime("-$i month"))],
                'y' => 0,
                'drilldown' => $this->meses[date('m', strtotime("-$i month"))],
                'month' => date('m', strtotime("-$i month")),
            ];
        }
    }

    public function getTotalesProyectos()
    {

        $fvc=ProjectData::whereHas('project.user', function ($query) {
            $query->where('id', auth()->id());
        })
            ->where('step_name', 'implementacion')
            ->where('substep_name', 'actividades')
            ->select('id','step_name','substep_name','content')
            ->get();
        $desa =ProjectData::whereHas('project.user', function ($query) {
            $query->where('id', auth()->id());
        })
            ->where('step_name', 'ejecucion')
            ->where('substep_name', 'actividades')
            ->select('id','step_name','substep_name','content')
            ->get();


        $fvcRetraso = 0;
        $fvcPorVencer = 0;
//        $fvcTotal = auth()->user()->projects()->whereProjectTypeId(ProjectType::FVC)->get()->count();
        $fvcTotal = 0;

        foreach ($fvc as $key => $value) {
            $table =@$value->content['table'];
            if($table) {
                foreach ($table as $key1 => $value1) {
                    $fvcTotal++;
                    $hoy = new DateTime(date('Y-m-d'));
                    $fechaFin = new DateTime($value1['dateEnd']);
                    $diff = $hoy->diff($fechaFin);

                    if ($diff->invert) {
                        $fvcRetraso++;
                    }else if ($diff->days >= 0 && $diff->days <= 3 ) {
                        $fvcPorVencer++;
                    }

                }
            }
        }

        $desaRetraso = 0;
        $desaPorVencer = 0;
//        $desaTotal =  auth()->user()->projects()->whereProjectTypeId(ProjectType::DESA)->get()->count();
        $desaTotal =  0;

        foreach ($desa as $key => $value) {
            $table = @$value->content['table'];
            if($table) {
                foreach ($table as $key1 => $value1) {
                    $desaTotal++;
                    $hoy = new DateTime(date('Y-m-d'));
                    $fechaFin = new DateTime($value1['dateEnd']);
                    $diff = $hoy->diff($fechaFin);

                    if ($diff->invert) {
                        $desaRetraso++;
                    }else if ($diff->days >= 0 && $diff->days <= 3 ) {
                        $desaPorVencer++;
                    }

                }
            }
        }



        $totalesProyectos = [
            'retraso' => [
                'fvc' => $fvcRetraso,
                'desa' => $desaRetraso
            ],
            'por_vencer' => [
                'fvc' => $fvcPorVencer,
                'desa' => $desaPorVencer
            ],
            'cartera'=> [
                'fvc' => $fvcTotal,
                'desa' => $desaTotal
            ],
        ];

        return response()->json($totalesProyectos);
    }

    public function getFlujoProyectosTotalesFvc()
    {

        $mesesPos = [];

        $fvc=ProjectData::whereHas('project.user', function ($query) {
            $query->where('id', auth()->id());
        })
            ->where('step_name', 'implementacion')
            ->where('substep_name', 'actividades')
            ->select('id','step_name','substep_name','content')
        ->get();

        for ($i=3; $i >= 0 ; $i--)
        {
            $mesesPos[] = [
                'name' => $this->meses[date('m', strtotime("-$i month"))],
                'y' => 0,
                'drilldown' => $this->meses[date('m', strtotime("-$i month"))],
                'month' => date('m', strtotime("-$i month")),
            ];
        }

        foreach ($fvc as $key => $value) {
                $table = @$value->content['table'];
                if ($table) {
                    foreach ($table as $key1 => $value1) {
                        $fechaFin = $value1['dateEnd'];
                        $color = "#1D4ED8";
                        $activity=$value1['activity'];
                        $mesesPos = $this->contarPorMeses($mesesPos, $fechaFin, $color,$activity);
                    }
                }
        }

        return $mesesPos;

    }

    public function getFlujoProyectosTotalesDesa()
    {

        $desa = ProjectData::whereHas('project.user', function ($query) {
            $query->where('id', auth()->id());
        })
            ->where('step_name', 'ejecucion')
            ->where('substep_name', 'actividades')
            ->select('id','step_name','substep_name','content')
            ->get();

        $mesesPos = [];

        for ($i=3; $i >= 0 ; $i--)
        {
            $mesesPos[] = [
                'name' => $this->meses[date('m', strtotime("-$i month"))],
                'y' => 0,
                'drilldown' => $this->meses[date('m', strtotime("-$i month"))],
                'month' => date('m', strtotime("-$i month")),
            ];
        }

        foreach ($desa as $key => $value) {
                $table = @$value->content['table'];
                if ($table) {
                    foreach ($table as $key1 => $value1) {
                        $fechaFin = $value1['dateEnd'];
                        $color = "#1D4ED8";
                        $activity=$value1['activity'];
                        $mesesPos = $this->contarPorMeses($mesesPos, $fechaFin, $color,$activity);
                    }
                }
        }

        return $mesesPos;

    }

    public function getFlujoProyectosPorVencerDesa()
    {

        $desa =   ProjectData::whereHas('project.user', function ($query) {
            $query->where('id', auth()->id());
        })
            ->where('step_name', 'ejecucion')
            ->where('substep_name', 'actividades')
            ->select('id','step_name','substep_name','content')
            ->get();

        $mesesPos = [];

        for ($i=3; $i >= 0 ; $i--)
        {
            $mesesPos[] = [
                'name' => $this->meses[date('m', strtotime("-$i month"))],
                'y' => 0,
                'drilldown' => $this->meses[date('m', strtotime("-$i month"))],
                'month' => date('m', strtotime("-$i month")),
            ];
        }

        foreach ($desa as $key => $value) {
            $table = @$value->content['table'];
            if($table) {
                foreach ($table as $key1 => $value1) {
                    $fechaFin = $value1['dateEnd'];
                    $color = "#B91C1C";
                    $activity=$value1['activity'];
                    $mesesPos = $this->contarPorMesesPorVencer($mesesPos, $fechaFin, $color,$activity);
                }
            }
        }

        return $mesesPos;

    }
    public function getFlujoProyectosPorVencerFvc()
    {

        $fvc =  ProjectData::whereHas('project.user', function ($query) {
            $query->where('id', auth()->id());
        })
            ->where('step_name', 'implementacion')
            ->where('substep_name', 'actividades')
            ->select('id','step_name','substep_name','content')
            ->get();

        $mesesPos = [];

        for ($i=3; $i >= 0 ; $i--)
        {
            $mesesPos[] = [
                'name' => $this->meses[date('m', strtotime("-$i month"))],
                'y' => 0,
                'drilldown' => $this->meses[date('m', strtotime("-$i month"))],
                'month' => date('m', strtotime("-$i month")),
            ];
        }


        foreach ($fvc as $key => $value) {
            $table = @$value->content['table'];
            if($table) {
                foreach ($table as $key1 => $value1) {
                    $fechaFin = $value1['dateEnd'];
                    $color = "#B91C1C";
                    $activity=$value1['activity'];
                    $mesesPos = $this->contarPorMesesPorVencer($mesesPos,$fechaFin,$color,$activity);
                }
            }
        }

        return $mesesPos;

    }


    private function contarPorMeses($meses, $fechaFin,$color,$activity="")
    {
        $fechaComoEntero = strtotime($fechaFin);
        $mes = date("m", $fechaComoEntero);
        foreach ($meses as $key => $value) {
            if($value['month'] == $mes){
                $meses[$key]["y"] = $meses[$key]["y"] + 1;
                $meses[$key]["color"] = $color;
            }
        }

        return $meses;

    }
    private function contarPorMesesPorVencer($meses, $fechaFin,$color,$activity='')
    {
        $fechaComoEntero = strtotime($fechaFin);
        $mes = date("m", $fechaComoEntero);
        $fechaFinal = new DateTime($fechaFin);
        $hoy = new DateTime(date('Y-m-d'));
        foreach ($meses as $key => $value) {
            if ($value['month'] == $mes) {
                $diff = $hoy->diff($fechaFinal);

                if (!$diff->invert && $diff->days >= 0 && $diff->days <= 3 ) {
                    $meses[$key]['y'] = $meses[$key]["y"] + 1;
                }
                $meses[$key]['color'] = $color;
            }
        }

        return $meses;

    }


}
