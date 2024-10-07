const cargarCategorias = () => {
    $.ajax({
        url: ruta_cargar_categorias,
        type: 'GET',
        dataType: 'json',
        async: false,
        success: function (data) {
            $("#lista-categorias").empty();
            $('#lista-categorias').append($('<option value="0">Todos</option>'));
            data.forEach(e => {
                $('#lista-categorias').append($(`<option value='${e.codigo}'>${e.nombre}</option>`));
            })
            return data;
        },
        error: function (errorThrown) {
            console.log(errorThrown);
        }
    });
}


const cargarProductos = () => {
    $.ajax({
        url: ruta_cargar_productos,
        type: 'POST',
        data: {
            'categoria': $("#lista-categorias").val(),
            'nombre': $("#buscar-nombre").val()
        },
        async: false,
        dataType: 'json',
        success: function (data) {
            if (data.respuesta) {
                $(".filtros").after($(`<p id='respuesta' style='color: green; font-weight: bold; font-size: 24px'>${data.respuesta}</p>`));
                function esconder() {
                    $("#respuesta").remove();
                }
                setTimeout(esconder, 3000);
            }
            if (data.length > 0) {
                $("#lista-productos").empty();

                data.forEach(e => {
                    $("#lista-productos").append($(`<div id='${e.id}' class='producto'><a href="${ruta_pagina_producto}/${e.id.replace(" ", "_")}">${e.nombre}</a><div>Disponibles: ${e.stock}</div><div><button class='btn-menos'>-</button><input type='text' class='cantidad' data-maximo='${e.stock}' value='1'><button class='btn-mas'>+</button></div><button class='btn-reservar button'><img src="${imagen_carrito}" alt="img carrito"></button></div>`));

                    $('.btn-reservar').unbind().click(function () {
                        let id_etiqueta = $(this).parent().attr('id');
                        let target = $(this).parent();
                        $.ajax({
                            url: ruta_guardar_carrito,
                            type: 'POST',
                            dataType: 'json',
                            data: {
                                'id': id_etiqueta,
                                'cantidad': $(this).parent().children("div").eq(1).children("input").eq(0).val()
                            },
                            async: true,
                            success: function (data) {
                                target.append($(`<p id='respuesta' style='color: white; font-weight: bold; font-size: 12px; text-align: center; position: absolute; bottom: 0;'>${data.respuesta}</p>`));
                                target.css("position", "relative");
                                target.css("padding-bottom", "2rem");
                                function esconder() {
                                    $("#respuesta").remove();
                                    target.css("position", "static");
                                    target.css("padding", "1rem");
                                }
                                setTimeout(esconder, 2000);
                            },
                            error: function (errorThrown) {
                                console.log(errorThrown);
                            }
                        });

                    });

                    $('.cantidad').unbind().keyup(function (e) {
                        if (e.key == "Backspace") {
                            if ($(this).val() == '') {
                                $(this).val('1');
                            }
                        } else {
                            if (isNaN(e.key)) {
                                $(this).val($(this).val().slice(0, -1));

                            }
                            if ($(this).val() > $(this).data('maximo')) {
                                $(this).val($(this).data('maximo'));
                            }
                        }
                    });

                    $('.btn-menos').unbind().click(function () {
                        if ($(this).next().val() > 1) {
                            $(this).next().val(parseInt(($(this).next().val())) - 1);
                        }
                    });

                    $('.btn-mas').unbind().click(function () {
                        if (parseInt($(this).prev().val()) < parseInt($(this).prev().data('maximo'))) {
                            $(this).prev().val(parseInt(($(this).prev().val())) + 1);
                        }
                    });
                });
            }
        },
        error: function (errorThrown) {
            console.log(errorThrown);
        }
    });
}

//cargarCategorias();
cargarProductos();

$("#lista-categorias").change(() => { $("#lista-productos").empty(); cargarProductos() });
$("#buscar-nombre").keyup(() => {
    let linebreaks = ($("#buscar-nombre").val().match(/\n/g) || []).length;
    if (linebreaks == 1) {
        $("#buscar-nombre").val($("#buscar-nombre").val().replace(/\n/g, ""));
        console.log($("#buscar-nombre").val())
        $("#buscar-nombre").val("");
    } else {
        cargarProductos();
       
    }
});