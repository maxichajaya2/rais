<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Lineas de investigación</title>
  @vite(['resources/css/app.css','resources/js/app.js'])
</head>

<body>
  @include('admin.components.navbar')
  <hr>
  <div class="mx-auto max-w-screen-xl p-4">
    <!--  Tabs  -->
    <div class="mb-4 border-b border-gray-200 dark:border-gray-700">
      <ul class="flex flex-wrap -mb-px text-sm font-medium text-center" id="default-tab" data-tabs-toggle="#default-tab-content" role="tablist">
        <li class="me-2" role="presentation">
          <button class="inline-block p-4 border-b-2 rounded-t-lg" id="listado-tab" data-tabs-target="#tab_listado" type="button" role="tab" aria-controls="tab_listado" aria-selected="false">Lista de lineas</button>
        </li>
        <li class="me-2" role="presentation">
          <button class="inline-block p-4 border-b-2 rounded-t-lg hover:text-gray-600 hover:border-gray-300 dark:hover:text-gray-300" id="crear-tab" data-tabs-target="#tab_crear" type="button" role="tab" aria-controls="tab_crear" aria-selected="false">Crear linea nueva</button>
        </li>
      </ul>
    </div>
    <div id="default-tab-content">
      <div class="hidden p-4 rounded-lg bg-gray-50 dark:bg-gray-800" id="tab_listado" role="tabpanel" aria-labelledby="profile-tab">
        <!--  Seleccionar facultad  -->
        <form class="max-w-sm mx-auto">
          <div class="mb-5">
            <label for="facultad" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Facultad:</label>
            <select id="facultad" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500">
              <option value="null" selected>Ninguna</option>
              @foreach($facultades as $facultad)
              <option value="{{ $facultad->id }}">{{ $facultad->nombre }}</option>
              @endforeach
            </select>
          </div>
        </form>
        <!--  Tabla de lineas de investigación  -->
        <div class="relative overflow-x-auto shadow-md sm:rounded-lg">
          <table id="lineas_table" class="w-full text-sm text-left rtl:text-right text-gray-500 dark:text-gray-400">
            <thead class="text-xs text-gray-700 uppercase dark:text-gray-400">
              <tr>
                <th scope="col" class="px-6 py-3 bg-gray-50 dark:bg-gray-800">
                  Código
                </th>
                <th scope="col" class="px-6 py-3">
                  Linea de investigación
                </th>
                <th scope="col" class="px-6 py-3 bg-gray-50 dark:bg-gray-800">
                  Resolución
                </th>
                <th scope="col" class="px-6 py-3">
                  Expandir
                </th>
              </tr>
            </thead>
            <tbody>
              @foreach($lineas as $linea)
              <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700">
                <th scope="row" class="px-6 py-4 font-medium text-gray-900 whitespace-nowrap dark:text-white bg-gray-50 dark:bg-gray-800">
                  {{ $linea->codigo }}
                </th>
                <td class="px-6 py-4">
                  {{ $linea->nombre }}
                </td>
                <td class="px-6 py-4 bg-gray-50 dark:bg-gray-800">
                  {{ $linea->resolucion }}
                </td>
                <td class="px-6 py-4 text-center">
                  Expandir
                </td>
              </tr>
              @endforeach
            </tbody>
          </table>
          <nav aria-label="Page navigation example">
            <ul class="inline-flex -space-x-px text-base h-10">
              <li>
                <a href="#" class="flex items-center justify-center px-4 h-10 ms-0 leading-tight text-gray-500 bg-white border border-e-0 border-gray-300 rounded-s-lg hover:bg-gray-100 hover:text-gray-700 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white">Previous</a>
              </li>
              <li>
                <a href="#" class="flex items-center justify-center px-4 h-10 leading-tight text-gray-500 bg-white border border-gray-300 hover:bg-gray-100 hover:text-gray-700 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white">1</a>
              </li>
              <li>
                <a href="#" class="flex items-center justify-center px-4 h-10 leading-tight text-gray-500 bg-white border border-gray-300 hover:bg-gray-100 hover:text-gray-700 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white">2</a>
              </li>
              <li>
                <a href="#" aria-current="page" class="flex items-center justify-center px-4 h-10 text-blue-600 border border-gray-300 bg-blue-50 hover:bg-blue-100 hover:text-blue-700 dark:border-gray-700 dark:bg-gray-700 dark:text-white">3</a>
              </li>
              <li>
                <a href="#" class="flex items-center justify-center px-4 h-10 leading-tight text-gray-500 bg-white border border-gray-300 hover:bg-gray-100 hover:text-gray-700 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white">4</a>
              </li>
              <li>
                <a href="#" class="flex items-center justify-center px-4 h-10 leading-tight text-gray-500 bg-white border border-gray-300 hover:bg-gray-100 hover:text-gray-700 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white">5</a>
              </li>
              <li>
                <a href="#" class="flex items-center justify-center px-4 h-10 leading-tight text-gray-500 bg-white border border-gray-300 rounded-e-lg hover:bg-gray-100 hover:text-gray-700 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-400 dark:hover:bg-gray-700 dark:hover:text-white">Next</a>
              </li>
            </ul>
          </nav>
        </div>
      </div>
      <div class="hidden p-4 rounded-lg bg-gray-50 dark:bg-gray-800" id="tab_crear" role="tabpanel" aria-labelledby="dashboard-tab">
        <!--  Crear nueva linea de investigación  -->
        <form action="{{ route('create_linea') }}" method="post" class="max-w-md mx-auto">
          @csrf
          <div class="mb-5">
            <label for="create_facultad" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Facultad:</label>
            <select id="create_facultad" name="facultad_id" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500">
              <option value="">Ninguna</option>
              @foreach($facultades as $facultad)
              <option value="{{ $facultad->id }}">{{ $facultad->nombre }}</option>
              @endforeach
            </select>
          </div>
          <div class="mb-5">
            <label for="create_padre" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Padre:</label>
            <select id="create_padre" name="parent_id" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500">
              <option value="">Cargando...</option>
            </select>
          </div>
          <div class="mb-5">
            <label for="codigo" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Código:</label>
            <input type="text" id="codigo" name="codigo" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500">
          </div>
          <div class="mb-5">
            <label for="linea" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Linea:</label>
            <input type="text" id="nombre" name="nombre" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500">
          </div>
          <div class="mb-5">
            <label for="resolucion" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Resolución:</label>
            <input type="text" id="resolucion" name="resolucion" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-700 dark:border-gray-600 dark:placeholder-gray-400 dark:text-white dark:focus:ring-blue-500 dark:focus:border-blue-500">
          </div>
          <button type="submit" class="text-white w-full bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800">Guardar</button>
        </form>
      </div>
    </div>
  </div>
  <script type="module">
    $(document).ready(function() {
      let state = false;
      $("#tab_crear").on('click', function() {
        if (!state) {
          $.ajax({
            url: "http://localhost:8000/ajaxGetLineasInvestigacion/",
            method: "GET",
            success: (data) => {
              let select = $("#create_padre");
              select.html("");
              select.append(
                $("<option>", {
                  value: "",
                  text: "Ninguno",
                  selected: true,
                })
              );
              $.each(data, (index, item) => {
                let option = $("<option>", {
                  value: item.id,
                  text: item.codigo + " " + item.nombre,
                });
                select.append(option);
              });
            },
          });
          state = true;
        }
      });

      // $('#create_facultad').change(() => {
      //   //  Indicar carga
      //   $("#create_padre").html("").append(
      //     $("<option>", {
      //       value: "null",
      //       text: "Cargando...",
      //       selected: true,
      //     })
      //   ).prop('disabled', true);
      //   //  Petición al servidor
      //   $.ajax({
      //     url: "http://localhost:8000/ajaxGetLineasInvestigacionFacultad/" + $("#create_facultad").val(),
      //     method: "GET",
      //     success: (data) => {
      //       let select = $("#create_padre");
      //       select.prop('disabled', false);
      //       select.html("");
      //       select.append(
      //         $("<option>", {
      //           value: "null",
      //           text: "Ninguno",
      //           selected: true,
      //         })
      //       );
      //       $.each(data, (index, item) => {
      //         let option = $("<option>", {
      //           value: item.id,
      //           text: item.nombre,
      //         });
      //         select.append(option);
      //       });
      //     },
      //   });
      // });
    })
  </script>
</body>

</html>