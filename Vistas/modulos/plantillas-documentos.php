<?php
// En Vistas/modulos/plantillas-documentos.php

if (!isset($_SESSION["rol"]) || $_SESSION["rol"] != "Administrador") {
    echo '<script>window.location = "inicio";</script>';
    return;
}

$plantillasController = new PlantillasDocumentosC();
if (isset($_POST["tituloCrear"])) { 
    $plantillasController->CrearPlantillaC(); 
}
if (isset($_POST["tituloEditar"])) { 
    $plantillasController->ActualizarPlantillaC(); 
}
?>

<section class="content-header">
    <h1>Gestión de Plantillas de Documentos</h1>
    <ol class="breadcrumb">
        <li><a href="inicio"><i class="fa fa-dashboard"></i> Inicio</a></li>
        <li class="active">Plantillas de Documentos</li>
    </ol>
</section>

<section class="content">
    <div class="box">
        <div class="box-header with-border">
            <button class="btn btn-primary" data-toggle="modal" data-target="#modalCrearPlantilla">
                Crear Nueva Plantilla
            </button>
        </div>
        <div class="box-body">
            <table class="table table-bordered table-striped dt-responsive tablaPlantillas" width="100%">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Título</th>
                        <th>Tipo</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</section>

<!-- MODAL CREAR PLANTILLA -->
<div id="modalCrearPlantilla" class="modal fade" role="dialog">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="post" action="index.php?url=plantillas-documentos">
                <div class="modal-header" style="background:#3c8dbc; color:white">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title">Crear Nueva Plantilla</h4>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <div class="input-group">
                            <span class="input-group-addon"><i class="fa fa-header"></i></span>
                            <input type="text" class="form-control input-lg" name="tituloCrear" placeholder="Título descriptivo" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <div class="input-group">
                            <span class="input-group-addon"><i class="fa fa-file-o"></i></span>
                            <select class="form-control input-lg" name="tipoCrear" id="tipoCrear" required>
                                <option value="">Seleccionar tipo</option>
                                <option value="receta">Receta</option>
                                <option value="certificado">Certificado</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <button type="button" class="btn btn-sm btn-info" id="btnEjemploCrear" style="display:none;">Cargar Ejemplo</button>
                    </div>

                    <div class="form-group">
                        <label>Contenido de la Plantilla:</label>
                        <textarea id="contenidoCrear" name="contenidoCrear" class="form-control" rows="10"></textarea>
                    </div>

                    <div class="callout callout-info">
                        <h4>Placeholders Disponibles</h4>
                        <p id="placeholdersCrear">Selecciona un tipo para ver los placeholders.</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default pull-left" data-dismiss="modal">Salir</button>
                    <button type="submit" class="btn btn-primary">Guardar Plantilla</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL EDITAR PLANTILLA -->
<div id="modalEditarPlantilla" class="modal fade" role="dialog">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="post" action="index.php?url=plantillas-documentos">
                <div class="modal-header" style="background:#f39c12; color:white">
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                    <h4 class="modal-title">Editar Plantilla</h4>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="idPlantillaEditar" name="idPlantillaEditar">

                    <div class="form-group">
                        <div class="input-group">
                            <span class="input-group-addon"><i class="fa fa-header"></i></span>
                            <input type="text" class="form-control input-lg" id="tituloEditar" name="tituloEditar" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <div class="input-group">
                            <span class="input-group-addon"><i class="fa fa-file-o"></i></span>
                            <select class="form-control input-lg" id="tipoEditar" name="tipoEditar" required>
                                <option value="">Seleccionar tipo</option>
                                <option value="receta">Receta</option>
                                <option value="certificado">Certificado</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <button type="button" class="btn btn-sm btn-info" id="btnEjemploEditar" style="display:none;">Cargar Ejemplo</button>
                    </div>

                    <div class="form-group">
                        <label>Contenido de la Plantilla:</label>
                        <textarea id="contenidoEditar" name="contenidoEditar" class="form-control" rows="10"></textarea>
                    </div>

                    <div class="callout callout-info">
                        <h4>Placeholders Disponibles</h4>
                        <p id="placeholdersEditar">Selecciona un tipo para ver los placeholders.</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default pull-left" data-dismiss="modal">Salir</button>
                    <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.ckeditor.com/4.22.1/standard/ckeditor.js"></script>

<?php ob_start(); ?>
<script>
$(document).ready(function(){

    CKEDITOR.replace('contenidoCrear',{allowedContent:true});
    CKEDITOR.replace('contenidoEditar',{allowedContent:true});

    var tablaPlantillas = $('.tablaPlantillas').DataTable({
        "ajax":{
            "url":"<?= BASE_URL ?>Ajax/plantillas-documentosA.php",
            "type":"POST",
            "data":{action:"listar"}
        },
        "columns":[
            { "data": null, "render": (data,type,row,meta)=> meta.row+1 },
            { "data": "titulo" },
            { "data": "tipo", "render": data => data=="receta"?'<span class="label label-success">Receta</span>':'<span class="label label-info">Certificado</span>' },
            { "data": "id", "render": (data,type,row)=>`
                <div class='btn-group'>
                    <button class='btn btn-warning btn-sm btnEditarPlantilla' data-id='${data}' data-toggle='modal' data-target='#modalEditarPlantilla'><i class='fa fa-pencil'></i></button>
                    <button class='btn btn-danger btn-sm btnEliminarPlantilla' data-id='${data}' data-nombre='${row.titulo}'><i class='fa fa-times'></i></button>
                </div>`, "orderable":false, "searchable":false
            }
        ]
    });

    // CREAR: actualizar placeholders y botón de ejemplo
    $('#tipoCrear').on('change', function(){
        var tipo = $(this).val();
        if(tipo){
            $('#btnEjemploCrear').show().data('tipo',tipo);
            $.post('<?= BASE_URL ?>Ajax/plantillas-documentosA.php',{action:'obtenerEjemplo',tipo:tipo},function(res){
                $('#placeholdersCrear').html(res.placeholders.join(', '));
            },'json');
        } else {
            $('#btnEjemploCrear').hide();
            $('#placeholdersCrear').html('Selecciona un tipo para ver los placeholders.');
        }
    });

    $('#btnEjemploCrear').on('click', function(){
        var tipo = $(this).data('tipo');
        $.post('<?= BASE_URL ?>Ajax/plantillas-documentosA.php',{action:'obtenerEjemplo',tipo:tipo},function(res){
            CKEDITOR.instances.contenidoCrear.setData(res.ejemplo);
        },'json');
    });

    // EDITAR: similar a crear
    $('#tipoEditar').on('change', function(){
        var tipo = $(this).val();
        if(tipo){
            $('#btnEjemploEditar').show().data('tipo',tipo);
            $.post('<?= BASE_URL ?>Ajax/plantillas-documentosA.php',{action:'obtenerEjemplo',tipo:tipo},function(res){
                $('#placeholdersEditar').html(res.placeholders.join(', '));
            },'json');
        } else {
            $('#btnEjemploEditar').hide();
            $('#placeholdersEditar').html('Selecciona un tipo para ver los placeholders.');
        }
    });

    $('#btnEjemploEditar').on('click', function(){
        var tipo = $(this).data('tipo');
        $.post('<?= BASE_URL ?>Ajax/plantillas-documentosA.php',{action:'obtenerEjemplo',tipo:tipo},function(res){
            CKEDITOR.instances.contenidoEditar.setData(res.ejemplo);
        },'json');
    });

    // Cargar datos en modal editar
    $('.tablaPlantillas tbody').on('click','.btnEditarPlantilla', function(){
        var id = $(this).data('id');
        $.post('<?= BASE_URL ?>Ajax/plantillas-documentosA.php',{action:'obtener',id_plantilla:id}, function(res){
            $('#idPlantillaEditar').val(res.id);
            $('#tituloEditar').val(res.titulo);
            $('#tipoEditar').val(res.tipo).trigger('change');
            CKEDITOR.instances.contenidoEditar.setData(res.contenido);
        },'json');
    });

    // Eliminar plantilla
    $('.tablaPlantillas tbody').on('click','.btnEliminarPlantilla', function(){
        var id = $(this).data('id');
        var titulo = $(this).data('nombre');
        Swal.fire({
            title:'¿Seguro?',
            text:`¡La plantilla "${titulo}" será eliminada!`,
            icon:'warning',
            showCancelButton:true,
            confirmButtonColor:'#d33',
            cancelButtonColor:'#3085d6',
            confirmButtonText:'Sí, eliminar'
        }).then((result)=>{
            if(result.isConfirmed){
                $.post('<?= BASE_URL ?>Ajax/plantillas-documentosA.php',{action:'eliminar',id_plantilla:id},function(res){
                    if(res.status=='success'){ tablaPlantillas.ajax.reload(null,false); Swal.fire('Eliminada','Plantilla eliminada.','success'); }
                    else Swal.fire('Error','No se pudo eliminar','error');
                },'json');
            }
        });
    });

});
</script>
<?php $scriptDinamico = ob_get_clean(); ?>
