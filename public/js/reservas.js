const cargarCarrito = () => {
  $.ajax({
    url: ruta_cargar_carrito,
    method: "GET",
    dataType: "json",
    async: true,
    success: function (data) {
      console.log(data);
      if (data.length > 0 || Object.keys(data).length > 0) {
        $("#reservas-solicitadas").append(
          $(
            `<div class='acciones'><button class='btn-aceptar-carrito'>Solicitar</button> Para el día: <input type='date' id='peticion'></div>`
          )
        );
        if (document.getElementById("peticion") != null) {
          document.getElementById("peticion").valueAsDate = new Date(
            new Date().getTime() + 7 * 24 * 60 * 60 * 1000
          );
        }
      } else {
        $("#reservas-solicitadas").append(
          $(`<p>Aún no has solicitado ningún producto!</p>`)
        );
      }
      data = Object.values(data);
      console.log(data);
      data.forEach(function (e) {
        $("#reservas-solicitadas").append(
          $(
            `<div id='${e.id}' class='producto'><label>${e.nombre}</label><label>Cantidad</label><div><button class='btn-menos'>-</button><input type='text' class='cantidad' data-inicial='${e.unidades}' data-maximo='${e.cantidad}' value='${e.unidades}'><button class='btn-mas'>+</button></div><img class="basura" src='${imagen_basura}' alt="basura"></div>`
          )
        );
        /*         $(".btn-cancelar")
          .unbind()
          .click(function () {
            jQuery.each($(".cantidad"), function (x, val) {
              $(val).val($(val).data("inicial"));
            });
          }); */

        $(".cantidad")
          .unbind()
          .keyup(function (e) {
            if (e.key == "Backspace") {
              if ($(this).val() == "") {
                $(this).val("1");
              }
            } else {
              if (isNaN(e.key)) {
                $(this).val($(this).val().slice(0, -1));
              }
              if ($(this).val() > $(this).data("maximo")) {
                $(this).val($(this).data("maximo"));
              }
            }
          });

        $(".btn-aceptar-carrito")
          .unbind()
          .click(function () {
            let productos = [];
            jQuery.each($(".producto"), function (i, val) {
              productos.push({
                id: $(val).attr("id"),
                cantidad: $(val)
                  .children("div")
                  .eq(0)
                  .children("input")
                  .eq(0)
                  .val(),
              });
            });

            console.log(productos);
            $.ajax({
              url: ruta_crear_reserva,
              type: "POST",
              dataType: "json",
              data: {
                productos: productos,
                peticion: $("#peticion").val(),
              },
              async: true,
              success: function (data) {
                console.log(data);
                $("#reservas-solicitadas").empty();
                cargarCarrito();
                cargarSolicitudes();
              },
              error: function (errorThrown) {
                console.log(errorThrown);
              },
            });
          });
        $(".btn-menos")
          .unbind()
          .click(function () {
            if ($(this).next().val() > 0) {
              $(this)
                .next()
                .val(parseInt($(this).next().val()) - 1);
            }
          });

        $(".btn-mas")
          .unbind()
          .click(function () {
            if (
              parseInt($(this).prev().val()) <
              parseInt($(this).prev().data("maximo"))
            ) {
              $(this)
                .prev()
                .val(parseInt($(this).prev().val()) + 1);
            }
          });
      });
      $(".basura").click(function () {
        $(this).parent().remove();
        $.ajax({
          type: "POST",
          url: ruta_eliminar,
          data: {
            producto: $(this).parent().attr("id"),
          },
          dataType: "json",
          success: function (response) {
            console.log(response);
          },
        });
        $(this).parent().remove();
      });
    },
    error: function (errorThrown) {
      console.log(errorThrown);
    },
  });
};

const cargarSolicitudes = () => {
  $.ajax({
    url: ruta_cargar_por_confirmar,
    method: "GET",
    dataType: "json",
    async: true,
    success: function (data) {
      $("#reservas-a-confirmar").empty();
      if (data.length > 0) {
        console.log(data);
        $("#reservas-a-confirmar").append(
          "<div id='reservas-confirmar'></div>"
        );
        data.forEach((e) => {
          let array_cod_productos = [];
          e.productos.forEach(p => {
            
            array_cod_productos.push(p.id_producto);
          })
          $("#reservas-confirmar").append(
            `<div id='reserva-${e.id
            }' class='reserva'><div class='fechas'><p>Fecha: ${e.fecha.date.substring(
              0,
              10
            )}</p><p>Para el día: ${e.solicitud.date.substring(
              0,
              10
            )}</p>Productos:<table ><tr><td>Producto</td><td>Cantidad</td><td>Comentario:</td></tr></table><div class='acciones'><span class='btn-aceptar'><i class="fa-solid fa-check"></i></span><span class='btn-denegar'><i class="fa-solid fa-x"></i></span></div></div>`
          );
          $(".btn-aceptar")
            .unbind()
            .click(function () {
              let btn = $(this);
              let comentarios = [];
              $(".comentario-admin").each(function () {
                comentarios.push($(this).val());
              });
              $.ajax({
                url: ruta_confirmar_solicitud,
                method: "POST",
                data: {
                  id: e.id,
                  confirmar: true,
                  comentarios: comentarios,
                },
                dataType: "json",
                async: true,
                success: function (data) {
                  $.ajax({
                    url: ruta_borrar_reservas,
                    method: "POST",
                    data: {
                      "productos": array_cod_productos
                    },
                    dataType: "json",
                    async: true,
                    success: function (data) {
                      console.log(data);
                      cargarSolicitudes();
                      cargarPrestamos();
                    },
                    error: function (errorThrown) {
                      console.log(errorThrown);
                    }
                  });
                  
                },
                error: function (errorThrown) {
                  console.log(errorThrown);
                },
              });
              
            });

          $(".btn-denegar")
            .unbind()
            .click(function () {
              let comentarios = [];
              $(".comentario-admin").each(function () {
                comentarios.push($(this).val());
              });
              $.ajax({
                url: ruta_confirmar_solicitud,
                method: "POST",
                data: {
                  id: e.id,
                  confirmar: false,
                  comentarios: comentarios,
                },
                dataType: "json",
                async: true,
                success: function (data) {
                  cargarSolicitudes();
                },
                error: function (errorThrown) {
                  console.log(errorThrown);
                },
              });
            });

          if (e.usuario) {
            $(`#reserva-${e.id} p`).eq(1).append(`<p>Por: ${e.usuario}</p>`);
          }
          e.productos.forEach((p) => {
            $(`#reserva-${e.id} table`).append(
              `<tr><td>${p.nombre}</td><td>${p.cantidad}</td><td><textarea class='comentario-admin'></textarea></td></tr>`
            );
          });
        });
      } else {
        $("#reservas-a-confirmar").append(
          $("<p>No hay reservas por confirmar.</p>")
        );
      }
    },
    error: function (errorThrown) {
      console.log(errorThrown);
    },
  });
};

const cargarPrestamos = () => {
  $.ajax({
    url: ruta_cargar_prestamos,
    method: "GET",
    dataType: "json",
    async: true,
    success: function (data) {
      console.log(data);
      $("#contenido-historial").empty();
      $("#contenido-historial").append("<div id='reservas'></div>");
      data.forEach((e) => {
        console.log(e);

        $("#reservas").append(
          `<div id='reserva-${e.id
          }' class='reserva'><button class='btn-imprimir button'>Imprimir</button><h3>ID: ${e.id
          }</h3><div class='fechas'><p>Fecha: ${e.fecha.date.substring(
            0,
            10
          )}</p><p>Para el día: ${e.solicitud.date.substring(
            0,
            10
          )}</p></div><p>Receptor: ${e.receptor
          }</p>Productos:<table><tr><td>Producto</td><td>Cantidad</td><td>Observaciones devolución</td></tr></table><p>Observaciones: </p><table id='productos-${e.id
          }'><tr><td>Producto</td><td>Observaciones iniciales</td></tr></table><p>Estado: ${e.estado ? e.estado : ""
          }</p><p>Fecha devolución: ${e.devolucion ? e.devolucion.date.substring(0, 10) : "NO DEVUELTO"
          }</p></div>`
        );
        e.productos.forEach((p) => {
          if (e.id == 57) {
            console.log(p);
          }
          let fila = $(
            `<tr><td>${p.nombre}</td><td>${p.observaciones_i ? p.observaciones_i : ""
            }</td></tr>`
          );
          $(`#productos-${e.id}`).append(fila);

          let fila2 = $(
            `<tr><td>${p.nombre}</td><td>${p.observaciones_i ? p.observaciones_i : ""
            }</td></tr>`
          );
        });

        if (e.usuario) {
          $(`#reserva-${e.id} p`)
            .eq(1)
            .after(`<p class='solicitante'>Solicitante: ${e.usuario}</p>`);
        }
        e.productos.forEach((p) => {
          $(`#reserva-${e.id} table:not([id])`).append(
            `<tr><td>${p.nombre}</td><td>${p.cantidad}</td><td>${p.observaciones_f ? p.observaciones_f : ""
            }</td></tr>`
          );
        });

        $(".btn-imprimir")
          .unbind()
          .click(function () {
            $(this).parent().addClass("aImprimir");
            $(this)
              .parent()
              .prepend(
                $(`<h1 class='aImprimir aBorrar'>PETICIÓN DE PRÉSTAMO</h1>`)
              );
            $(this)
              .parent()
              .append(
                $(
                  "<div id='firmas' class='aBorrar'><div><p>Responsable de almacen</p><p>Fdo:</p></div><div><p>Solicitante</p><p>Fdo:</p></div></div>"
                )
              );
            window.print();
            $(this).parent().removeClass("aImprimir");
            $(".aBorrar").remove();
          });
      });
    },
    error: function (errorThrown) {
      console.log(errorThrown);
    },
  });
};
cargarPrestamos();
cargarCarrito();
cargarSolicitudes();
