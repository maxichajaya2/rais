<?php

namespace App\Http\Controllers\Investigador\Informes;

use App\Http\Controllers\S3Controller;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class Informe_academicoController extends S3Controller {
  public function listadoPendientes(Request $request) {
    $informes = DB::table('Informe_tipo as t1')
      ->join('Proyecto as t2', 't1.tipo', '=', 't2.tipo_proyecto')
      ->leftJoin('Informe_tecnico as t3', function (JoinClause $join) {
        $join->on('t3.proyecto_id', '=', 't2.id')
          ->on('t1.id', '=', 't3.informe_tipo_id');
      })
      ->leftJoin('Proyecto_integrante as t5', 't5.proyecto_id', '=', 't2.id')
      ->leftJoin('Informe_tecnico as t33', 't33.proyecto_id', '=', 't2.id')
      ->leftJoin('Informe_tipo as t11', 't11.id', '=', 't33.informe_tipo_id')
      ->select([
        't3.id AS id',
        't2.id AS proyecto_id',
        't2.codigo_proyecto',
        't2.titulo',
        't2.tipo_proyecto',
        't1.informe',
        't2.periodo',
        't3.fecha_presentacion',
        DB::raw("CASE(t3.estado)
          WHEN 0 THEN 'En proceso'
          WHEN 1 THEN 'Aprobado'
          WHEN 2 THEN 'Presentado'
          WHEN 3 THEN 'Observado'
        ELSE 'Por presentar' END AS estado")
      ])
      ->where(function ($query) {
        $query->whereIn(DB::raw('COALESCE(t5.condicion, t5.proyecto_integrante_tipo_id)'), ['Responsable', 'Asesor', 7]);
      })
      ->where(function ($query) {
        $query->whereIn('t3.estado', [0, -1, 2, 3])
          ->orWhereNull('t3.estado');
      })
      ->where('t2.estado', 1)
      ->where('t2.periodo', '>', 2016)
      ->where('t5.investigador_id', $request->attributes->get('token_decoded')->investigador_id)
      ->where(DB::raw("
        CASE
            WHEN t2.tipo_proyecto = 'PTPDOCTO' THEN
                IF (t1.informe = 'Segundo informe académico de avance',
                    IF (t11.informe = 'Informe académico de avance' AND t33.estado = 1, true, false),
                true)
                AND
                IF (t1.informe = 'Informe académico final',
                    IF (t11.informe = 'Segundo informe académico de avance' AND t33.estado = 1, true, false),
                true)
            ELSE
                IF (t1.informe = 'Informe académico final' AND t1.tipo <> 'ptpgrado',
                    IF (t11.informe = 'Informe académico de avance' AND t33.estado = 1, true, false),
                true)
        END
    "), true)
      ->groupBy('t2.id')
      ->groupBy('t1.id')
      ->get();

    return $informes;
  }

  public function listadoAceptados(Request $request) {
    $informes = DB::table('Informe_tipo as t1')
      ->join('Proyecto as t2', 't1.tipo', '=', 't2.tipo_proyecto')
      ->leftJoin('Informe_tecnico as t3', function (JoinClause $join) {
        $join->on('t3.proyecto_id', '=', 't2.id')
          ->on('t1.id', '=', 't3.informe_tipo_id');
      })
      ->leftJoin('Proyecto_integrante as t5', 't5.proyecto_id', '=', 't2.id')
      ->leftJoin('Informe_tecnico as t33', 't33.proyecto_id', '=', 't2.id')
      ->leftJoin('Informe_tipo as t11', 't11.id', '=', 't33.informe_tipo_id')
      ->select([
        't3.id AS id',
        't2.id AS proyecto_id',
        't2.codigo_proyecto',
        't2.titulo',
        't2.tipo_proyecto',
        't1.informe',
        't2.periodo',
        't3.fecha_presentacion',
        DB::raw("CASE(t3.estado)
          WHEN 0 THEN 'En proceso'
          WHEN 1 THEN 'Aprobado'
          WHEN 2 THEN 'Presentado'
          WHEN 3 THEN 'Observado'
        ELSE 'Por presentar' END AS estado")
      ])
      ->where(function ($query) {
        $query->whereIn(DB::raw('COALESCE(t5.condicion, t5.proyecto_integrante_tipo_id)'), ['Responsable', 'Asesor', 7]);
      })
      ->where('t3.estado', 1)
      ->where('t2.estado', 1)
      ->where('t2.periodo', '>', 2016)
      ->where('t5.investigador_id', '=', $request->attributes->get('token_decoded')->investigador_id)
      ->where(DB::raw("
        CASE
            WHEN t2.tipo_proyecto = 'PTPDOCTO' THEN
                IF (t1.informe = 'Segundo informe académico de avance',
                    IF (t11.informe = 'Informe académico de avance' AND t33.estado = 1, true, false),
                true)
                AND
                IF (t1.informe = 'Informe académico final',
                    IF (t11.informe = 'Segundo informe académico de avance' AND t33.estado = 1, true, false),
                true)
            ELSE
                IF (t1.informe = 'Informe académico final' AND t1.tipo <> 'ptpgrado',
                    IF (t11.informe = 'Informe académico de avance' AND t33.estado = 1, true, false),
                true)
        END
    "), true)
      ->groupBy('t2.id')
      ->groupBy('t1.id')
      ->get();

    return $informes;
  }

  public function verInforme(Request $request) {
    $proyecto = DB::table('Informe_tecnico AS a')
      ->join('Proyecto AS f', 'f.id', '=', 'a.proyecto_id')
      ->leftJoin('Facultad AS b', 'b.id', '=', 'f.facultad_id')
      ->leftJoin('Grupo AS c', 'c.id', '=', 'f.grupo_id')
      ->leftJoin('Grupo_integrante AS d', function (JoinClause $join) {
        $join->on('d.grupo_id', '=', 'c.id')
          ->where('d.cargo', '=', 'Coordinador');
      })
      ->leftJoin('Usuario_investigador AS e', 'e.id', '=', 'd.investigador_id')
      ->leftJoin('Linea_investigacion AS g', 'g.id', '=', 'f.linea_investigacion_id')
      ->leftJoin('Informe_tipo AS h', 'h.id', '=', 'a.informe_tipo_id')
      ->select([
        'f.id AS proyecto_id',
        'f.codigo_proyecto',
        'f.titulo',
        'f.periodo',
        'f.localizacion',
        'b.nombre AS facultad',
        'c.grupo_nombre',
        DB::raw("CONCAT(e.apellido1, ' ', e.apellido2, ' ', e.nombres) AS coordinador"),
        'f.resolucion_rectoral',
        'g.nombre AS linea',
        //  Informe
        'h.tipo',
        'h.informe',
        DB::raw("CASE(a.estado)
          WHEN 0 THEN 'En proceso'
          WHEN 1 THEN 'Aprobado'
          WHEN 2 THEN 'Presentado'
          WHEN 3 THEN 'Observado'
        ELSE 'Por presentar' END AS estado"),
        'a.updated_at',
        'a.resumen_ejecutivo',
        'a.palabras_clave',
        'a.infinal1',
        'a.infinal2',
        'a.infinal3',
        'a.infinal4',
        'a.infinal5',
        'a.infinal6',
        'a.infinal7',
        'a.infinal8',
        'a.infinal9',
        'a.infinal10',
        'a.infinal11',
      ])
      ->where('a.id', '=', $request->query('id'))
      ->first();

    $miembros = DB::table('Proyecto_integrante AS a')
      ->leftJoin('Usuario_investigador AS b', 'b.id', '=', 'a.investigador_id')
      ->select([
        'b.codigo',
        DB::raw("CONCAT(b.apellido1, ' ', b.apellido2, ' ', b.nombres) AS nombres"),
        'a.condicion',
        'b.tipo'
      ])
      ->where('a.proyecto_id', '=', $proyecto->proyecto_id)
      ->get();

    $extras = DB::table('Proyecto_descripcion')
      ->select([
        'codigo',
        'detalle'
      ])
      ->where('proyecto_id', '=', $proyecto->proyecto_id)
      ->get()
      ->mapWithKeys(function ($item) {
        return [$item->codigo => $item->detalle];
      });

    if ($proyecto->tipo == "pconfigi") {
      $pdf = Pdf::loadView('investigador.informes.academico.pconfigi', ['proyecto' => $proyecto, 'miembros' => $miembros, 'extras' => $extras]);
      return $pdf->stream();
    } else if ($proyecto->tipo == "eci") {
      $pdf = Pdf::loadView('investigador.informes.academico.eci', ['proyecto' => $proyecto, 'miembros' => $miembros]);
      return $pdf->stream();
    } else {
      return "ok";
    }
  }
}
