let correo = $('#correo').val();
let nombre = $('#nombre').val();
let dni = $('#dni').val();
let zona = $("#zona").val();
$("#link-perfil").addClass("active");
if (window.innerHeight > window.innerWidth) {
    $("#link-perfil").detach().insertBefore("a:first");
}
$("#zona").val(zona);
// Event Listeners

$('#cancelar').click(function () {
    $('#correo').val(correo)
    $('#nombre').val(nombre)
    $('#dni').val(dni)
    $("#clave").val("");
    $("#confirmar").val("");
    $("#zona").val(zona)
    if (es_admin) {
        $("#departamento").val(dept_inicial);
    }
})

$("#info-zona").mouseenter(() => $("#mensaje").toggleClass("esconder"));
$("#info-zona").mouseleave(() => $("#mensaje").toggleClass("esconder"));

$('#actualizar').click(function () {
    if (validarFormularioModificar()) {
        $.ajax({
            url: ruta_modificar,
            method: "POST",
            dataType: "json",
            async: true,
            data: {
                'correo': $("#correo").val(),
                'nombre': $("#nombre").val(),
                'dni': $("#dni").val(),
                'clave': $("#clave").val(),
                'depart': $("#departamento").val() === '' ? 'nada' : $("#departamento").val(),
                'zona': $("#zona").val()
            },
            success: function (data) {
                if (data.error) {
                    alert(data.error);
                } else {
                    alert('Cambios guardados.');
                    location.reload();
                }
            },
            error: function (errorThrown) {
                console.log(errorThrown);
            }
        });
    }
});

// Funciones

const cargarDepartamentos = () => {
    $.ajax({
        url: ruta_cargar_dept,
        method: "GET",
        dataType: "json",
        async: true,
        success: function (data) {
            data.forEach(element => {
                let option = $(`<option value="${element.id}">${element.nombre}</option>`);
                if (element.id == dept_inicial) {
                    option.attr("selected", "selected");
                }

                if (es_admin) {
                    $("#departamento").append(option);
                }
            });
        },
        error: function (errorThrown) {
            console.log(errorThrown);
        }
    });
}

cargarDepartamentos();

const validarFormularioModificar = () => {
    if ($("#clave").val().length > 0 || $("#confirmar").val() > 0) {
        if ($('#clave').val() != $('#confirmar').val()) {
            alert('Las contraseñas tienen que coincidir!');
            return false;
        }
    }
    if ($("#correo").length > 0 && $("#correo").val() == '') {
        alert('Todos los campos son obligatorios!');
        return false;
    }

    return true;
}



const comprobarDni = (dni) => {
    let numero, letra, letr;
    let expresion_regular = /^[XYZ]?\d{5,8}[A-Z]$/;

    dni = dni.toUpperCase();

    if (expresion_regular.test(dni)) {
        numero = dni.substr(0, dni.length - 1);
        numero = numero.replace('X', 0);
        numero = numero.replace('Y', 1);
        numero = numero.replace('Z', 2);

        letr = dni.substr(dni.length - 1, 1);
        numero = numero % 23;
        letra = 'TRWAGMYFPDXBNJZSQVHLCKET';
        letra = letra.substring(numero, numero + 1);

        if (letra == letr) {
            return true;
        }
    }
    return false;
}
