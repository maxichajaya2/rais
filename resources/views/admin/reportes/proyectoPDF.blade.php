@php
  $tituloActual = '';
  $numTituloActual = 1;
  $firstEl = 0;
  $currentTipo = '';
@endphp
<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta http-equiv="X-UA-Compatible" content="ie=edge">
  <title>Reporte</title>
  {{-- <style>
    * {
      font-family: Helvetica;
    }

    @page {
      margin: 165px 20px 20px 20px;
    }

    .head-1 {
      position: fixed;
      top: -135px;
      left: 0px;
      height: 90px;
    }

    .head-1 img {
      margin-left: 120px;
      height: 85px;
    }

    .head-2 {
      position: fixed;
      top: -135px;
      right: 0;
    }

    .head-2 p {
      text-align: right;
    }

    .head-2 .rais {
      font-size: 11px;
      margin-bottom: 0;
    }

    .head-2 .fecha {
      font-size: 5px;
      margin-top: 0;
    }

    .head-2 .user {
      font-size: 11px;
      margin-top: 0;
    }

    .foot-1 {
      position: fixed;
      bottom: 0px;
      left: 0px;
      text-align: left;
      font-size: 11px;
      font-style: oblique;
    }

    .div {
      position: fixed;
      top: -45px;
      width: 100%;
      height: 0.5px;
      background: #000;
    }

    .titulo {
      width: 754px;
      font-size: 16px;
      text-align: center;
    }

    .texto {
      font-size: 13px;
      margin: 20px 0;
    }

    .table1 {
      width: 100%;
      border-collapse: separate;
    }

    .table1>tbody {
      border-bottom: 1.5px solid #000;
    }

    .table1>thead {
      margin-top: -1px;
      font-size: 10px;
      border-top: 1.5px solid #000;
      border-bottom: 1.5px solid #000;
    }

    .table1>thead th {
      font-weight: normal;
    }

    .table1>tbody td {
      font-size: 10px;
      text-align: center;
      padding-top: 2px;
    }

    .table2 {
      width: 100%;
      border-collapse: separate;
      margin-bottom: 40px;
    }

    .table2>thead {
      margin-top: -1px;
      font-size: 10px;
      border-bottom: 1.5px solid #000;
    }

    .table2>thead th {
      text-align: left;
      font-weight: normal;
    }

    .table2>tbody td {
      font-size: 10px;
      padding-top: 2px;
    }

    .extra-1 {
      font-size: 11px;
      text-align: left;
      width: 100%;
    }

    .extra-2 {
      font-size: 11px;
      text-align: right;
      width: 100%;
    }

    .extra-firma {
      font-size: 11px;
      text-align: center;
      width: 100%;
    }

    .titulo_proyecto {
      font-weight: bold;
      font-style: oblique;
    }

    .nom_grupo {
      background: #D7DFDF;
      font-size: 10px;
      padding: 2px;
      margin: 0 1px;
      border-top: 1.5px solid #000;
    }

    .nom_grupo>p {
      margin: 1px 5px;
    }

    .fac_grupo {
      font-size: 10px;
      margin: 2px;
      text-align: right;
    }
  </style> --}}
  <style>
    * {
        font-family: Arial, sans-serif;
    }

    .header-table {
        width: 100%;
        border-collapse: collapse;
        table-layout: fixed;
        margin-bottom: 20px;
    }

    .header-table td {
        vertical-align: middle;
        /* Centra el contenido verticalmente */
    }

    .header-left {
        width: 14%;
        /* Espacio fijo para la izquierda */
        text-align: left;
        /* Alineación a la izquierda */
        font-size: 10px;
    }

    .header-center {
        width: 72%;
        /* Espacio amplio para la imagen */
        text-align: center;
        /* Centra el contenido */
    }

    .header-right {
        width: 14%;
        /* Espacio fijo para la derecha */
        text-align: right;
        /* Alineación a la derecha */
        font-size: 10px;
    }

    .header-center img {
        max-width: 100%;
        max-height: 100px;
        /* Controla la altura de la imagen */
        object-fit: contain;
        /* Evita la deformación */
    }

    .cuerpo-table {
        width: 100%;
        text-align: center;
        border-collapse: collapse;
        table-layout: fixed;
        margin-bottom: 20px;
    }

    .title {
        font-size: 20px;
        text-align: center;
        margin-top: 20px;
        margin-bottom: 20px;
        color: #0a0a84;
    }

    .table-texto,
    .table-texto3 {
        width: 100%;
        font-size: 12px;
        text-align: justify;
    }

    .table-texto3 {
        margin-bottom: 20px;
    }

    .table-texto2 {
        width: 100%;
        table-layout: fixed;

    }

    .table-texto2 td {
        text-align: left;
        font-size: 12px;

    }

    .table-texto2 td:first-child {
        text-align: right;
    }

    .table-footer {
        width: 100%;
        text-align: center;
        margin-top: 90px;

    }

    .table-content {
        width: 100%;
        font-size: 11px;
        margin-bottom: 10px;
        border-collapse: collapse;
    }

    .table-content thead th {
        border-top: 1px solid black;
        /* Línea superior en el encabezado */
        border-bottom: 1px solid black;
        /* Línea inferior en el encabezado */
        padding: 10px;
        text-align: left;
    }

    .table-content tbody td {
        border-bottom: 1px dashed black;
        padding: 8px;

    }

    .table-content tbody tr:last-child td {
        border-bottom: 1px solid black;
        /* Línea inferior más gruesa en la última fila */
    }

    .extra-1,
    .extra-2,
    {
    font-size: 12px;

    }

    .extra-firma {
        font-size: 14px;
    }

    .foot-1 {
        position: fixed;
        bottom: -20px;
        left: 0px;
        text-align: left;
        font-size: 10px;
    }
</style>
</head>

<body>
  
  <table class="header-table">
    <tr>
        <td class="header-left">
            <span>Fecha: {{ date('d/m/Y') }}</span><br>
            <span>Hora: {{ date('H:i:s') }}</span>
        </td>
        <td class="header-center">
            <img src="{{ public_path('head-pdf.jpg') }}" alt="Header">
        </td>
        <td class="header-right">
            <span>© RAIS</span><br>
            <span>Usuario: ichajaya</span>
        </td>
    </tr>
</table>

<table class="cuerpo-table">
    <tr class="title">
        {{-- <td><b>{{ $tipo .' '. $periodo}}</b></td> --}}
    </tr>
</table>
<table>
  <tr>
    {{-- <td>Área : {{ $facultad}}</td> --}}
  </tr>
</table>


</body>
<body>
  

  {{-- <div class="cuerpo">
    @foreach ($lista as $item)
      @if ($tituloActual != $item->titulo)
        @if ($firstEl == 1)
          </tbody>
          </table>
        @endif
        @php
          $firstEl = 1;
        @endphp
        <div class="fac_grupo"><strong>Facultad:</strong> {{ $item->facultad_grupo }}</div>
        <div class="nom_grupo">
          <p>Nombre del grupo: <strong>{{ $item->grupo_nombre }}</strong></p>
        </div>
        <table class="table1">
          <thead>
            <tr>
              <th style="width: 5%;">Nro.</th>
              <th style="width: 10%;">Código</th>
              <th style="width: 65%;">Título del proyecto</th>
              <th style="width: 10%;">Presupuesto</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td>{{ $numTituloActual }}</td>
              <td>{{ $item->codigo_proyecto }}</td>
              <td class="titulo_proyecto">{{ $item->titulo }}</td>
              <td>{{ $item->presupuesto }}</td>
            </tr>
          </tbody>
        </table>
        <table class="table2">
          <thead>
            <tr>
              <th>Condición/Código</th>
              <th>Nombre</th>
              <th>Tipo</th>
              <th>Facultad</th>
              <th>Condición en GI</th>
            </tr>
          </thead>
          <tbody>
            @php
              $numTituloActual++;
            @endphp
      @endif
      @if ($currentTipo != $item->condicion)
        <tr>
          <td>{{ $item->condicion }}</td>
          <td></td>
          <td></td>
          <td></td>
          <td></td>
        </tr>
      @endif
      <tr>
        <td>{{ $item->codigo }}</td>
        <td>{{ $item->nombres }}</td>
        <td>{{ $item->tipo }}</td>
        <td>{{ $item->facultad_miembro }}</td>
        <td>{{ $item->condicion_gi }}</td>
      </tr>
      @php
        $currentTipo = $item->condicion;
        $tituloActual = $item->titulo;
      @endphp
    @endforeach
  </div> --}}

  <script type="text/php">
    if (isset($pdf)) {
      $x = 527;
      $y = 818;
      $text = "Página {PAGE_NUM} de {PAGE_COUNT}";
      $font = $fontMetrics->get_font("Helvetica", "Italic");
      $size = 8;
      $color = array(0,0,0);
      $word_space = 0.0;  //  default
      $char_space = 0.0;  //  default
      $angle = 0.0;   //  default
      $pdf->page_text($x, $y, $text, $font, $size, $color, $word_space, $char_space, $angle);
    }
  </script>
</body>

</html>
