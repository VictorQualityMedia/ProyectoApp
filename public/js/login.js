$("#enlace").on("click", () => {
  if ($("#enlace").html() == "¿Iniciar Sesion?") {
    $("#enlace").html("¿Olvidaste tu contraseña?");
    $("button").html("Sign in");
    $("#formulario").attr("action", login);
  } else {
    $("#enlace").html("¿Iniciar Sesion?");
    $("button").html("Enviar Correo");
    $("#formulario").attr("action", correo);
  }
  $(".datos").eq(1).slideToggle("slow");
});
