<?php

namespace App\Http\Controllers\Admin\Estudios;

use App\Http\Controllers\Admin\Estudios\Proyectos\EciController;
use App\Http\Controllers\S3Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\Shared\Html;

class ProyectosGrupoController extends S3Controller {
  public function listado($periodo) {
    $proyectos = DB::table('Proyecto AS a')
      ->join('Grupo AS b', 'b.id', '=', 'a.grupo_id')
      ->leftJoin('Linea_investigacion AS c', 'c.id', '=', 'a.linea_investigacion_id')
      ->leftJoin('Proyecto_integrante AS d', 'd.proyecto_id', '=', 'a.id')
      ->join('Facultad AS e', 'e.id', '=', 'b.facultad_id')
      ->leftJoin('Proyecto_presupuesto AS f', 'f.proyecto_id', '=', 'a.id')
      ->join('Usuario_investigador AS g', 'g.id', '=', 'd.investigador_id')
      ->select(
        'a.id',
        'a.tipo_proyecto',
        'a.codigo_proyecto',
        'c.nombre AS linea',
        'a.titulo',
        DB::raw('CONCAT(g.apellido1, " " , g.apellido2, ", ", g.nombres) AS responsable'),
        'b.grupo_nombre',
        'e.nombre AS facultad',
        DB::raw('SUM(f.monto) AS monto'),
        'a.resolucion_rectoral',
        'a.updated_at',
        'a.estado'
      )
      ->where('d.condicion', '=', 'Responsable')
      ->where('a.periodo', '=', $periodo)
      ->groupBy('a.id')
      ->get();

    return ['data' => $proyectos];
  }

  public function dataProyecto(Request $request) {
    $tipo = DB::table('Proyecto')
      ->select(['tipo_proyecto'])
      ->where('id', '=', $request->query('proyecto_id'))
      ->first();

    switch ($tipo->tipo_proyecto) {
      case "PCONFIGI":
        $detalle = $this->detalle($request);
        $miembros = $this->miembros($request);
        $descripcion = $this->descripcion($request);
        $actividades = $this->actividades($request);
        $presupuesto = $this->presupuesto($request);

        return [
          'detalle' => $detalle,
          'miembros' => $miembros,
          'descripcion' => $descripcion,
          'actividades' => $actividades,
          'presupuesto' => $presupuesto,
        ];
      case "ECI":
        $ctrl = new EciController();
        $detalle = $ctrl->detalle($request);
        $especificaciones = $ctrl->especificaciones($request);
        $impacto = $ctrl->impacto($request);
        $descripcion = $this->descripcion($request);
        $presupuesto = $this->presupuesto($request);

        return [
          'detalle' => $detalle,
          'descripcion' => $descripcion,
          'especificaciones' => $especificaciones,
          'impacto' => $impacto,
          'presupuesto' => $presupuesto,
        ];
      default:
        $detalle = $this->detalle($request);
        $miembros = $this->miembros($request);
        $descripcion = $this->descripcion($request);
        $actividades = $this->actividades($request);
        $presupuesto = $this->presupuesto($request);

        return [
          'detalle' => $detalle,
          'miembros' => $miembros,
          'descripcion' => $descripcion,
          'actividades' => $actividades,
          'presupuesto' => $presupuesto,
        ];
    }
  }

  public function detalle(Request $request) {
    $detalle = DB::table('Proyecto AS a')
      ->leftJoin('Linea_investigacion AS b', 'b.id', '=', 'a.linea_investigacion_id')
      ->leftJoin('Ocde AS c', 'c.id', '=', 'a.ocde_id')
      ->select(
        'a.titulo',
        'a.codigo_proyecto',
        'a.tipo_proyecto',
        'a.estado',
        'a.resolucion_rectoral',
        DB::raw("IFNULL(a.resolucion_fecha, '') AS resolucion_fecha"),
        'a.comentario',
        'a.observaciones_admin',
        'a.fecha_inicio',
        'a.fecha_fin',
        'a.palabras_clave',
        'b.nombre AS linea',
        'c.linea AS ocde',
        'a.localizacion'
      )
      ->where('a.id', '=', $request->query('proyecto_id'))
      ->first();

    return $detalle;
  }

  public function updateDetalle(Request $request) {
    $count = DB::table('Proyecto')
      ->where('id', '=', $request->input('proyecto_id'))
      ->update([
        'titulo' => $request->input('titulo'),
        'codigo_proyecto' => $request->input('codigo_proyecto'),
        'resolucion_rectoral' => $request->input('resolucion_rectoral'),
        'resolucion_fecha' => $request->input('resolucion_fecha'),
        'resolucion_decanal' => $request->input('resolucion_decanal'),
        'comentario' => $request->input('comentario'),
        'estado' => $request->input('estado')["value"],
      ]);

    if ($count > 0) {
      //  Relacionar proyecto a economía
      if ($request->input('resolucion_rectoral') != "" && $request->input('resolucion_fecha') != "" && $request->input('resolucion_decanal') != "") {

        return ['message' => 'success', 'detail' => 'Datos del proyecto actualizados y proyecto incluído al módulo de economía'];
      }
      return ['message' => 'success', 'detail' => 'Datos del proyecto actualizados correctamente'];
    } else {
      return ['message' => 'warning', 'detail' => 'No se pudo actualizar la información'];
    }
  }

  public function miembros(Request $request) {
    $miembros = DB::table('Proyecto_integrante AS a')
      ->join('Proyecto_integrante_tipo AS b', 'b.id', '=', 'a.proyecto_integrante_tipo_id')
      ->join('Usuario_investigador AS c', 'c.id', '=', 'a.investigador_id')
      ->select(
        'a.id',
        'b.nombre AS tipo_integrante',
        DB::raw('CONCAT(c.apellido1, " ", c.apellido2, " ", c.nombres) AS nombre'),
        'c.tipo AS tipo_investigador'
      )
      ->where('a.proyecto_id', '=', $request->query('proyecto_id'))
      ->orderBy('b.id')
      ->get();

    return $miembros;
  }

  public function cartas(Request $request) {
    $cartas = DB::table('Proyecto_doc')
      ->select(
        'id',
        'nombre',
        'archivo'
      )
      ->where('proyecto_id', '=', $request->query('proyecto_id'))
      ->get()
      ->map(function ($item) {
        $item->archivo = "/minio/proyecto-doc/" . $item->archivo;
        return $item;
      });

    return $cartas;
  }

  public function descripcion(Request $request) {
    $descripcion = DB::table('Proyecto_descripcion')
      ->select(
        'id',
        'codigo',
        'detalle'
      )
      ->where('proyecto_id', '=', $request->query('proyecto_id'))
      ->get();

    $detalles = [];
    foreach ($descripcion as $data) {
      if (isset($data->codigo)) {
        $detalles[$data->codigo] = $data->detalle;
      }
    }

    return $detalles;
  }

  public function actividades(Request $request) {
    $actividades = DB::table('Proyecto_actividad')
      ->select(
        'id',
        'actividad',
        'fecha_inicio',
        'fecha_fin'
      )
      ->where('proyecto_id', '=', $request->query('proyecto_id'))
      ->get();

    return $actividades;
  }

  public function presupuesto(Request $request) {
    $presupuesto = DB::table('Proyecto_presupuesto AS a')
      ->join('Partida AS b', 'b.id', '=', 'a.partida_id')
      ->select(
        'a.tipo',
        'b.partida',
        'a.justificacion',
        'a.monto',
      )
      ->where('a.proyecto_id', '=', $request->query('proyecto_id'))
      ->orderBy('a.tipo')
      ->get();

    //  Info de presupuesto
    $info = [
      'bienes_monto' => 0.00, 'bienes_cantidad' => 0,
      'servicios_monto' => 0.00, 'servicios_cantidad' => 0,
      'otros_monto' => 0.00, 'otros_cantidad' => 0
    ];

    foreach ($presupuesto as $data) {
      if ($data->tipo == "Bienes") {
        $info["bienes_monto"] += $data->monto;
        $info["bienes_cantidad"]++;
      }
      if ($data->tipo == "Servicios") {
        $info["servicios_monto"] += $data->monto;
        $info["servicios_cantidad"]++;
      }
      if ($data->tipo == "Otros") {
        $info["otros_monto"] += $data->monto;
        $info["otros_cantidad"]++;
      }
    }

    $div = ($info["bienes_monto"] + $info["servicios_monto"] + $info["otros_monto"]);

    if ($div != 0) {
      $info["bienes_porcentaje"] = number_format(($info["bienes_monto"] / $div) * 100, 2);
      $info["servicios_porcentaje"] = number_format(($info["servicios_monto"] / $div) * 100, 2);
      $info["otros_porcentaje"] = number_format(($info["otros_monto"] / $div) * 100, 2);
    } else {
      $info["bienes_porcentaje"] = 0;
      $info["servicios_porcentaje"] = 0;
      $info["otros_porcentaje"] = 0;
    }

    return ['data' => $presupuesto, 'info' => $info];
  }

  public function exportToWord(Request $request) {
    $descripcion = DB::table('Proyecto_descripcion')
      ->select(
        'id',
        'codigo',
        'detalle'
      )
      ->where('proyecto_id', '=', $request->query('proyecto_id'))
      ->get();

    $detalles = [];
    foreach ($descripcion  as $data) {
      switch ($data->codigo) {
        case "resumen_ejecutivo":
          $detalles["resumen_ejecutivo"] = $data->detalle;
          break;
        case "antecedentes":
          $detalles["antecedentes"] = $data->detalle;
          break;
        case "objetivos":
          $detalles["objetivos"] = $data->detalle;
          break;
        case "justificacion":
          $detalles["justificacion"] = $data->detalle;
          break;
        case "hipotesis":
          $detalles["hipotesis"] = $data->detalle;
          break;
        case "metodologia_trabajo":
          $detalles["metodologia_trabajo"] = $data->detalle;
          break;
        case "contribucion_impacto":
          $detalles["contribucion_impacto"] = $data->detalle;
          break;
        default:
          break;
      }
    }

    $phpWord = new PhpWord();
    $section = $phpWord->addSection();

    $section->addText("Resumen ejecutivo:", array('name' => 'Calibri', 'size' => 12, 'bold' => true), array('alignment' => 'center'));
    $resumen_ejecutivo = htmlspecialchars($detalles["resumen_ejecutivo"], ENT_QUOTES, 'UTF-8');
    // Html::addHtml($section, $resumen_ejecutivo);
    $section->addText($resumen_ejecutivo);

    $section->addText("Antecedentes:", array('name' => 'Calibri', 'size' => 12, 'bold' => true), array('alignment' => 'center'));
    $antecedentes = htmlspecialchars($detalles["antecedentes"], ENT_QUOTES, 'UTF-8');
    // Html::addHtml($section, $antecedentes);
    $section->addText($antecedentes);

    $section->addText("Objetivos:", array('name' => 'Calibri', 'size' => 12, 'bold' => true), array('alignment' => 'center'));
    $objetivos = htmlspecialchars($detalles["objetivos"], ENT_QUOTES, 'UTF-8');
    // Html::addHtml($section, $objetivos);
    $section->addText($objetivos);

    $section->addText("Justificación:", array('name' => 'Calibri', 'size' => 12, 'bold' => true), array('alignment' => 'center'));
    $justificacion = htmlspecialchars($detalles["justificacion"], ENT_QUOTES, 'UTF-8');
    // Html::addHtml($section, $justificacion);
    $section->addText($justificacion);

    $section->addText("Hipótesis:", array('name' => 'Calibri', 'size' => 12, 'bold' => true), array('alignment' => 'center'));
    $hipotesis = htmlspecialchars($detalles["hipotesis"], ENT_QUOTES, 'UTF-8');
    // Html::addHtml($section, $hipotesis);
    $section->addText($hipotesis);

    $section->addText("Metodología de trabajo:", array('name' => 'Calibri', 'size' => 12, 'bold' => true), array('alignment' => 'center'));
    $metodologia_trabajo = htmlspecialchars($detalles["metodologia_trabajo"], ENT_QUOTES, 'UTF-8');
    // Html::addHtml($section, $metodologia_trabajo);
    $section->addText($metodologia_trabajo);

    $section->addText("Contribución:", array('name' => 'Calibri', 'size' => 12, 'bold' => true), array('alignment' => 'center'));
    $contribucion_impacto = htmlspecialchars($detalles["contribucion_impacto"], ENT_QUOTES, 'UTF-8');
    // Html::addHtml($section, $contribucion_impacto);
    $section->addText($contribucion_impacto);

    $objWriter = IOFactory::createWriter($phpWord, 'Word2007');

    // Crear un archivo temporal en el almacenamiento
    $fileName = 'formato_word.docx';
    $tempPath = storage_path('app/' . $fileName);
    $objWriter->save($tempPath);

    // Devolver el archivo como una respuesta de descarga
    return response()->download($tempPath)->deleteFileAfterSend();
  }
}
