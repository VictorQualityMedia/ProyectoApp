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

function filtrarJornadas() {
  $("#jornadas_previas").remove();
  $("#bton-admin").remove();
  $("#horas").remove();

  let inicio = document.getElementById("dateInicio")
    ? document.getElementById("dateInicio").value
    : null;
  let fin = document.getElementById("dateFin")
    ? document.getElementById("dateFin").value
    : null;
  if (!fin) {
    fin = inicio;
  }
  let cod = null;
  if (typeof datosIniciales !== "undefined") {
    cod = datosIniciales.codigo_usuario;
  } else {
    cod = "";
  }

  if ($("#mes-inicio").length > 0) {
    let mes = $("#mes-inicio").val();
    let anIn = $("#agno-inicio").val();
    inicio = anIn + "-" + mes + "-01";

    mes = $("#mes-fin").val();
    anIn = $("#agno-fin").val();
    let fecha = new Date(anIn + "-" + mes);
    fecha = new Date(fecha.getFullYear(), fecha.getMonth() + 1, 0);
    console.log(fecha);
    fin = moment(fecha).format("YYYY-MM-DD");
  }

  $.ajax({
    url: ruta_filtrar_jornada + `/${inicio}/${fin}/${cod}`,
    type: "GET",
    dataType: "json",
    async: true,
    beforeSend: function () {
      $("#loadingDiv").show();
    },
    success: function (data) {
      console.log(data);
      $("#loadingDiv").hide();
      if (data.jornadas.length) {
        let horas_normales = 24 * data.normal.days + data.normal.h;
        let horas_nocturnas = 24 * data.nocturno.days + data.nocturno.h;
        let horas_totales = 24 * data.total.days + data.total.h;
        let horas_mensuales =
          data.horas_laborables.days * 24 + data.horas_laborables.h;

        let div = $("<div><div>");
        $(div).attr("id", "horas");
        $(div).append(
          `<div><strong>Normales:</strong> ${horas_normales}:${data.normal.i}<div>`
        );
        $(div).append(
          `<div><strong>Nocturnas:</strong> ${horas_nocturnas}:${data.nocturno.i}<div>`
        );
        $(div).append(
          `<div><strong>Recuperadas:</strong> ${
            data.recuperadas.days * 24 + data.recuperadas.h
          }:${data.recuperadas.i}</div>`
        );
        // RECUPERAR

        $.ajax({
          url: ruta_horas_recuperar,
          type: "POST",
          dataType: "json",
          async: false,
          data: {
            id: cod,
          },
          //success: function (data) {
            //console.log(data);
            //$(div).append(
              //`<div><strong>Exceso:</strong> ${data.h ? data.h : 0}:${
                //data.m ? data.m : 0
               //}<div>`
            //);
          //},
          error: function (errorThrown) {
            console.log(errorThrown);
          },
        });
        $(div).append(
          `<div><strong>Totales:</strong> ${horas_totales}:${data.total.i}<div>`
        );
        $(div).append(
          `<div><strong>A trabajar:</strong> ${horas_mensuales} (${
            data.por_trabajar.invert ? "Faltan: " : "Sobran: "
          } ${ Math.abs((data.por_trabajar.days * 24 + data.por_trabajar.h)-(data.recuperadas.days * 24 + data.recuperadas.h)) } horas, ${Math.abs(
            data.por_trabajar.i-data.recuperadas.i)
          } minutos)</div><div id='info-jornadas'><i class="fa-solid fa-circle-info" style="color: #9c0707;"></i> <div class='esconder' id='mensaje'><p><strong>Normales:</strong> horas trabajadas excepto las nocturnas/festivos/extra</p><p><strong>Nocturnas:</strong> horas trabajadas entre las 22:00 PM y 6:00 AM</p><p><strong>Recuperadas:</strong> cantidad de horas extra que se han recuperado.</p><p><strong>Totales:</strong> número de horas totales trabajadas durante el periodo seleccionado.</p><p><strong>A trabajar:</strong> número de horas que te corresponden trabajar durante el periodo seleccionado.</p></div></div>`
        );
        $("#datos-jornadas").append($(div));
        $("#info-jornadas").mouseenter(() =>
          $("#mensaje").toggleClass("esconder")
        );
        $("#info-jornadas").mouseleave(() =>
          $("#mensaje").toggleClass("esconder")
        );
        visualizarJornadas(data.jornadas);
      }
    },
    error: function (errorThrown) {
      console.log(errorThrown);
    },
  });
}

if ($("#dateInicio")) {
  $("#dateInicio").val(moment().format("YYYY-MM-DD"));
}

if ($("#dateFin")) {
  $("#dateFin").val(moment().format("YYYY-MM-DD"));
}

if (!$("#izquierda")) {
  filtrarJornadas();
}

$("#dateFin").change(filtrarJornadas);
$("#dateInicio").change(filtrarJornadas);

$("select:not([id])").change(() => {
  if ($("#dateFin").css("display") == "none") {
    $("#dateFin").css("display", "inline");
  } else {
    $("#dateFin").css("display", "none");
    $("#dateFin").val(null);
  }
  filtrarJornadas();
});
// Funciones

function crearTablaImprimir(data) {
  $("#tabla-datos").empty();
  $("#tabla-datos").append(
    "<tr><th>Fecha</th><th>Tipo</th><th>Entrada - Salida</th><th>Total</th></tr>"
  );
  data.forEach((element) => {
    let fila = $(`<tr></tr>`);
    fila.append(
      $(
        `<td>${element.fecha.date.substring(0, 10)}</td><td>${element.tipo}/${
          element.confirmado
        }</td>`
      )
    );

    let tablaEntradas = $("<table></table>");
    element.entradas.forEach((e) => {
      let tr = $("<tr></tr>");
      tr.append($(`<td>${e.entrada}</td><td>${e.salida}</td>`));
      tablaEntradas.append(tr);
    });
    fila.append(tablaEntradas);
    fila.append($(`<td>${formartTiempo(element.tiempo)}</td>`));
    $("#tabla-datos").append(fila);
  });
}

function visualizarJornadas(data) {
  $("#jornadas_previas").remove();
  div = $("<div></div>");
  div.attr("id", "jornadas_previas");
  data.forEach((element) => {
    let table = $(`<table class="entradas"></table>`);
    table.attr("id", element.id);
    table.append(
      $(`<tr><td colspan="2">${element.fecha.date.substring(0, 10)}</td></tr>`)
    );
    table.append(
      $(`<tr><td>${element.tipo}</td><td>${element.confirmado}</td></tr>`)
    );
    table.append(
      $(
        "<tr><td><strong>Entrada</strong></td><td><strong>Salida</strong></td></tr>"
      )
    );
    element.entradas.forEach((entrada) => {
      entrada.entrada = entrada.entrada.date.substring(11, 19);
      let salida = "";
      if (entrada.salida) {
        salida = entrada.salida.date.substring(0, 16);
        entrada.salida = entrada.salida.date.substring(11, 19);
      }
      let trEntrada = $(`<tr id="${entrada.id}"></tr>`);
      trEntrada.append(
        `<td>${entrada.entrada}</td><td id="${salida}">${entrada.salida}</td></td>`
      );
      table.append(trEntrada);
    });
    table.append(
      $(
        `<tr><td><strong>Total: </strong></td><td>${formartTiempo(
          element.tiempo
        )}</td><td></td></tr>`
      )
    );
    table.append(
      $(
        `<tr><td colspan="2"><strong>Comentario</strong></td><tr><tr><td colspan="3">${element.observaciones}</td></tr>`
      )
    );
    div.append(table);
  });
  
  
  if (typeof departamento_usuario !== 'undefined') {
    if (rol_usu == "ROLE_SUPERADMIN" || (rol_usu == "ROLE_ADMIN" && departamento_usuario == datosIniciales.dept_inicial)) {
      $("#buscar-datos")
        .first()
        .append(
          $(`<div id="bton-admin"><button id='modificar'>Modificar</button><div>`)
        );
        
        
    }
  } else {
    if (rol_usu == "ROLE_SUPERADMIN" || (rol_usu == "ROLE_ADMIN") || (rol_usu == "ROLE_USER")) {
      $("#buscar-datos")
      .first()
      .append(
        $(`<div id="bton-admin"><button id='modificar'>Modificar</button><div>`)
      );
      
    }
  }

  cambiar_color();
  
  
  
  $("#datos-jornadas").append(div);
  $("#modificar").on("click", function () {
    let tipo_jonada;
    $.ajax({
      url: tipo_Jornada,
      type: "GET",
      dataType: "json",
      async: false,
      success: function (data) {
        tipo_jonada = data;
      },
      error: function (errorThrown) {},
    });

    $(".entradas>tbody").each(function (i, table) {
      $(table).children("tr").eq(0).children().removeAttr("colspan");
      $(table)
        .children("tr")
        .eq(0)
        .append(
          `<td><button><img src="${imagen_papelera}" alt="papelera">Jornada</button></td>`
        );

      $(".entradas button").on("click", function () {
        $(this).parent().parent().parent().parent().css("display", "none");
      });

      let tipo = $(table).children("tr").eq(1).children().first().html();
      let aprobado = $(table).children("tr").eq(1).children().last().html();
      let selectTipoJornada = $("<select></select>");

      if (tipo != "Fin de semana" && tipo != "Festivo") {
        tipo_jonada.forEach((element) => {
          let option = $(`<option>${element.nombre}</option>`);
          option.attr("value", element.id);
          if (tipo == element.nombre) {
            option.attr("selected", true);
          }
          selectTipoJornada.append(option);
        });
      } else {
        let festivo = $(`<option>${tipo}</option>`);
        festivo.attr("selected", true);
        festivo.attr("disabled", true);
        selectTipoJornada.append(festivo);
      }


      let selectAprobado = $("<select></select>");

if (rol_usu != "ROLE_USER") {
      $("#buscar-datos") 
      let opAprobado, opPendiente;
      opAprobado = $('<option value="1">Aprobado</option></select>');
      opPendiente = $('<option value="0">Pendiente</option></select>');
      if (aprobado == "Aprobado") {
        opAprobado.attr("selected", true);
      } else {
        opPendiente.attr("selected", true);
      }
      selectAprobado.append(opAprobado);
      selectAprobado.append(opPendiente);
}
      

      $(table).children("tr").eq(1).children().first().empty();
      $(table)
        .children("tr")
        .eq(1)
        .children()
        .first()
        .append(selectTipoJornada);
      $(table).children("tr").eq(1).children().last().empty();
      $(table).children("tr").eq(1).children().last().append(selectAprobado);
    });
    $(".entradas tbody > tr[id]").each(function (i, tr) {
      // ...
      let input = $("<input type='time'>");
      $(input).attr("value", $(tr).children().first().html().substr(0, 5));

      let input2 = $("<input type='datetime-local'>");
      $(input2).attr("value", $(tr).children().last().attr("id"));

      let button = $(
        `<td><button class="eliminarFila"><img src="${imagen_papelera}" alt="papelera"></button></td>`
      );

      if($(tr).children().last().html()){
       $(tr).children().first().html(input);
       $(tr).children().last().html(input2);
       $(tr).append(button);
     }
    });
    $(".eliminarFila").click(function () {
      $(this).parent().parent().css("display", "none");
    });

    butonAceptar = $("<button>Aceptar</buton>");
    butonCancelar = $("<button>Cancelar</buton>");
    $("#bton-admin").empty();
    $("#bton-admin").append(butonAceptar);
    $("#bton-admin").append(butonCancelar);

    butonAceptar.on("click", modificarJornadas);
    butonCancelar.on("click", filtrarJornadas);
  });
  crearTablaImprimir(data);
}

function modificarJornadas() {
  let jornadas = [];
  $(".entradas").each(function (i, table) {
    let jornada = [];
    let id = $(".entradas").eq(i).attr("id");
    let tipo = $(table)
      .children()
      .children("tr")
      .eq(1)
      .children()
      .children()
      .first()
      .val();
    let aprobado = $(table)
      .children()
      .children("tr")
      .eq(1)
      .children()
      .children()
      .last()
      .val();
    let eliminar = $(".entradas").eq(i).css("display") == "none";

    let entradas = [];
    $(table)
      .children()
      .children("[id]")
      .each(function (i, tr) {
        let id = $(tr).attr("id");
        let inicio = $(tr).children().eq(0).children().val();
        let fin = $(tr).children().eq(1).children().val();
        let eliminar = $(tr).css("display") == "none";
        entradas.push([id, inicio, fin, eliminar]);
      });
    jornada.push(id, tipo, aprobado, entradas, eliminar);
    jornadas.push(jornada);
  });
  $.ajax({
    url: ruta_editar_jornada,
    type: "POST",
    data: {
      jornadas: jornadas,
    },
    dataType: "json",
    async: false,
    success: function (data) {
      console.log(data);
    },
    error: function (errorThrown) {
      console.log(errorThrown);
    },
  });

  filtrarJornadas();
}

function formartTiempo(element) {
  if (element != "") {
    h = element.days * 24;
    h += element.h;
    m = element.i;
    s = element.s;
    if (!(s + "").length == 2) {
      s = "0" + s;
    }
    if (s >= 60) {
      m += s / 60;
      s = s % 60;
    }
    if (m >= 60) {
      h += m / 60;
      m = m % 60;
    }

    if (h < 10) {
      h = "0" + h;
    }

    if (m < 10) {
      m = "0" + m;
    }

    m = Math.round(m);

    if (s < 10) {
      s = "0" + s;
    }

    element = horas = h + ":" + m + ":" + s;
  }
  return element;
}

if ($("#dateInicio").length > 1 || $("#agno-inicio").length > 1) {
  filtrarJornadas();
}