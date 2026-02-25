$(document).ready(function () {
    // Inicializar DataTables
    var table = $('.DT').DataTable({
        ajax: {
            url: 'ruta/al/archivo.php', // Ruta al archivo que devuelve los datos
            method: 'POST',
            dataSrc: 'data' // Nombre del array que contiene los datos en la respuesta JSON
        },
        columns: [
            { data: 'id' },
            { data: 'apellido' },
            { data: 'nombre' },
            { data: 'usuario' },
            { data: 'sexo' },
            {
                data: null,
                render: function (data, type, row) {
                    return `
                        <button class="btn btn-success EditarDoctor" Did="${data.id}">Editar</button>
                        <button class="btn btn-danger EliminarDoctor" Did="${data.id}" imgD="${data.foto || ''}">Eliminar</button>
                    `;
                }
            }
        ]
    });

    // Evento para crear un doctor
    $("#formCrearDoctor").on("submit", function (e) {
        e.preventDefault(); // Evitar el envío tradicional del formulario

        var datos = new FormData(this); // Crear un objeto FormData con los datos del formulario

        $.ajax({
            url: "Ajax/doctoresC.php", // Ruta al archivo que procesa la creación
            method: "POST",
            data: datos,
            dataType: "json",
            contentType: false,
            cache: false,
            processData: false,
            success: function (response) {
                console.log("Respuesta del servidor:", response);

                // Verificar si la respuesta es un JSON válido
                if (typeof response === 'object' && response !== null) {
                    if (response.success) {
                        alert("Doctor creado correctamente.");
                        table.ajax.reload(); // Recargar la tabla
                        $("#CrearDoctor").modal("hide"); // Ocultar el modal
                        $("#formCrearDoctor")[0].reset(); // Limpiar el formulario
                    } else {
                        alert(response.error || "Hubo un error al crear el doctor.");
                    }
                } else {
                    alert("La respuesta del servidor no es válida.");
                }
            },
            error: function (xhr, status, error) {
                console.log("Error en AJAX:", status, error);
                alert("Hubo un problema al procesar la creación.");
            }
        });
    });

    // Evento para editar un doctor
    $(".DT").on("click", ".EditarDoctor", function () {
        var Did = $(this).attr("Did");
        var datos = new FormData();
        datos.append("Did", Did);

        $.ajax({
            url: "Ajax/doctoresA.php",
            method: "POST",
            data: datos,
            dataType: "json",
            contentType: false,
            cache: false,
            processData: false,
            success: function (resultado) {
                console.log("Resultado de la edición del doctor:", resultado);

                // Verificar si la respuesta es un JSON válido
                if (typeof resultado === 'object' && resultado !== null) {
                    if (resultado.error) {
                        alert(resultado.error); // Mostrar error si existe
                    } else if (resultado.data) {
                        // Llenar los campos del modal con los datos del doctor
                        $("#Did").val(resultado.data["id"]);
                        $("#apellidoE").val(resultado.data["apellido"]);
                        $("#nombreE").val(resultado.data["nombre"]);
                        $("#usuarioE").val(resultado.data["usuario"]);
                        $("#claveE").val(resultado.data["clave"]);
                        $("#sexoE").val(resultado.data["sexo"]);

                        // Mostrar el modal de edición
                        $("#EditarDoctor").modal("show");
                    }
                } else {
                    alert("La respuesta del servidor no es válida.");
                }
            },
            error: function (xhr, status, error) {
                console.log("Error en AJAX:", status, error);
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
                url: "Ajax/doctoresE.php",
                method: "POST",
                data: datos,
                dataType: "json",
                contentType: false,
                cache: false,
                processData: false,
                success: function (response) {
                    console.log("Respuesta de eliminación:", response);

                    // Verificar si la respuesta es un JSON válido
                    if (typeof response === 'object' && response !== null) {
                        if (response.success) {
                            alert("Doctor eliminado exitosamente.");
                            table.ajax.reload(); // Recargar la tabla
                        } else {
                            alert(response.error || "Hubo un error al eliminar el doctor.");
                        }
                    } else {
                        alert("La respuesta del servidor no es válida.");
                    }
                },
                error: function (xhr, status, error) {
                    console.log("Error en AJAX:", status, error);
                    alert("Hubo un problema al procesar la eliminación.");
                }
            });
        }
    });

    // Evento para guardar los cambios al editar un doctor
    $("#formEditarDoctor").on("submit", function (e) {
        e.preventDefault(); // Evitar el envío tradicional del formulario

        var datos = new FormData(this);

        $.ajax({
            url: "Ajax/doctoresE.php", // Ruta al archivo que procesa la edición
            method: "POST",
            data: datos,
            dataType: "json",
            contentType: false,
            cache: false,
            processData: false,
            success: function (response) {
                console.log("Respuesta de la edición:", response);

                // Verificar si la respuesta es un JSON válido
                if (typeof response === 'object' && response !== null) {
                    if (response.success) {
                        alert("Doctor actualizado correctamente.");
                        table.ajax.reload(); // Recargar la tabla
                        $("#EditarDoctor").modal("hide"); // Ocultar el modal
                    } else {
                        alert(response.error || "Hubo un error al actualizar el doctor.");
                    }
                } else {
                    alert("La respuesta del servidor no es válida.");
                }
            },
            error: function (xhr, status, error) {
                console.log("Error en AJAX:", status, error);
                alert("Hubo un problema al procesar la actualización.");
            }
        });
    });
});