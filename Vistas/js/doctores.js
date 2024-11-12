// Evento para editar un doctor
$(".DT").on("click", ".EditarDoctor", function () {
	var Did = $(this).attr("Did");
	var datos = new FormData();
	datos.append("Did", Did);

	$.ajax({
		url: "Ajax/doctoresA.php",  // Se llama a este archivo para obtener los datos del doctor
		method: "POST",
		data: datos,
		dataType: "json",
		contentType: false,
		cache: false,
		processData: false,
		success: function (resultado) {
			console.log("Resultado de la edición del doctor:", resultado); // Log para revisar la respuesta
			// Se llenan los campos del modal con los datos del doctor
			if (resultado.error) {
				alert(resultado.error); // Si hay error, lo mostramos
			} else {
				$("#Did").val(resultado["id"]);
				$("#apellidoE").val(resultado["apellido"]);
				$("#nombreE").val(resultado["nombre"]);
				$("#usuarioE").val(resultado["usuario"]);
				$("#claveE").val(resultado["clave"]);
				$("#sexoE").val(resultado["sexo"]);
			}
		},
		error: function (xhr, status, error) {
			console.log("Error en AJAX:", status, error); // Log para verificar errores en la llamada AJAX
			alert("Hubo un problema al obtener los datos.");
		}
	});
});

// Evento para eliminar un doctor
$(".DT").on("click", ".EliminarDoctor", function () {
	var Did = $(this).attr("Did");
	var imgD = $(this).attr("imgD");

	// Confirmación antes de proceder con la eliminación
	if (confirm("¿Estás seguro de eliminar este doctor?")) {
		var datos = new FormData();
		datos.append("Did", Did);
		datos.append("imgD", imgD);  // Si se necesita eliminar la foto también

		$.ajax({
			url: "Ajax/doctoresE.php",  // Se llama a este archivo para realizar la eliminación
			method: "POST",
			data: datos,
			dataType: "json",
			contentType: false,
			cache: false,
			processData: false,
			success: function (response) {
				console.log("Respuesta de eliminación:", response); // Log para revisar la respuesta
				if (response.success) {
					alert("Doctor eliminado exitosamente.");
					// Recargar la tabla o la página para actualizar la vista
					$(".DT").DataTable().ajax.reload();  // Si estás usando DataTables, esto recarga los datos
				} else {
					alert(response.error || "Hubo un error al eliminar el doctor.");
				}
			},
			error: function (xhr, status, error) {
				console.log("Error en AJAX:", status, error); // Log para verificar errores en la llamada AJAX
				alert("Hubo un problema al procesar la eliminación.");
			}
		});
	}
});
