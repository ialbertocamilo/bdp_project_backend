<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ProjectData;
use DateTime;
use Illuminate\Http\Request;

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

        for ($i=0; $i < 4; $i++)
        {
            $this->mesesPos[] = [
                'name' => $this->meses[date('m', strtotime("-$i month"))],
                'y' => 0,
                'drilldown' => $this->meses[date('m', strtotime("-$i month"))],
                'month' => date('m', strtotime("-$i month")),
            ];//date('m', strtotime("-$i month"));
        }
    }

    public function getTotalesProyectos()
    {
        $fvc = ProjectData::where('step_name','implementacion')->where('substep_name','actividades')->get();
        $desa = ProjectData::where('step_name','ejecucion')->where('substep_name','actividades')->get();

        $fvcRetraso = 0;
        $fvcPorVencer = 0;
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
        $desaTotal = 0;

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

        $labels = [];

        $fvc = ProjectData::where('step_name','implementacion')->where('substep_name','actividades')->get();

        foreach ($fvc as $key => $value) {
            $table = @$value->content['table'];
            if($table) {
                foreach ($table as $key1 => $value1) {
                    $fechaFin = $value1['dateEnd'];
                    $color = "#1D4ED8";
                    $data = $this->contarPorMeses($this->mesesPos,$fechaFin,$color);
                    if ($data) {
                        $labels[] = $data;
                    }
                }
            }
        }

        return $labels;

    }

    private function contarPorMeses($meses, $fechaFin,$color)
    {
        $fechaComoEntero = strtotime($fechaFin);
        $mes = date("m", $fechaComoEntero);
        foreach ($meses as $key => $value) {
            if ($value['month'] == $mes) {
                $value['y'] = $value['y'] + 1;
                $meses[$key]['y'] = $value['y'];
                $meses[$key]['color'] = $color;
                unset($meses[$key]['month']);
                return $meses[$key];
            }
        }

    }

    public function getFlujoProyectosTotalesDesa()
    {

        $labels = [];

        $desa = ProjectData::where('step_name','ejecucion')->where('substep_name','actividades')->get();

        foreach ($desa as $key => $value) {
            $table = @$value->content['table'];
            if($table) {
                foreach ($table as $key1 => $value1) {
                    $fechaFin = $value1['dateEnd'];
                    $color = "#1D4ED8";
                    $data = $this->contarPorMeses($this->mesesPos,$fechaFin,$color);
                    if ($data) {
                        $labels[] = $data;
                    }
                }
            }
        }

        return $labels;

    }

    public function getFlujoProyectosPorVencerDesa()
    {

        $labels = [];

        $desa = ProjectData::where('step_name','ejecucion')->where('substep_name','actividades')->get();

        foreach ($desa as $key => $value) {
            $table = @$value->content['table'];
            if($table) {
                foreach ($table as $key1 => $value1) {
                    $fechaFin = $value1['dateEnd'];
                    $color = "#B91C1C";
                    $data = $this->contarPorMesesPorVencer($this->mesesPos,$fechaFin,$color);
                    if ($data) {
                        $labels[] = $data;
                    }
                }
            }
        }

        return $labels;

    }
    public function getFlujoProyectosPorVencerFvc()
    {

        $labels = [];

        $fvc = ProjectData::where('step_name','implementacion')->where('substep_name','actividades')->get();

        foreach ($fvc as $key => $value) {
            $table = @$value->content['table'];
            if($table) {
                foreach ($table as $key1 => $value1) {
                    $fechaFin = $value1['dateEnd'];
                    $color = "#B91C1C";
                    $data = $this->contarPorMesesPorVencer($this->mesesPos,$fechaFin,$color);
                    if ($data) {
                        $labels[] = $data;
                    }
                }
            }
        }

        return $labels;

    }

    private function contarPorMesesPorVencer($meses, $fechaFin,$color)
    {
        $fechaComoEntero = strtotime($fechaFin);
        $mes = date("m", $fechaComoEntero);
        $fechaFinal = new DateTime($fechaFin);
        $hoy = new DateTime(date('Y-m-d'));
        foreach ($meses as $key => $value) {
            if ($value['month'] == $mes) {
                $diff = $hoy->diff($fechaFinal);

                if (!$diff->invert && $diff->days >= 0 && $diff->days <= 3 ) {
                    $meses[$key]['y'] = $value['y'] + 1;
                }

                $meses[$key]['color'] = $color;
                unset($meses[$key]['month']);
                return $meses[$key];
            }
        }

    }


}
