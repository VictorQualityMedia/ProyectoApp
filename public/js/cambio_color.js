// --------- SECCION DEL CÓDIGO PARA EL CAMBIO DE COLOR ------------

// Color obtenido por la función de recibir color, accede a la bbdd y extrae el color establecido por el administrador
var color_bbdd = "";


// Esta función toma el color de la base de datos y luego llama a otra función para directamente cambiarlo. Cabe aclarar que esta función es
// únicamente para los botones.
function recibir_color() {
  $color = "";
  $.ajax({
    url: ruta_devolver_color,
    method: "POST",
    dataType: "json",
    data: {},
    async: true,
    success: function (data) {
      if (data.error) {
        alert("Ha habido un error con el cambio de color.");
      } 
      else {
        color_bbdd = data.exito;
        cambiar_color();
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
}

// Cambia el color de todos los botones por un valor hexadecimal como parámetro
function cambiar_color () {
    let buttons = document.querySelectorAll('input[type="submit"]');
    let buttons_2 = document.querySelectorAll('button[class="especial"]');
    buttons.forEach(function(button) {
        button.style.backgroundColor = color_bbdd;
    });
    buttons_2.forEach(function(button) {
      button.style.backgroundColor = color_bbdd;
  });

  // Seleccionamos todos los enlaces dentro del contenedor .topnav
  let elementos_cabecera = document.querySelector('a');
  let links = document.querySelectorAll('.topnav a');

  // Recorremos cada enlace y le asignamos eventos
  links.forEach(function(link) {
  // Cambia el color de fondo cuando el ratón está sobre el enlace
  link.addEventListener('mouseover', function() {
      link.style.backgroundColor = color_bbdd;
  });

  // Vuelve a su color original cuando el ratón sale del enlace
  link.addEventListener('mouseout', function() {
      link.style.backgroundColor = ''; // Esto elimina el estilo inline aplicado
  });
  });
}

// Ejecutamos la función al principio para asegurarnos.
$(document).ready(function() {
  // Llamamos a las funciones cuando la página se carga completamente
  // Esta función se encargará de obtener el color de la base de datos y luego cambiar el color
  recibir_color();
  cambiar_color();
  console.log("Se ejecutaaron las funciones.");
});

// -----------------------------------------------------------------------------------