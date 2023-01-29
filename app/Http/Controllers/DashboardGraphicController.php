<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ProjectData;
use DateTime;
use Illuminate\Http\Request;

class DashboardGraphicController extends Controller
{
    public function getTotalesProyectos()
    {
        $fvc = ProjectData::where('step_name','implementacion')->where('substep_name','actividades')->get();
        $desa = ProjectData::where('step_name','ejecucion')->where('substep_name','actividades')->get();

        $fvcRetraso = 0;
        $fvcPorVencer = 0;
        $fvcTotal = 0;

        foreach ($fvc as $key => $value) {
            $table = $value->content['table'];
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
            $table = $value->content['table'];
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

    public function getFlujoProyectos()
    {
        $meses = [
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


        $labels = [];
        $mesesPos =  [];

        for ($i=0; $i < 4; $i++)
        {
            $mesesPos[] = date('m', strtotime("-$i month"));
        }

        $fvc = ProjectData::where('step_name','implementacion')->where('substep_name','actividades')->get();
        $desa = ProjectData::where('step_name','ejecucion')->where('substep_name','actividades')->get();

        $fvcRetraso = 0;
        $fvcTotal = 0;

        foreach ($fvc as $key => $value) {
            $table = $value->content['table'];
            if($table) {
                foreach ($table as $key1 => $value1) {

                    $fechaFin = $value1['dateEnd'];
                    $fechaFinEntero = strtotime($fechaFin);
                    $mes = date("m", $fechaFinEntero);

                    if (in_array($mes,$mesesPos)) {

                    }

                    $fechaFin = new DateTime($value1['dateEnd']);

                }
            }
        }

        $desaRetraso = 0;
        $desaTotal = 0;

        foreach ($desa as $key => $value) {
            $table = $value->content['table'];
            if($table) {
                foreach ($table as $key1 => $value1) {
                    $desaTotal++;

                    $hoy = new DateTime(date('Y-m-d'));
                    $fechaFin = new DateTime($value1['dateEnd']);

                    $diff = $hoy->diff($fechaFin);

                    if ($diff->invert) {
                        $desaRetraso++;
                    }
                }
            }
        }

        return $meses;
    }
}
