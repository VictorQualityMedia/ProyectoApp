const intervalo = () => {
    $.ajax({
        url: ruta_comprobar_actividad,
        method: "GET",
        dataType: "json",
        success: function (data) {
            if (!data) {
                location.reload();
            }
        },
        error: function (err) {
            console.log(err);
        }
    })
}

setInterval(intervalo, 60000);