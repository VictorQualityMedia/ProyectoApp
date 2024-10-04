// Leer la cookie de color
let color = getCookie("color");

// Si la cookie de color existe, aplicamos el color a los botones
function cambiar_color () {
  if (color) {
    let buttons = document.querySelectorAll("button");
    buttons.forEach(function(button) {
        button.style.backgroundColor = color;
    });
  }
}

cambiar_color();


$("#link-administracion").addClass("active");
if (window.innerHeight > window.innerWidth) {
  $("#link-administracion").detach().insertBefore("a:first");
}

const cargarDatos = () => {
  $.ajax({
    url: ruta_buscar_usuario,
    type: "GET",
    dataType: "json",
    async: true,
    success: function (data) {
      $("table tr:nth-child(n+2)").remove();
      mostrarUsuarios(data.usuarios);
      if (data.departamentos) {
        $("#lista_dept").empty();
        $("#dept_a_borrar").empty();
        data.departamentos.forEach((element) => {
          let dept = $(
            `<option value="${element.nombre}">${element.nombre}</option>`
          );
          $("#lista_dept").append(dept);
          $("#dept_a_borrar").append(dept);
        });
      }
    },
    error: function (errorThrown) {
      console.log(errorThrown);
    },
  });
};

const cargarRoles = () => {
  let roles = [
    { codigo: 0, nombre: "Empleado" },
    { codigo: 1, nombre: "Administrador" },
    { codigo: 2, nombre: "Superadministrador" },
  ];
  roles.forEach((element) => {
    let option = $(
      `<option value="${element.codigo}">${element.nombre}</option>`
    );
    $("#mod_rol").append(option);
  });
};

// Event listeners

// Botones principales

$("#btn-crear").click(() => {
  $("#btn-crear, #btn-gestionar").removeClass("activo");
  $("#btn-crear").addClass("activo");
  $("#botones").siblings().remove();
  $("main").append(
    $(`<div id="div-crear">
    <div class="botones">
        <button id="btn_crear_usu">USUARIOS</button>
        <button id="btn_crear_dept">DEPART.</button>
        <button id="btn_crear_festivo">FESTIVOS</button>
        <button id="btn_configuracion">CONFIGURACION</button>
    </div>
    <div id="formulario"></div>
</div>`)
  );
  // La cambiamos también aquí porque son elementos "nuevos".
  cambiar_color();

  $("#btn_crear_usu").click(() => {
    $("#div-crear button").removeClass("activo");
    $("#btn_crear_usu").addClass("activo");
    $("#formulario").empty();
    let formulario = $(
      "<h2>Nuevo Usuario</h2><form method='POST' action='#'  id='form_usu'><input id='usu_nom' type='text' placeholder='Nombre...' required><input id='usu_mail' type='email' placeholder='Correo...' ><input type='password' id='clave_usu' placeholder='Password...' required><input id='usu_dni' type='text' placeholder='DNI...'><input list='lista_dept' id='usu_dept' type='text' placeholder='Departamento...' required><label for='mod_rol'>Rol:</label><select id='mod_rol'></select><label for='tipo_contrato'>Tipo contrato:</label><select id='tipo_contrato'><option value='0'>Contrato parcial</option><option value='1' selected>Contrato normal</option><option value='2'>Personal</option></select><label for='fecha_contrato'>Inicio contrato:</label><input type='date' id='fecha_contrato'><label for='tipo_horario'>Horario:</label><select id='tipo_horario'><option value='0'>L-V</option><option value='1'>X-D</option><option value='2'>L-D (rotatorio)</option></select><input type='submit' value='Guardar'></form>"
    );
    $("#formulario").append(formulario);
    $("#fecha_contrato").val(moment().format('YYYY-MM-DD'));
    cargarDatos();
    cargarRoles();

    $("#tipo_contrato").change(function (e) {
      e.preventDefault();
      $("#horas_personal").remove();
      if ($(this).val() == 2) {
        $(this).after(
          `<input type="number" id="horas_personal" placeholder="Horas Mensuales Ej: 160" />`
        );
      }
    });
    $("#form_usu").submit((e) => {
      e.preventDefault();
      if (!comprobarDni($("#usu_dni").val())) {
        $("#formulario").append(
          $(
            `<p id='respuesta' style='color: red; font-weight: bold; font-size: 28px'>DNI/NIE inválido!</p>`
          )
        );
        function esconder() {
          $("#respuesta").remove();
        }
        setTimeout(esconder, 2000);
      } else {
        // borrar error/resultado previo
        $("#error").remove();
        $("#exito").remove();
        $.ajax({
          url: ruta_crear_usuario,
          method: "POST",
          dataType: "json",
          data: {
            nom_usu: $("#usu_nom").val(),
            email_usu: $("#usu_mail").val(),
            dni_usu: $("#usu_dni").val(),
            clave_usu: $("#clave_usu").val(),
            nom_dept: $("#usu_dept").val(),
            rol: $("#mod_rol").val(),
            contrato: $("#tipo_contrato").val(),
            inicio_contrato: $("#fecha_contrato").val(),
            horas_personal: $("#horas_personal").length == 1? $("#horas_personal").val():0 ,
            horario: $("#tipo_horario").val(),
          },
          async: true,
          success: function (data) {
            if (data.error) {
              $("#formulario").append(
                $(
                  `<p id='error' style='color: red; font-weight: bold; font-size: 28px'>${data.error}</p>`
                )
              );
            } else {
              $("#formulario").append(
                $(
                  `<p id='exito' style='color: green; font-weight: bold; font-size: 28px'>${data.exito}</p>`
                )
              );
              cargarDatos();
            }
          },
          error: function (errorThrown) {
            console.log(errorThrown);
          },
        });
      }
    });
  });

  $("#btn_crear_dept").click(() => {
    $("#div-crear button").removeClass("activo");
    $("#btn_crear_dept").addClass("activo");
    $("#formulario").empty();
    let formulario = $(
      "<h2>Nuevo Departamento</h2><input id='nom_dept' type='text' placeholder='Nombre...'><button id='form_dept'>Guardar</button><h2>Borrar Departamento</h2><select id='dept_a_borrar'></select><button id='btn_borrar_dept'>Borrar</button>"
    );
    $("#formulario").append(formulario);
    $("#dept_a_borrar").append($("<option>Indique el departamento</option>"));
    cargarDatos();

    $("#btn_borrar_dept").click(() => {
      if ($("#dept_a_borrar").val() == "Indique el departamento") {
        $("#formulario").append(
          $(
            `<p id='respuesta' style='color: red; font-weight: bold; font-size: 28px'>Indique un departamento!</p>`
          )
        );
      } else {
        $.ajax({
          url: ruta_borrar_dept,
          method: "POST",
          dataType: "json",
          data: {
            dept: $("#dept_a_borrar").val(),
          },
          async: true,
          success: function (data) {
            $("#formulario").append(
              $(
                `<p id='respuesta' style='color: green; font-weight: bold; font-size: 28px'>Departamento borrado!</p>`
              )
            );
            $("#dept_a_borrar").empty();
            $("#dept_a_borrar").append(
              $("<option>Indique el departamento</option>")
            );
            cargarDatos();
          },
          error: function (errorThrown) {
            console.log(errorThrown);
          },
        });
      }

      function esconder() {
        $("#respuesta").remove();
      }
      setTimeout(esconder, 2000);
    });

    $("#form_dept").click(() => {
      if ($("#nom_dept").val().length > 0) {
        $.ajax({
          url: ruta_crear_departamento,
          method: "POST",
          dataType: "json",
          data: {
            nom_dept: $("#nom_dept").val(),
          },
          async: true,
          success: function (data) {
            if (data.error) {
              $("#formulario").append(
                $(
                  `<p id='respuesta' style='color: red; font-weight: bold; font-size: 28px'>${data.error}</p>`
                )
              );
            } else {
              $("#formulario").append(
                $(
                  `<p id='respuesta' style='color: green; font-weight: bold; font-size: 28px'>${data.exito}</p>`
                )
              );
              cargarDatos();
            }
            function esconder() {
              $("#respuesta").remove();
            }
            setTimeout(esconder, 2000);
          },
          error: function (errorThrown) {
            console.log(errorThrown);
          },
        });
      } else {
        $("#formulario").append(
          $(
            `<p id='respuesta' style='color: red; font-weight: bold; font-size: 28px'>Indique el nombre del departamento!</p>`
          )
        );
        function esconder() {
          $("#respuesta").remove();
        }
        setTimeout(esconder, 2000);
      }
    });
  });

  $("#btn_crear_festivo").click(() => {
    $("#div-crear button").removeClass("activo");
    $("#btn_crear_festivo").addClass("activo");
    let fechas = [];
    $("#formulario").empty();
    let datos = $(
      "<h2>Nuevo Festivo</h2><input type='date' id='fecha-festivo'><button id='btn-anadir'>Seleccionar</button>Lista de fechas:<ul id='lista-fechas'></ul><button id='crear-festivos'>Guardar</button><h2>Borrar Festivo</h2><input type='date' id='fecha_borrar'><button id='btn-borrar-festivo'>Borrar</button>"
    );
    $("#formulario").append(datos);

    $("#btn-anadir").click(() => {
      if (
        !fechas.includes($("#fecha-festivo").val()) &&
        $("#fecha-festivo").val().length == 10
      ) {
        fechas.push($("#fecha-festivo").val());
        $("#lista-fechas").empty();
        fechas.forEach((fecha) => {
          $("#lista-fechas").append(
            $(
              `<li>${fecha}<button class="borrar-fecha" data-id="${fecha}" style='height: 25px; width: 60px; margin-left: 5px;'>borrar</button></li>`
            )
          );
        });

        $(".borrar-fecha").click(function () {
          let index = fechas.indexOf($(this).data("id"));
          fechas.splice(index, 1);
          $(this).parent().remove();
        });
      }
    });

    $("#btn-borrar-festivo").click(() => {
      $.ajax({
        url: ruta_borrar_festivo,
        method: "POST",
        dataType: "json",
        data: {
          festivo: $("#fecha_borrar").val(),
        },
        async: true,
        success: function (data) {
          $("#formulario").append(
            $(
              `<p id='respuesta' style='color: green; font-weight: bold; font-size: 28px'>Festivo borrado!</p>`
            )
          );
          function esconder() {
            $("#respuesta").remove();
          }
          setTimeout(esconder, 2000);
        },
        error: function (errorThrown) {
          console.log(errorThrown);
        },
      });
    });

    $("#crear-festivos").click(() => {
      fechas.forEach((fecha) => {
        $.ajax({
          url: ruta_crear_festivo,
          method: "POST",
          dataType: "json",
          data: {
            fecha_inicio: fecha,
          },
          async: true,
          success: function (data) {
            fechas = [];
            $("#lista-fechas").empty();
          },
          error: function (errorThrown) {
            console.log(errorThrown);
          },
        });
      });
      if (fechas.length > 0) {
        $("#formulario").append(
          $(
            `<p id='respuesta' style='color: green; font-weight: bold; font-size: 28px'>Festivos creados!</p>`
          )
        );
        function esconder() {
          $("#respuesta").remove();
        }
        setTimeout(esconder, 2000);
      }
    });
  });

  // Añadir event listener para el botón de Configuración
  $("#btn_configuracion").click(() => {
    // Remover la clase activo de los otros botones y añadir al botón actual
    $("#div-crear button").removeClass("activo");
    $("#btn_configuracion").addClass("activo");

    // Vaciar el formulario para cargar el nuevo contenido
    $("#formulario").empty();

    // Añadir el contenido del formulario de configuración
    // let formulario = $(
    //   `<h2>Configuración logo</h2>
    //   <label for="logo" class="custom-file-label">Cambia tu logo seleccionando un archivo:</label>
        
    //     <!-- Solo el input de archivo -->
    //     <input id="logo" name="logo" type="file" required class="file-input">

    //     <!-- Botón para subir -->
    //     <button type="submit" class="submit-btn" id="form_logo">Guardar cambios</button>`
    // );

    // Añadir el contenido del formulario de configuración
    let formulario2 = $(
      `<h2>Configuración colores</h2>
        <label for="color">Cambia el color de loos botones:</label>
        
        <!-- Solo el input de archivo -->
        <input id="color" name="color" type="color" required>

        <!-- Botón para subir -->
        <button type="submit" class="submit-btn" id="form_color">Guardar cambios</button>`
    );

    // Insertar el formulario en el div correspondiente
    // $("#formulario").append(formulario);
    $("#formulario").append(formulario2);
    console.log("Probando.");

    // Manejar el envío del formulario
    $("#form_color").click(() => {
      console.log("Probando.");

      // Recoger los valores de los inputs
      let color_input = $("#color").val();
      console.log(color_input);

      // Realizar el envío de los datos mediante AJAX (ejemplo de envío)
      $.ajax({
        url: ruta_color, // Ruta de configuración en el servidor
        method: "POST",
        dataType: "json",
        data: {
          color: color_input
        },
        async: true,
        success: function (data) {
          // Mostrar mensajes de éxito o error
          if (data.error) {
            $("#formulario").append(
              $(
                `<p id='respuesta' style='color: red; font-weight: bold; font-size: 28px'>${data.error}</p>`
              )
            );
          } 
          else {
            $("#formulario").append(
              $(
                `<p id='respuesta' style='color: green; font-weight: bold; font-size: 28px'>${data.exito}</p>`
              )
            );
            cargarDatos();
          }

          function esconder() {
            $("#respuesta").remove();
          }
          setTimeout(esconder, 2000);
        },
        // Entra aqui, trata de ver por qué
        error: function (errorThrown) {
          console.log(errorThrown);
        },
      });
      window.location.reload();
    });
  });
});

$("#btn-gestionar").click(() => {
  $("#btn-crear, #btn-gestionar").removeClass("activo");
  $("#btn-gestionar").addClass("activo");
  $("#botones").siblings().remove();
  $("main").append(
    $(`<div id="div-gestionar">
    <h2>Buscar</h2>
    <div id="filtros">
        <div id='input-filtro'>
        <input id="busca_usu" type="text" placeholder="Usuario...">
        </div>
        <h2>Lista de Usuarios</h2>
        <table></table>
    </div>`)
  );

  if (es_superadmin) {
    $("#busca_usu").after(
      $(
        `<input list="lista_dept" id="busca_dept" placeholder="Departamento...">`
      )
    );
  }

  cargarDatos();

  $("#busca_usu").keyup(() => {
    $.ajax({
      url: ruta_buscar_usuario,
      type: "POST",
      dataType: "json",
      data: {
        buscar_nombre: $("#busca_usu").val(),
        buscar_departamento: $("#busca_dept").val(),
      },
      async: true,
      success: function (data) {
        $("table tr:nth-child(n+2)").remove();
        mostrarUsuarios(data.usuarios);
      },
      error: function (errorThrown) {
        console.log(errorThrown);
      },
    });
  });

  $("#busca_dept").keyup(() => {
    $.ajax({
      url: ruta_buscar_usuario,
      type: "POST",
      dataType: "json",
      data: {
        buscar_nombre: $("#busca_usu").val(),
        buscar_departamento: $("#busca_dept").val(),
      },
      async: true,
      success: function (data) {
        $("table tr:nth-child(n+2)").remove();
        mostrarUsuarios(data.usuarios);
      },
      error: function (errorThrown) {
        console.log(errorThrown);
      },
    });
  });
});

// Funciones

const mostrarUsuarios = (usuarios) => {
  $("table").empty();

  if (!usuarios) {
    $("table").append($("<h2>No se ha encontrado ningún usuario</h2>"));
  } else {
    let cabecera = $(`<tr>
            <th>Nombre</th>
            <th>Departamento</th>
        </tr>`);

    $("table").append(cabecera);
    usuarios.forEach((element) => {
      let rol;
      switch (element.rol) {
        case 1:
          rol = "ADMINISTRADOR";
          break;
        case 2:
          rol = "SUPERADMINISTRADOR";
          break;
        default:
          rol = "EMPLEADO";
          break;
      }
      let tr = $(`<tr>
            <td><form action="${ruta_modificar_usuario}" method="POST"><input class='id_usu' name='hidden' type='hidden' value=${element.id}><button type='submit' class='btn-modificar''>${element.nombre}</button> </form>  </td>
                <td>${element.departamento}</td>
                </tr>`);
      $("table").append(tr);
    });
  }
};

function comprobarDni(dni) {
  let numero, letra, letr;
  let expresion_regular = /^[XYZ]?\d{5,8}[A-Z]$/;

  dni = dni.toUpperCase();

  if (expresion_regular.test(dni)) {
    numero = dni.substr(0, dni.length - 1);
    numero = numero.replace("X", 0);
    numero = numero.replace("Y", 1);
    numero = numero.replace("Z", 2);

    letr = dni.substr(dni.length - 1, 1);
    numero = numero % 23;
    letra = "TRWAGMYFPDXBNJZSQVHLCKET";
    letra = letra.substring(numero, numero + 1);

    if (letra == letr) {
      return true;
    }
  }
  return false;
}


// Función para obtener el valor de una cookie
function getCookie(name) {
    let cookieArr = document.cookie.split(";");
    for(let i = 0; i < cookieArr.length; i++) {
        let cookiePair = cookieArr[i].split("=");
        if(name == cookiePair[0].trim()) {
            return decodeURIComponent(cookiePair[1]);
        }
    }
    return null;
}

