$(document).ready(function () {
    // Cargar datos al formulario cuando se hace clic en el botón Editar
    $(".btnEditarPerfil").click(function () {
        const id = $(this).data("id");
        const usuario = $(this).data("usuario");
        const nombre = $(this).data("nombre");
        const apellido = $(this).data("apellido");
        const foto = $(this).data("foto");
        const clave = $(this).data("clave");

        // Asignar los valores al formulario del modal
        $("#idPaciente").val(id);
        $("#usuarioE").val(usuario);
        $("#nombreE").val(nombre);
        $("#apellidoE").val(apellido);
        $("#fotoActual").val(foto);
        $("#claveActual").val(clave);
    });

    // Manejar el envío del formulario de actualización
    $("#formActualizarPerfil").submit(function (event) {
        event.preventDefault(); // Prevenir el envío normal del formulario

        const formData = new FormData(this); // Obtener los datos del formulario

        $.ajax({
            url: "Controladores/pacientesC.php?action=actualizarPerfil",
            type: "POST",
            data: formData,
            processData: false,
            contentType: false,
            success: function (response) {
                if (response == "ok") {
                    // Actualización exitosa, redirigir al perfil del paciente
                    window.location = "perfil-Paciente";
                } else {
                    // Mostrar un mensaje de error si algo salió mal
                    alert("Hubo un error al actualizar el perfil");
                }
            },
            error: function () {
                alert("Error en la solicitud Ajax");
            }
        });
    });
});
