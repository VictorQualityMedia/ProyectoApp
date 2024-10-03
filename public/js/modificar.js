const cargarDepartamentos = () => {
  $.ajax({
    url: ruta_cargar_dept,
    method: "GET",
    dataType: "json",
    async: true,
    success: function (data) {
      data.forEach((element) => {
        let option = $(
          `<option value="${element.id}">${element.nombre}</option>`
        );
        if (element.id == datosIniciales.dept_inicial) {
          option.attr("selected", "selected");
        }
        if (element.id != 0 || datosIniciales.rol_inicial == 2) {
          $("#mod_dept").append(option);
        }
      });
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
    if (element.codigo == datosIniciales.rol_inicial) {
      option.attr("selected", "selected");
    }
    $("#mod_rol").append(option);
  });
};


// Seleccionar el contrato actual
$("#mod_contrato").val(datosIniciales.contrato_inicial);
$("#mod_horario").val(datosIniciales.horario_inicial);
if ($("#mod_contrato").val() == 2) {
  $("#mod_contrato").after(
    $(
      `<input type="text" id="horas_personal" value='${datosIniciales.personal}'/>`
    )
  );
  $("#horas_personal").on("keyup", (e) => {
    if (!isFinite(e.key) && e.key != 'Backspace') {
      $("#horas_personal").val($("#horas_personal").val().slice(0, -1));
    } 
  });
}
$("#mod_contrato").change(function () {
  $("#horas_personal").remove();
  console.log($(this).val());
  if ($(this).val() == 2) {
    console.log("entro");
    $("#mod_contrato").after(
      $(
        `<input type="text" id="horas_personal" value="${datosIniciales.personal}" placeholder="Horas Mensuales Ej: 160">`
      )
    );
    $("#horas_personal").on("keyup", (e) => {
      if (!isFinite(e.key) && e.key != 'Backspace') {
        $("#horas_personal").val($("#horas_personal").val().slice(0, -1));
      } 
    });
  }
});
cargarDepartamentos();
cargarRoles();

// Event listeners

$("#btn_cambiar").click(() => {
  // no enviar dni o correo si no se ha cambiado
  let mod_dni, mod_correo, mod_dept, mod_pass, mod_horario, mod_contrato,inicio_contrato;
  if ($("#mod_dni").val() == datosIniciales.dni_inicial) {
    mod_dni = null;
  } else {
    mod_dni = $("#mod_dni").val();
  }

  if ($("#mod_contrato").val() == datosIniciales.contrato_inicial) {
    mod_contrato = null;
  } else {
    mod_contrato = $("#mod_contrato").val();
    if ($("#mod_contrato").val() == null && $("#mod_contrato").length > 0) {
      $("#izquierda").append(
        $(
          `<p id='respuesta' style='color: red; font-weight: bold; font-size: 28px'>Indique el tipo de contrato!</p>`
        )
      );
      function esconder() {
        $("#respuesta").remove();
      }
      setTimeout(esconder, 3000);
      return;
    }
  }

  if ($("#mod_horario").val() == datosIniciales.horario_inicial) {
    mod_horario = null;
  } else {
    mod_horario = $("#mod_horario").val();
    if ($("#mod_horario").val() == null &&  $("#mod_horario").length > 0) {
      $("#izquierda").append(
        $(
          `<p id='respuesta' style='color: red; font-weight: bold; font-size: 28px'>Indique el tipo de horario!</p>`
        )
      );
      function esconder() {
        $("#respuesta").remove();
      }
      setTimeout(esconder, 3000);
      return;
    }
  }
     const fechaInicial = new Date(datosIniciales.inicio_contrato);
  const year = fechaInicial.getFullYear();
  const month = String(fechaInicial.getMonth() + 1).padStart(2, '0');
  const day = String(fechaInicial.getDate()).padStart(2, '0');
  const inicioContrato = `${year}-${month}-${day}`;

  if ($("#fecha_contrato").val() == inicioContrato) {
    inicio1 = inicio_contrato;
  } else {
    inicio1 = $("#fecha_contrato").val();
    if ($("#fecha_contrato").val() == null && $("#fecha_contrato").length > 0) {
      $("#izquierda").append(
        $(
          `<p id='respuesta' style='color: red; font-weight: bold; font-size: 28px'>Indique el tipo de fecha!</p>`
        )
      );
      function esconder() {
        $("#respuesta").remove();
      }
      setTimeout(esconder, 3000);
      return;
    }
  }
    


  if ($("#mod_pass") != "") {
    mod_pass = $("#mod_pass").val();
  } else {
    mod_pass = null;
  }

  if (mod_dni != null && !comprobarDni(mod_dni)) {
    alert("DNI/NIE no válido!");
  } else {
    if ($("#mod_correo").val() == datosIniciales.correo_inicial) {
      mod_correo = null;
    } else {
      mod_correo = $("#mod_correo").val();
    }

    if ($("#mod_dept").val() == null) {
      mod_dept = datosIniciales.dept_inicial;
    } else {
      mod_dept = $("#mod_dept").val();
    }

  

    $.ajax({
      url: ruta_boton_cambiar,
      method: "POST",
      dataType: "json",
      data: {
        cod_usu: datosIniciales.codigo_usuario,
        mod_usu: $("#mod_usu").val(),
        mod_correo: mod_correo,
        mod_dni: mod_dni,
        mod_rol: $("#mod_rol").val(),
        mod_departamento: mod_dept,
        mod_pass: mod_pass,
        mod_horario: $("#mod_horario").val(),
        mod_contrato: $("#mod_contrato").val(),
        horas_personal: $("#horas_personal").length == 1? $("#horas_personal").val(): 0 ,
        mod_inicio: inicio1,
      },
      async: true,
      success: function (data) {
        $("#respuesta").remove();
        if (!data.error) {
          $("#izquierda").append(
            $(
              "<p id='respuesta' style='color: green; font-weight: bold; font-size: 28px'>Cambios guardados!</p>"
            )
          );
        } else {
          $("#izquierda").append(
            $(
              `<p id='respuesta' style='color: red; font-weight: bold; font-size: 28px'>${data.error}</p>`
            )
          );
        }
        function esconder() {
          $("#respuesta").remove();
        }
        setTimeout(esconder, 3000);
      },
      error: function (errorThrown) {
        console.log(errorThrown);
      },
    });
  }
});

$("#btn_cancelar").click(() => {
  $("#mod_usu").val(datosIniciales.nombre_inicial);
  $("#mod_correo").val(datosIniciales.correo_inicial);
  $("#mod_dni").val(datosIniciales.dni_inicial);
  $("#mod_rol").val(datosIniciales.rol_inicial);
  $("#mod_dept").val(datosIniciales.dept_inicial);
  $("#mod_horario").val(datosIniciales.horario_inicial);
  $("#mod_contrato").val(datosIniciales.contrato_inicial);
});

$("#btn-imprimir").click(() => {
  window.print();
});

$("#btn_borrar").click(() => {
  let c = confirm("Está seguro de que quiere borrar el usuario?");
  if (c) {
    $.ajax({
      url: ruta_borrar_usuario,
      method: "POST",
      dataType: "json",
      data: {
        id_a_borrar: datosIniciales.codigo_usuario,
      },
      async: true,
      success: function (data) {
        window.location.href = "/admin";
      },
      error: function (errorThrown) {
        console.log(errorThrown);
      },
    });
  }
});

$("#mes-inicio,#agno-inicio,#mes-fin,#agno-fin").each(function () {
  $(this).on("change", () => {
    filtrarJornadas();
  })
});

// Funciones

const comprobarDni = (dni) => {
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
};