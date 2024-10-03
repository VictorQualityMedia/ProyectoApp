let tipoJornada = null;
let laborable = true;
let tipoJornadaManual = "Normal";
let interval = null;
// para comida
let minutos = 60;
$("#link-jornadas").addClass("active");
if (window.innerHeight > window.innerWidth) {
  $("#link-jornadas").detach().insertBefore("a:first");
}

// Comprobar si ya existe una entrada no finalizada para este usuario
const comprobarEntrada = () => {
  $.ajax({
    url: ruta_inicio_control,
    type: "GET",
    dataType: "json",
    async: true,
    beforeSend: function () {
      $("#loadingDiv").show();
    },
    success: function (data) {
      $("#loadingDiv").hide();
      if (data.length != 0 && !data.completado) {
        interval = setInterval(displayTimer, 1000);
      }
    },
    error: function (errorThrown) {
      console.log(errorThrown);
    },
  });
};

const comprobarJornada = () => {
  $.ajax({
    url: ruta_comprobar_jornada,
    type: "GET",
    dataType: "json",
    async: true,
    beforeSend: function () {
      $("#loadingDiv").show();
    },
    success: function (data) {
      $("#loadingDiv").hide();
      if (data.tipo == "no laborable") {
        laborable = false;
      } else {
        laborable = true;
      }

      if (laborable) {
        $("#tipo-jornada").after(
          $(`<div id="contador">
            <p id="tiempo"></p>
            <button id="btn-iniciar">Iniciar</button>
        </div>`)
        );

        if (interval != null) {
          $("#tiempo").html("");
          $("#btn-iniciar").html("Finalizar");
        } else {
          $("#tiempo").html("00:00:00");
        }

        $("#btn-iniciar").click(() => {
          $("#observaciones").remove();
          $("#p-comida").remove();
          $("#comida").remove();
          $("#btn-observaciones").remove();
          if (tipoJornada == "Normal" || tipoJornada == "laborable") {
            if ($("#btn-iniciar").text() == "Iniciar") {
              peticionIniciar();

              $("#jornada-manual").css("display", "none");
              if (interval == null) {
                interval = setInterval(displayTimer, 1000);
                $("#btn-iniciar").css({ "background-color": "#9c0707" });
                $("#btn-iniciar").text("Finalizar");
              }
            } else {
              $("#tipo-jornada").css("display", "block");
              $("#jornada-manual").css("display", "block");
              $("#crear-jornada").css("display", "none");
              clearInterval(interval);
              interval = null;
              peticionFinalizar();
              $("#btn-iniciar").css({ "background-color": "#9c0707" });
              $("#btn-iniciar").text("Iniciar");
              // observaciones
              let observaciones = $(
                "<textarea id='observaciones' rows='7' cols='80' placeholder='Observaciones...'></textarea><button style='margin-bottom: 10px;' id='btn-observaciones'>Guardar</button>"
              );
              $("#contador").append(observaciones);
              cargarObservacion();

              $("#observaciones").before(
                $(
                  "<p id='p-comida'>¿Has comido? <input type='checkbox' id='check-comida'></p>"
                )
              );

              $("#check-comida").click(() => {
                console.log(122);
                if ($("#comida").length == 0) {
                  $("#btn-iniciar").after(
                    $(
                      "<div id='comida'><p>Inicio comida: <input type='datetime-local' id='hora-inicio-comida'> Fin comida: <input type='datetime-local' id='hora-fin-comida'></p></div>"
                    )
                  );
                  $("#hora-inicio-comida,#hora-fin-comida").val(
                    moment().format('YYYY-MM-DDTHH:mm')
                    
                  );
                } else {
                  $("#comida").remove();
                }
              });

              $("#btn-observaciones").click(() => {
                $("#p-comida").remove();
                let inicioComida = 0;
                let finComida = 0;
                let horaActual =
                  new Date().getHours() + ":" + new Date().getMinutes();
                if ($("#hora-inicio-comida").length > 0) {
                  inicioComida = $("#hora-inicio-comida").val();
                }
                if ($("#hora-fin-comida").length > 0) {
                  finComida = $("#hora-fin-comida").val();
                }
                if (inicioComida > finComida) {
                  alert("Indica unas horas de comida válidas!");
                  return;
                }

                $.ajax({
                  url: ruta_guardar_observacion,
                  type: "POST",
                  data: {
                    observacion: $("#observaciones").val(),
                    inicio_comida: inicioComida,
                    fin_comida: finComida,
                  },
                  dataType: "json",
                  async: true,
                  success: function (data) {
                    $("#observaciones").after(
                      $(
                        "<p id='respuesta' style='color: green; font-weight: bold; font-size: 28px'>Observacion guardada!</p>"
                      )
                    );
                    $("#comida").remove();
                    $("#tiempo").text(`00:00:00`);

                    function esconder() {
                      $("#respuesta").remove();
                    }
                    setTimeout(esconder, 3000);
                    $("#observaciones, #btn-observaciones").remove();
                  },
                  error: function (errorThrown) {
                    console.log(errorThrown);
                  },
                });
              });
            }
          } else {
            $.ajax({
              url: ruta_crear_jornada_manual,
              type: "POST",
              data: {
                fecha_inicio: $("#fecha-inicio").val(),
                fecha_fin: $("#fecha-fin").val(),
                tipo_jornada: tipoJornada,
              },
              dataType: "json",
              async: true,
              success: function (data) {},
              error: function (errorThrown) {
                console.log(errorThrown);
              },
            });
          }
        });
      } else {
        $("#contenedor").append(
          $(
            `<h2 id='aviso-jornada'>No puedes inicializar una entrada! (${data.mensaje})</h2>`
          )
        );
      }
    },
    error: function (errorThrown) {
      console.log(errorThrown);
    },
  });
};

$("#fecha-inicio").val(moment().format("YYYY-MM-DD HH:mm"));

// Comprobar si ha empezado una entrada para continuar con el contador
comprobarEntrada();

// Event Listeners

$("#btn-normal").click(function () {
  // Comprobar si hoy tiene jornada laborable
  // css
  tipoJornada = "laborable";
  $(this).addClass("activo");
  $("#btn-tus-jornadas, #btn-manual").removeClass("activo");
  // borrar contenidos
  $(
    "#jornada-manual, #datos-jornadas, #contador, #observaciones, #btn-observaciones, #aviso-jornada, #horas-extra"
  ).remove();
  // añadir contenido si la jornada no es baja/vacaciones
  comprobarJornada();
});

$("#btn-manual").click(function () {
  // css
  $(this).addClass("activo");
  $("#btn-tus-jornadas, #btn-normal").removeClass("activo");
  // borrar contenido
  $(
    "#jornada-manual, #datos-jornadas, #contador, #observaciones, #btn-observaciones, #aviso-jornada, #horas-extra"
  ).remove();
  // añadir contenido
  $("#tipo-jornada").after(
    $(`<div id="jornada-manual">
    <h3>Registra una jornada manualmente:</h3>
    <div id='btn-jornada-manual'>
    <button>Normal</button>
    <button>Recuperar</button>
    <button>Vacaciones</button>
    <button>Baja</button>
    <i class="info fa-solid fa-circle-info" style="color: #9c0707;"></i><div class="informacion"><div>Normal: iniciar una jornada anterior</div><div>Recuperar: recuperar tus horas</div><div>Vacaciones, Baja</div>
    </div>
    
</div>`)
  );

  $("#btn-jornada-manual button").click((e) => {
    $("#datetimes, #crear-jornada").remove();
    $("#btn-jornada-manual").after(
      $(`<div id="datetimes">
        <div id="fechas">
            <label for="fecha-inicio">Inicio:</label>
            <input type="datetime-local" id="fecha-inicio">
            <label for="fecha-fin">Fin:</label>
            <input type="datetime-local" id="fecha-fin">
        </div>
        <div id="obs-manual">
            <label for="observaciones-manual">Observaciones:</label>
            <textarea id="observaciones-manual" cols="40" rows="5"></textarea>
        </div>
    </div>
    <button id="crear-jornada">Guardar Jornada</button>`)
    );
    cargarObservacionManual();
    $("#fechas input").on("change", () => {
      cargarObservacionManual();
    });
    $("#btn-jornada-manual button").removeClass("activo");
    $("#btn-jornada-manual button").css("border", "none");
    $(e.target).addClass("activo");
    $(e.target).css("border", "1px solid black");

    tipoJornadaManual = e.target.innerText;
    if (tipoJornadaManual != "Normal") {
      $("#datetimes input").attr("type", "date");
      $("#fecha-inicio").val(moment().format("YYYY-MM-DD"));
      $("#fecha-fin").val(moment().format("YYYY-MM-DD"));
      if (tipoJornadaManual == "Recuperar") {
        $("#fecha-fin").prev().remove();
        $("#fecha-fin").remove();
        $("#fechas").remove();
        $("#obs-manual").remove();
        $("#datetimes").append(
          $(
            `<div><label for='fecha-inicio'>Inicio:</label><input type='datetime-local' id='fecha-inicio'><label for='fecha-fin'>Fin:</label><input type='datetime-local' id='fecha-fin'></div>`
          )
        );
      }
    } else {
      $("#fecha-inicio").val(
        moment().subtract(10, "minutes").format("YYYY-MM-DD HH:mm")
      );
      $("#fecha-fin").val(moment().format("YYYY-MM-DD HH:mm"));
      $("#obs-manual").before(
        $("<p>¿Has comido? <input type='checkbox' id='check-comida'></p>")
      );
      $("#check-comida").click(() => {
        if ($("#comida").length == 0) {
          $("#obs-manual").before(
            $(
              "<div id='comida'><p>Inicio comida: <input type='datetime-local' id='hora-inicio-comida'> Fin comida: <input type='datetime-local' id='hora-fin-comida'></p></div>"
            )
          );
          $("#hora-inicio-comida,#hora-fin-comida").val(
            $("#fecha-inicio").val()
          //  moment().format("HH:mm")
          );

        } else {
          $("#comida").remove();
        }
      });
    }

    $("#crear-jornada").click(() => {
      let recuperar = null;
      if (tipoJornadaManual == "Recuperar") {
        recuperar = "Recuperar";
      }
      if ($("#fecha-fin").val() != "") {
        let inicioComida = 0;
        let finComida = 0;
        let horaActual = new Date().getHours() + ":" + new Date().getMinutes();
        if ($("#hora-inicio-comida").length > 0) {
          inicioComida = $("#hora-inicio-comida").val();
        }
        if ($("#hora-fin-comida").length > 0) {
          finComida = $("#hora-fin-comida").val();
        }

        if (
          inicioComida > finComida || $("#fecha-inicio").val() > inicioComida || $("#fecha-fin").val() < finComida
        ) {
          alert("Indica unas horas de comida válidas!");
          return;
        }

        $.ajax({
          url: ruta_crear_jornada_manual,
          type: "POST",
          data: {
            fecha_inicio: $("#fecha-inicio").val(),
            fecha_fin: $("#fecha-fin").val(),
            tipo_jornada: tipoJornadaManual,
            observaciones: $("#observaciones-manual").val(),
            inicio_comida: inicioComida,
            fin_comida: finComida,
            recuperar: recuperar,
          },
          dataType: "json",
          async: true,
          success: function (data) {
            console.log(data);
            if (data.completado) {
              $("#datetimes").after(
                $(
                  "<p id='respuesta' style='color: green; font-weight: bold; font-size: 18px'>Jornada guardada!</p>"
                )
              );
            } else {
              if (data.tipo == 1) {
                $("#datetimes").after(
                  $(
                    "<p id='respuesta' style='color: red; font-weight: bold; font-size: 18px'>Tiempo de entrada mínimo: 10 minutos!</p>"
                  )
                );
              } else if (data.tipo == 0) {
                $("#datetimes").after(
                  $(
                    "<p id='respuesta' style='color: red; font-weight: bold; font-size: 18px'>Conflicto entre entradas!</p>"
                  )
                );
              } else if (data.error) {
                $("#datetimes").after(
                  $(
                    `<p id='respuesta' style='color: red; font-weight: bold; font-size: 18px'>${data.error}</p>`
                  )
                );
              } else {
                $("#datetimes").after(
                  $(
                    "<p id='respuesta' style='color: red; font-weight: bold; font-size: 18px'>No puedes añadir entradas posteriores a la actual!</p>"
                  )
                );
              }
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
        $("#datetimes").after(
          $(
            "<p id='respuesta' style='color: red; font-weight: bold; font-size: 18px'>Indica la segunda fecha!</p>"
          )
        );
        function esconder() {
          $("#respuesta").remove();
        }
        setTimeout(esconder, 3000);
      }
    });
  });
});

// Click en el boton para ver las jornadas previas
$("#btn-tus-jornadas").click(function () {
  // css
  $(this).addClass("activo");
  $("#btn-normal, #btn-manual").removeClass("activo");

  // borrar contenido de otros botones
  $(
    "#jornada-manual, #datos-jornadas, #contador, #observaciones, #btn-observaciones, #aviso-jornada, #horas-extra"
  ).remove();
  // añadir contenido
  $("#tipo-jornada").after(
    $(`<div id="datos-jornadas">
    <div id='buscar-datos'>
        <div>Buscar:
            <select>
                <option value="rango">Rango</option>
                <option value="dia">Dia</option>
            </select>
        </div>


        <div>
            <input type="date" id="dateInicio">
            <input type="date" name="end" id="dateFin">
        </div>
    </div>
</div>`)
  );
  document.getElementById("dateInicio").valueAsDate = new Date();
  document.getElementById("dateFin").valueAsDate = new Date();
  filtrarJornadas();

  $("#dateFin").change(filtrarJornadas);
  $("#dateInicio").change(filtrarJornadas);

  $("select").change(() => {
    if ($("#dateFin").css("display") == "none") {
      $("#dateFin").css("display", "inline");
    } else {
      $("#dateFin").css("display", "none");
      $("#dateFin").val(null);
    }
    filtrarJornadas();
  });
});

// Funciones

const cargarObservacion = () => {
  $.ajax({
    url: ruta_cargar_observacion,
    type: "GET",
    dataType: "json",
    async: true,
    success: function (data) {
      minutos = data.comida / 60;
      if (isNaN(minutos)) {
        minutos = 0;
      }
      $("#tiempo-comida").val(minutos);
      let h = Math.floor(minutos / 60);
      let m = minutos % 60;
      if (isNaN(h)) {
        h = 0;
      }
      if (isNaN(m)) {
        m = 0;
      }
      $("#horas-comida").text(`${h} horas ${m} minutos`);
      $("#observaciones").val(data.respuesta);
    },
    error: function (errorThrown) {
      console.log(errorThrown);
    },
  });
};

const cargarObservacionManual = () => {
  $.ajax({
    url: ruta_cargar_observacion,
    type: "POST",
    dataType: "json",
    data: {
      fecha: $("#fecha-inicio").val().substring(0, 10),
    },
    async: true,
    success: function (data) {
      console.log(typeof data.comida);
      if (data.comida == null) {
        minutos = 60;
        m = 0;
        h = 1;

        $("#horas-comida").text(`${h} horas ${m} minutos`);
        $("#tiempo-comida").val(minutos);
        $("#observaciones-manual").val(data.respuesta);
      } else {
        minutos = data.comida / 60;
        if (isNaN(minutos)) {
          minutos = 0;
        }
        let h = Math.floor(minutos / 60);
        let m = minutos % 60;
        console.log(minutos, h, m);

        if (isNaN(h)) {
          h = 0;
        }
        if (isNaN(m)) {
          m = 0;
        }
        console.log(minutos, h, m);

        $("#horas-comida").text(`${h} horas ${m} minutos`);
        $("#tiempo-comida").val(minutos);
        $("#observaciones-manual").val(data.respuesta);
      }
    },
    error: function (errorThrown) {
      minutos = 60;
      m = 0;
      h = 1;

      $("#horas-comida").text(`${h} horas ${m} minutos`);
      $("#tiempo-comida").val(minutos);
      $("#observaciones-manual").val("");
      console.log(errorThrown);
    },
  });
};

// Contador
const displayTimer = () => {
  $.ajax({
    url: ruta_inicio_control,
    type: "GET",
    dataType: "json",
    async: true,
    success: function (data) {
      localStorage.setItem("codigo", data.codigo);
      if (data.actual === undefined) {
        location.reload();
      }
      let hours = data.actual.h;
      let minutes = data.actual.i;
      let seconds = data.actual.s;
      let days = data.actual.days;

      hours += days * 24;

      hours = hours < 10 ? "0" + hours : hours;
      minutes = minutes < 10 ? "0" + minutes : minutes;
      seconds = seconds < 10 ? "0" + seconds : seconds;
      $("#tiempo").text(`${hours}:${minutes}:${seconds}`);
    },
    error: function (errorThrown) {
      console.log(errorThrown);
    },
  });
};

// Guardar el codigo de la jornada en localStorage
const peticionIniciar = () => {
  // enviar el tipo
  $.ajax({
    url: ruta_inicio_control,
    type: "POST",
    dataType: "json",
    data: {
      tipo: tipoJornada,
    },
    async: true,
    success: function (data) {
      $("#tiempo").text(`00:00:00`);
    },
    error: function (errorThrown) {
      console.log(errorThrown);
    },
  });
};

// Borrar el codigo de la jornada
const peticionFinalizar = () => {
  $.ajax({
    url: ruta_inicio_control,
    type: "POST",
    data: {
      codigo: localStorage.getItem("codigo"),
    },
    dataType: "json",
    async: true,
    success: function (data) {
      localStorage.removeItem("codigo");
      //$("#tiempo").text(`00:00:00`);
    },
    error: function (errorThrown) {
      console.log(errorThrown);
    },
  });
};
