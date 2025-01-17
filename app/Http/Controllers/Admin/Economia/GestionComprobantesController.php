<?php

namespace App\Http\Controllers\Admin\Economia;

use App\Http\Controllers\S3Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;

class GestionComprobantesController extends S3Controller
{
  public function listadoProyectos()
  {
    $responsable = DB::table('Proyecto_integrante AS a')
      ->leftJoin('Usuario_investigador AS b', 'b.id', '=', 'a.investigador_id')
      ->select(
        'a.proyecto_id',
        DB::raw('CONCAT(b.apellido1, " " , b.apellido2, ", ", b.nombres) AS responsable')
      )
      ->where('condicion', '=', 'Responsable');

    $pendientes = DB::table('Geco_documento AS a')
      ->select([
        'geco_proyecto_id',
        DB::raw('COUNT(*) AS cuenta')
      ])
      ->where('estado', '=', 4)
      ->groupBy('geco_proyecto_id');

    $revisados = DB::table('Geco_documento AS a')
      ->select([
        'geco_proyecto_id',
        DB::raw('COUNT(*) AS cuenta')
      ])
      ->where('estado', '!=', 4)
      ->groupBy('geco_proyecto_id');

    $total = DB::table('Geco_documento AS a')
      ->select([
        'geco_proyecto_id',
        DB::raw('COUNT(*) AS cuenta')
      ])
      ->groupBy('geco_proyecto_id');

    $proyectos = DB::table('Geco_proyecto AS a')
      ->join('Proyecto AS b', 'b.id', '=', 'a.proyecto_id')
      ->leftJoin('Facultad AS c', 'c.id', '=', 'b.facultad_id')
      ->leftJoinSub($responsable, 'res', 'res.proyecto_id', '=', 'b.id')
      ->leftJoinSub($pendientes, 'd', 'd.geco_proyecto_id', '=', 'a.id')
      ->leftJoinSub($revisados, 'e', 'e.geco_proyecto_id', '=', 'a.id')
      ->leftJoinSub($total, 'f', 'f.geco_proyecto_id', '=', 'a.id')
      ->select([
        'a.id',
        'b.codigo_proyecto',
        'res.responsable',
        'c.nombre AS facultad',
        'b.tipo_proyecto',
        'b.periodo',
        'd.cuenta AS pendientes',
        'e.cuenta AS revisados',
        'f.cuenta AS total',
      ])
      ->groupBy('b.id')
      ->orderByDesc('d.cuenta')
      ->get();

    return $proyectos;
  }

  public function detalleProyecto(Request $request)
  {
    $responsable = DB::table('Proyecto_integrante AS a')
      ->leftJoin('Usuario_investigador AS b', 'b.id', '=', 'a.investigador_id')
      ->select(
        'a.proyecto_id',
        DB::raw('CONCAT(b.apellido1, " " , b.apellido2, ", ", b.nombres) AS responsable'),
        'b.email3',
        'b.telefono_movil'
      )
      ->where('condicion', '=', 'Responsable');

    $detalle = DB::table('Geco_proyecto AS a')
      ->join('Proyecto AS b', 'b.id', '=', 'a.proyecto_id')
      ->leftJoinSub($responsable, 'res', 'res.proyecto_id', '=', 'b.id')
      ->select([
        'b.tipo_proyecto',
        'b.titulo',
        'b.codigo_proyecto',
        'a.estado',
        'res.responsable',
        'res.email3',
        'res.telefono_movil',
      ])
      ->where('a.id', '=', $request->query('geco_id'))
      ->first();

    return $detalle;
  }

  public function listadoComprobantes(Request $request)
  {
    $comprobantes = DB::table('Geco_documento')
      ->select([
        'id',
        'tipo',
        'numero',
        'fecha',
        DB::raw("ROUND(total_declarado, 2) AS total_declarado"),
        'created_at',
        'updated_at',
        'estado',
        'razon_social',
        'ruc',
        'observacion'
      ])
      ->where('geco_proyecto_id', '=', $request->query('geco_id'))
      ->get()
      ->map(function ($item) {
        $item->total_declarado = (float) $item->total_declarado;
        return $item;
      });

    return $comprobantes;
  }

  public function listadoPartidasComprobante(Request $request)
  {

    $partidas = DB::table('Geco_documento_item AS a')
      ->join('Partida AS b', 'b.id', '=', 'a.partida_id')
      ->select([
        'a.id',
        'b.codigo',
        'b.partida',
        'a.total'
      ])
      ->where('a.geco_documento_id', '=', $request->query('geco_documento_id'))
      ->where('b.tipo', '!=', 'Otros')
      ->get();

    $comprobante = DB::table('Geco_documento_file')
      ->select([
        'key'
      ])
      ->where('geco_documento_id', '=', $request->query('geco_documento_id'))
      ->first();

    if ($comprobante->key != null) {
      $comprobante->url = '/minio/geco-documento/' . $comprobante->key;
    }

    return ['partidas' => $partidas, 'comprobante' => $comprobante->url];
  }

  public function updateEstadoComprobante(Request $request)
  {
    if ($request->input('estado')["value"] == 3 && $request->input('observacion') == "") {
      return ['message' => 'warning', 'detail' => 'Necesita detallar la observación'];
    } else if ($request->input('estado')["value"] == 3 && $request->input('observacion') != "") {
      $count = DB::table('Geco_documento')
        ->where('id', '=', $request->input('geco_documento_id'))
        ->update([
          'estado' => $request->input('estado')["value"],
          'observacion' => $request->input('observacion')
        ]);

      $proyecto = DB::table('Geco_documento')
        ->select([
          'geco_proyecto_id'
        ])
        ->where('id', '=', $request->input('geco_documento_id'))
        ->first();

      //  Cálculos previos - Eliminar cuando todo esté normal
      $this->limpiarMontos($proyecto->geco_proyecto_id);
      $this->calcularEnviado($proyecto->geco_proyecto_id);
      $this->calcularRendicion($proyecto->geco_proyecto_id);
      $this->calcularExceso($proyecto->geco_proyecto_id);

      if ($count > 0) {
        $this->agregarAudit($request);
        return ['message' => 'info', 'detail' => 'Estado del comprobante actualizado'];
      } else {
        return ['message' => 'warning', 'detail' => 'No se pudo actualizar la información'];
      }
    } else {
      $count = DB::table('Geco_documento')
        ->where('id', '=', $request->input('geco_documento_id'))
        ->update([
          'estado' => $request->input('estado')["value"]
        ]);

      $proyecto = DB::table('Geco_documento')
        ->select([
          'geco_proyecto_id'
        ])
        ->where('id', '=', $request->input('geco_documento_id'))
        ->first();

      //  Cálculos previos - Eliminar cuando todo esté normal
      $this->limpiarMontos($proyecto->geco_proyecto_id);
      $this->calcularEnviado($proyecto->geco_proyecto_id);
      $this->calcularRendicion($proyecto->geco_proyecto_id);
      $this->calcularExceso($proyecto->geco_proyecto_id);

      if ($count > 0) {
        $this->agregarAudit($request);
        return ['message' => 'info', 'detail' => 'Estado del comprobante actualizado'];
      } else {
        return ['message' => 'warning', 'detail' => 'No se pudo actualizar la información'];
      }
    }
  }

  public function listadoPartidasProyecto(Request $request)
  {

    $totalMonto = 0;
    $totalMontoRendidoEnviado = 0;
    $totalMontoRendido = 0;
    $totalMontoExcedido = 0;


    $partidas = DB::table('Geco_proyecto_presupuesto AS a')
      ->leftJoin('Partida AS b', 'b.id', '=', 'a.partida_id')
      ->select([
        'b.codigo',
        'b.tipo',
        'b.partida',
        'a.monto',
        'a.monto_rendido_enviado',
        DB::raw("LEAST(a.monto, a.monto_rendido) AS monto_rendido"),
        'a.monto_excedido'
      ])
      ->where('a.geco_proyecto_id', '=', $request->query('geco_proyecto_id'))
      ->where('b.tipo', '!=', 'Otros')
      ->orderBy('b.tipo', 'ASC')
      ->get();


    foreach ($partidas as $partida) {
      $partida->monto = (float) $partida->monto;
      $partida->monto_rendido_enviado = (float) $partida->monto_rendido_enviado;
      $partida->monto_rendido = (float) $partida->monto_rendido;
      $partida->monto_excedido = (float) $partida->monto_excedido;

      // Suma a los totales
      $totalMonto += $partida->monto;
      $totalMontoRendidoEnviado += $partida->monto_rendido_enviado;
      $totalMontoRendido += $partida->monto_rendido;
      $totalMontoExcedido += $partida->monto_excedido;
    }


    // Agrega los totales al final de las partidas
    $partidas[] = (object) [
      'total_monto' => number_format($totalMonto, 2),
      'total_monto_rendido_enviado' => number_format($totalMontoRendidoEnviado, 2),
      'total_monto_rendido' => number_format($totalMontoRendido, 2),
      'total_monto_excedido' => number_format($totalMontoExcedido, 2),
    ];


    return $partidas;
  }
  public function detalleGasto(Request $request) {
    $geco_proyecto_id = $request->query('geco_proyecto_id');
    
    $proyecto_id = DB::table('Geco_proyecto')
      ->select([
        'proyecto_id AS id'
      ])
      ->where('id', '=', $geco_proyecto_id)
      ->first();

    $proyecto = DB::table('Proyecto AS a')
      ->join('Proyecto_integrante AS b', function (JoinClause $join) {
        $join->on('a.id', '=', 'b.proyecto_id')
          ->where('condicion', '=', 'Responsable');
      })
      ->join('Usuario_investigador AS c', 'b.investigador_id', '=', 'c.id')
      ->leftJoin('Grupo AS d', 'd.id', '=', 'a.grupo_id')
      ->leftJoin('Facultad AS e', 'e.id', '=', 'd.facultad_id')
      ->select([
        'd.grupo_nombre',
        'e.nombre AS facultad',
        'a.titulo',
        DB::raw("CONCAT(c.apellido1, ' ', c.apellido2, ' ', c.nombres) AS responsable"),
        'c.email3',
        'c.telefono_movil',
        'a.codigo_proyecto',
      ])
      ->where('a.id', '=', $proyecto_id->id)
      ->first();

    $bienes = DB::table('Geco_documento AS a')
      ->join('Geco_documento_item AS b', 'b.geco_documento_id', '=', 'a.id')
      ->join('Partida AS c', 'c.id', '=', 'b.partida_id')
      ->select([
        'a.fecha',
        'a.tipo',
        'a.numero',
        'c.codigo',
        'c.partida',
        'b.total'
      ])
      ->where('a.geco_proyecto_id', '=', $geco_proyecto_id)
      ->where('c.tipo', '=', 'Bienes')
      ->where('a.estado', '=', 1)
      ->get();

    $servicios = DB::table('Geco_documento AS a')
      ->join('Geco_documento_item AS b', 'b.geco_documento_id', '=', 'a.id')
      ->join('Partida AS c', 'c.id', '=', 'b.partida_id')
      ->select([
        'a.fecha',
        'a.tipo',
        'a.numero',
        'c.codigo',
        'c.partida',
        'b.total'
      ])
      ->where('a.geco_proyecto_id', '=', $geco_proyecto_id)
      ->where('c.tipo', '=', 'Servicios')
      ->where('a.estado', '=', 1)
      ->get();

    $pdf = Pdf::loadView(
      'investigador.informes.economico.detalle_gasto',
      ['proyecto' => $proyecto, 'bienes' => $bienes, 'servicios' => $servicios]
    );
    return $pdf->stream();
  }

  public function hojaResumen(Request $request) {
    $proyecto_id = DB::table('Geco_proyecto')
      ->select([
        'proyecto_id AS id'
      ])
      ->where('id', '=', $request->query('id'))
      ->first();

    $proyecto = DB::table('Proyecto AS a')
      ->join('Proyecto_integrante AS b', function (JoinClause $join) {
        $join->on('a.id', '=', 'b.proyecto_id')
          ->where('condicion', '=', 'Responsable');
      })
      ->join('Usuario_investigador AS c', 'b.investigador_id', '=', 'c.id')
      ->leftJoin('Facultad AS d', 'a.facultad_id', '=', 'd.id')
      ->select([
        'a.fecha_inscripcion',
        'a.periodo',
        'a.tipo_proyecto',
        'a.codigo_proyecto',
        'a.titulo',
        DB::raw("COALESCE(d.nombre, 'No figura') AS facultad"),
        DB::raw("CONCAT(c.apellido1, ' ', c.apellido2, ' ', c.nombres) AS responsable"),
        'c.email3',
        'c.telefono_movil',
      ])
      ->where('a.id', '=', $proyecto_id->id)
      ->first();

    $presupuesto = DB::table('Geco_proyecto AS a')
      ->join('Geco_proyecto_presupuesto AS b', 'b.geco_proyecto_id', '=', 'a.id')
      ->join('Partida AS c', 'c.id', '=', 'b.partida_id')
      ->leftJoin('Proyecto_presupuesto AS d', function (JoinClause $join) use ($proyecto_id) {
        $join->on('d.partida_id', '=', 'b.partida_id')
          ->where('d.proyecto_id', '=', $proyecto_id->id);
      })
      ->select([
        'b.id',
        'c.tipo',
        'c.partida',
        DB::raw("COALESCE(d.monto, 0) AS monto_original"),
        DB::raw("COALESCE(b.monto, 0) AS monto_modificado"),
        DB::raw("(b.monto_rendido - b.monto_excedido) AS monto_rendido"),
        DB::raw("(b.monto - b.monto_rendido + b.monto_excedido) AS saldo_rendicion"),
        'b.monto_excedido'
      ])
      ->where('a.proyecto_id', '=', $proyecto_id->id)
      ->where('c.tipo', '!=', 'Otros')
      ->orderBy('c.tipo')
      ->get()
      ->groupBy('tipo');

    //  Estado
    $estado = '';
    $rendido = DB::table('Geco_proyecto_presupuesto AS a')
      ->join('Partida AS b', 'b.id', '=', 'a.partida_id')
      ->select([
        DB::raw("SUM(a.monto) AS total"),
        DB::raw("SUM(a.monto_rendido) AS rendido")
      ])
      ->whereIn('b.tipo', ['Bienes', 'Servicios'])
      ->where('geco_proyecto_id', '=', $request->query('id'))
      ->first();

    if ($rendido->total <= $rendido->rendido) {
      $estado = 'COMPLETADO';
    } else {
      $estado = 'PENDIENTE';
    }

    $pdf = Pdf::loadView('investigador.informes.economico.hoja_resumen', ['proyecto' => $proyecto, 'presupuesto' => $presupuesto, 'estado' => $estado]);
    return $pdf->stream();
  }

  public function detalles(Request $request) {
    
    // return 'hola'.$request->query('id');
      //  Datos generales
      $datos = DB::table('Geco_proyecto AS a')
        ->join('Proyecto AS b', 'b.id', '=', 'a.proyecto_id')
        ->select([
          'a.proyecto_id',
          'b.titulo',
          'b.codigo_proyecto',
          'b.tipo_proyecto'
        ])
        ->where('a.id', '=', $request->query('id'))
        ->first();

      //  Cifras
      $porcentaje = 0;
      $rendido = DB::table('Geco_proyecto_presupuesto AS a')
        ->join('Partida AS b', 'b.id', '=', 'a.partida_id')
        ->select([
          DB::raw("SUM(a.monto) AS total"),
          DB::raw("SUM(a.monto_rendido) AS rendido")
        ])
        ->whereIn('b.tipo', ['Bienes', 'Servicios'])
        ->where('geco_proyecto_id', '=', $request->query('id'))
        ->first();

      if ($rendido->total <= $rendido->rendido) {
        $porcentaje = 100;
        $actualizarEstado= DB::table('Geco_proyecto')
          ->where('id', '=', $request->query('id'))
          ->update([
            'estado' => 1
          ]);
      } else {
        $porcentaje = round(($rendido->rendido / $rendido->total) * 100, 2);
        
        $actualizarEstado= DB::table('Geco_proyecto')
          ->where('id', '=', $request->query('id'))
          ->update([
            'estado' => 0
          ]);
      }

      $partidas = DB::table('Geco_proyecto_presupuesto AS a')
        ->join('Partida AS b', 'b.id', '=', 'a.partida_id')
        ->where('a.geco_proyecto_id', '=', $request->query('id'))
        ->where('b.tipo', '!=', 'Otros')
        ->orderBy('b.tipo')
        ->count();

      $comprobantes_aprobados = DB::table('Geco_documento')
        ->where('geco_proyecto_id', '=', $request->query('id'))
        ->where('estado', '=', 1)
        ->count();

      $transferencias_aprobadas = DB::table('Geco_operacion')
        ->where('geco_proyecto_id', '=', $request->query('id'))
        ->where('estado', '=', 1)
        ->count();

      //  Asignación económica
      $asignacion = DB::table('Geco_proyecto_presupuesto AS a')
        ->leftJoin('Partida AS b', 'b.id', '=', 'a.partida_id')
        ->select([
          'b.codigo',
          'b.tipo',
          'b.partida',
          'a.monto',
          'a.monto_rendido_enviado',
          DB::raw("LEAST(a.monto, a.monto_rendido) AS monto_rendido"),
          'a.monto_excedido'
        ])
        ->where('a.geco_proyecto_id', '=', $request->query('id'))
        ->where('b.tipo', '!=', 'Otros')
        ->orderBy('b.tipo')
        // ->where(function ($query) {
        //   $query
        //     ->orWhere('a.partida_nueva', '!=', '1')
        //     ->orWhereNull('a.partida_nueva');
        // })
        ->get();

      //  Comprobantes
      $comprobantes = DB::table('Geco_documento AS a')
        ->join('Geco_proyecto AS b', 'b.id', '=', 'a.geco_proyecto_id')
        ->leftJoin('Geco_documento_file AS c', 'c.geco_documento_id', '=', 'a.id')
        ->select(
          'a.id',
          'a.tipo',
          'a.numero',
          'a.fecha',
          'a.total_declarado',
          DB::raw("CONCAT('/minio/geco-documento/', c.key) AS url"),
          DB::raw('CASE 
            WHEN a.estado = 1 THEN "Aprobado"
            WHEN a.estado = 2 THEN "Rechazado"
            WHEN a.estado = 3 THEN "Observado"
            WHEN a.estado = 4 THEN "Enviado"
            WHEN a.estado = 5 THEN "Anulado"
            ELSE "Desconocido"
          END AS estado'),
          'a.observacion'
        )
        ->where('b.id', '=', $request->query('id'))
        ->get();

      //  Transferencia o presupuesto
      $presupuesto = [];
      $transferenciaPendiente = DB::table('Geco_operacion')
        ->where('geco_proyecto_id', '=', $request->query('id'))
        ->whereIn('estado', [3, 4])
        ->count();

      if ($transferenciaPendiente == 0) {
        $presupuesto = DB::table('Geco_proyecto_presupuesto AS a')
          ->join('Partida AS b', 'b.id', '=', 'a.partida_id')
          ->select([
            'b.tipo',
            'b.codigo',
            'b.partida',
            'a.monto',
          ])
          ->where('a.geco_proyecto_id', '=', $request->query('id'))
          ->get();
      } else {
        $operacion = DB::table('Geco_operacion')
          ->select([
            'id'
          ])
          ->where('geco_proyecto_id', '=', $request->query('id'))
          ->orderByDesc('created_at')
          ->first();

        $movimientos = DB::table('Geco_operacion_movimiento')
          ->select([
            'geco_proyecto_presupuesto_id',
            'operacion',
            'monto',
          ])
          ->where('geco_operacion_id', '=', $operacion->id)
          ->get();

        $presupuesto = DB::table('Geco_proyecto_presupuesto AS a')
          ->join('Partida AS b', 'b.id', '=', 'a.partida_id')
          ->select([
            'a.id',
            'b.tipo',
            'b.codigo',
            'b.partida',
            'a.monto',
            DB::raw("0 AS monto_nuevo")
          ])
          ->where('a.geco_proyecto_id', '=', $request->query('id'))
          ->get()
          ->map(function ($item) use ($movimientos) {
            $monto_nuevo = $item->monto;
            foreach ($movimientos as $movimiento) {
              if ($movimiento->geco_proyecto_presupuesto_id == $item->id) {
                if ($movimiento->operacion == "+") {
                  $monto_nuevo = $monto_nuevo + $movimiento->monto;
                } else {
                  $monto_nuevo = $monto_nuevo - $movimiento->monto;
                }
              }
            }
            $item->monto_nuevo = $monto_nuevo;
            return $item;
          });
      }



      return [
        'cifras' => [
          'rendido' => $porcentaje,
          'partidas' => $partidas,
          'comprobantes' => $comprobantes_aprobados,
          'transferencias' => $transferencias_aprobadas
        ],
      ];
   
  }


  public function recalcularMontos(Request $request)
  {
    //  Cálculos previos - Eliminar cuando todo esté normal
    $this->limpiarMontos($request->query('geco_proyecto_id'));
    $this->calcularEnviado($request->query('geco_proyecto_id'));
    $this->calcularRendicion($request->query('geco_proyecto_id'));
    $this->calcularExceso($request->query('geco_proyecto_id'));

    return ['message' => 'success', 'detail' => 'Montos rendidos, excedidos y de envío actualizados'];
  }

  /**
   * Métodos para el cálculo de las operaciones al aprobar y enviar comprobantes
   * - limpiarMontos:
   * ELimina los valores de las columnas monto_rendido, monto_rendido_enviado y
   * monto_excedido, este método se debe ejecutar previo al resto para volver a
   * calcular todo de manera correcta en base a los comprobantes y presupuesto
   * actual
   * 
   * - calcularRendicion: 
   * Calcula el valor de la columna monto_rendido en base a las partidas de los
   * comprobantes aprobados de un proyecto (ESTADO = 1).
   * 
   * - calcularEnviado:
   * Calcula el valor de la columna monto_rendido_enviado en base a las partidas
   * de los comprobantes enviados por los docentes en un proyecto (ESTADO = 4).
   * 
   * - calcularExceso:
   * Calcula el valor de la columna monto_excedido en base a la siguiente operación
   * siempre y cuando su resultado sea > a 0.
   * (monto_rendido + monto_rendido_enviado) - monto 
   */

  public function limpiarMontos($geco_proyecto_id)
  {
    DB::table('Geco_proyecto_presupuesto')
      ->where('geco_proyecto_id', '=', $geco_proyecto_id)
      ->update([
        'monto_rendido_enviado' => 0,
        'monto_rendido' => 0,
        'monto_excedido' => 0,
      ]);

    return true;
  }

  public function calcularRendicion($geco_proyecto_id)
  {
    $comprobantes = DB::table('Geco_documento AS a')
      ->join('Geco_documento_item AS b', 'b.geco_documento_id', '=', 'a.id')
      ->select([
        DB::raw("SUM(b.total) AS monto_rendido"),
        'b.partida_id',
      ])
      ->where('geco_proyecto_id', '=', $geco_proyecto_id)
      ->where('estado', '=', 1)
      ->groupBy('b.partida_id')
      ->get();

    foreach ($comprobantes as $item) {
      DB::table('Geco_proyecto_presupuesto')
        ->where('geco_proyecto_id', '=', $geco_proyecto_id)
        ->where('partida_id', '=', $item->partida_id)
        ->update([
          'monto_rendido' => $item->monto_rendido
        ]);
    }
  }

  public function calcularEnviado($geco_proyecto_id)
  {
    $comprobantes = DB::table('Geco_documento AS a')
      ->join('Geco_documento_item AS b', 'b.geco_documento_id', '=', 'a.id')
      ->select([
        DB::raw("SUM(b.total) AS monto_rendido_enviado"),
        'b.partida_id',
      ])
      ->where('geco_proyecto_id', '=', $geco_proyecto_id)
      ->where('estado', '=', 4)
      ->groupBy('b.partida_id')
      ->get();

    foreach ($comprobantes as $item) {
      DB::table('Geco_proyecto_presupuesto')
        ->where('geco_proyecto_id', '=', $geco_proyecto_id)
        ->where('partida_id', '=', $item->partida_id)
        ->update([
          'monto_rendido_enviado' => $item->monto_rendido_enviado
        ]);
    }
  }

  public function calcularExceso($geco_proyecto_id)
  {
    $presupuesto = DB::table('Geco_proyecto_presupuesto')
      ->select([
        'id',
        'monto',
        'monto_rendido_enviado',
        'monto_rendido',
      ])
      ->where('geco_proyecto_id', '=', $geco_proyecto_id)
      ->get();

    foreach ($presupuesto as $item) {
      $exceso = 0;

      if (($item->monto_rendido_enviado + $item->monto_rendido) > $item->monto) {
        $exceso = ($item->monto_rendido_enviado + $item->monto_rendido) - $item->monto;
      }

      DB::table('Geco_proyecto_presupuesto')
        ->where('id', '=', $item->id)
        ->update([
          'monto_excedido' => $exceso
        ]);
    }
  }

  /**
   * AUDITORÍA:
   * Dentro de la columna audit (JSON almacenado como string) se guardarán
   * los cambios de estado por parte de los administradores.
   */

  public function agregarAudit(Request $request)
  {
    $documento = DB::table('Geco_documento')
      ->select([
        'audit'
      ])
      ->where('id', '=', $request->input('geco_documento_id'))
      ->first();

    $audit = json_decode($documento->audit ?? "[]");

    $audit[] = [
      'fecha' => Carbon::now()->format('Y-m-d H:i:s'),
      'estado' => $request->input('estado')["value"],
      'nombre' => $request->attributes->get('token_decoded')->nombre . " " . $request->attributes->get('token_decoded')->apellidos
    ];

    $audit = json_encode($audit, JSON_UNESCAPED_UNICODE);

    DB::table('Geco_documento')
      ->where('id', '=', $request->input('geco_documento_id'))
      ->update([
        'audit' => $audit
      ]);
  }

  public function verAuditoria(Request $request)
  {
    $documento = DB::table('Geco_documento')
      ->select([
        'audit'
      ])
      ->where('id', '=', $request->query('id'))
      ->first();

    $audit = json_decode($documento->audit ?? "[]");

    return $audit;
  }
}
