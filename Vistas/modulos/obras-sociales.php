<?php
if ($_SESSION["rol"] != "Administrador") { echo '<script>window.location = "inicio";</script>'; return; }

// Carga de clases
if (!class_exists('ObrasSocialesC')) {
    require_once "Controladores/ObrasSocialesC.php";
    require_once "Modelos/ObrasSocialesM.php";
}

$obras = ObrasSocialesC::ObtenerTodasC();
?>

<div class="content-wrapper">
    <section class="content-header">
        <h1>Gestor de Obras Sociales y Planes</h1>
        <ol class="breadcrumb"><li><a href="inicio"><i class="fa fa-dashboard"></i> Inicio</a></li><li class="active">Obras Sociales</li></ol>
    </section>

    <section class="content">
        <div class="box">
            <div class="box-header with-border">
                <button class="btn btn-primary" data-toggle="modal" data-target="#modalAgregarOS"><i class="fa fa-plus"></i> Agregar Entidad</button>
            </div>

            <div class="box-body">
                <table class="table table-bordered table-striped dt-responsive tablas" width="100%">
                    <thead><tr><th style="width:10px">#</th><th>Sigla</th><th>Nombre</th><th>Tipo</th><th>Planes Cargados</th><th>Acciones</th></tr></thead>
                    <tbody>
                        <?php foreach ($obras as $key => $value): 
                            // Contar planes para mostrar en la tabla
                            $planes = ObrasSocialesC::ObtenerPlanesPorOSC($value["id"]);
                            $cantPlanes = count($planes);
                        ?>
                            <tr>
                                <td><?= ($key + 1) ?></td>
                                <td><b><?= htmlspecialchars($value["sigla"] ?? '') ?></b></td>
                                <td><?= htmlspecialchars($value["nombre"]) ?></td>
                                <td><?= strtoupper($value["tipo"]) ?></td>
                                <td>
                                    <?php if($value["tipo"] != "particular"): ?>
                                        <span class="badge bg-teal"><?= $cantPlanes ?> Planes</span>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="btn-group">
                                        <!-- BOTÓN PLANES (Solo si no es particular) -->
                                        <?php if($value["tipo"] != "particular"): ?>
                                        <button class="btn btn-info btnGestionarPlanes" 
                                            data-id="<?= $value["id"] ?>" 
                                            data-nombre="<?= htmlspecialchars($value["nombre"]) ?>" 
                                            data-planes='<?= json_encode($planes) ?>'
                                            data-toggle="modal" data-target="#modalGestionarPlanes">
                                            <i class="fa fa-list-alt"></i> Planes
                                        </button>
                                        <?php endif; ?>

                                        <button class="btn btn-warning btnEditarOS" data-id="<?= $value["id"] ?>" data-nombre="<?= htmlspecialchars($value["nombre"]) ?>" data-sigla="<?= htmlspecialchars($value["sigla"] ?? '') ?>" data-tipo="<?= $value["tipo"] ?>" data-cuit="<?= htmlspecialchars($value["cuit"] ?? '') ?>" data-toggle="modal" data-target="#modalEditarOS"><i class="fa fa-pencil"></i></button>
                                        
                                        <?php if($value["id"] != 1): ?>
                                        <button class="btn btn-danger btnEliminarOS" data-id="<?= $value["id"] ?>" data-nombre="<?= htmlspecialchars($value["nombre"]) ?>"><i class="fa fa-times"></i></button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </section>
</div>

<!-- =========================================== -->
<!-- MODAL GESTIONAR PLANES (NUEVO)              -->
<!-- =========================================== -->
<div id="modalGestionarPlanes" class="modal fade" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header" style="background:#00c0ef; color:white">
                <button type="button" class="close" data-dismiss="modal">&times;</button>
                <h4 class="modal-title">Planes de <span id="titulo_os_planes"></span></h4>
            </div>
            <div class="modal-body">
                
                <!-- Formulario para agregar plan -->
                <form method="post" class="row">
                    <input type="hidden" name="id_os_plan" id="id_os_plan">
                    <div class="col-xs-8">
                        <input type="text" class="form-control" name="nuevo_plan_nombre" placeholder="Nombre del Plan (Ej: 210, Oro, Global)" required>
                    </div>
                    <div class="col-xs-4">
                        <button type="submit" class="btn btn-success btn-block">Agregar</button>
                    </div>
                    <?php $crearPlan = new ObrasSocialesC(); $crearPlan->CrearPlanC(); ?>
                </form>
                
                <hr>
                
                <!-- Lista de planes existentes -->
                <table class="table table-bordered table-hover">
                    <thead><tr><th>Nombre del Plan</th><th style="width:50px">Borrar</th></tr></thead>
                    <tbody id="tabla_planes_body">
                        <!-- Se llena con JS -->
                    </tbody>
                </table>

            </div>
            <div class="modal-footer"><button type="button" class="btn btn-default" data-dismiss="modal">Cerrar</button></div>
        </div>
    </div>
</div>

<!-- MODALES AGREGAR Y EDITAR OS (MISMOS DE ANTES) -->
<div id="modalAgregarOS" class="modal fade" role="dialog"><div class="modal-dialog"><form role="form" method="post"><div class="modal-content"><div class="modal-header" style="background:#3c8dbc; color:white"><button type="button" class="close" data-dismiss="modal">&times;</button><h4 class="modal-title">Agregar Obra Social</h4></div><div class="modal-body"><div class="box-body"><div class="form-group"><label>Nombre:</label><input type="text" class="form-control input-lg" name="nombre_nuevo" required></div><div class="form-group"><label>Sigla:</label><input type="text" class="form-control input-lg" name="sigla_nuevo"></div><div class="form-group"><label>Tipo:</label><select class="form-control input-lg" name="tipo_nuevo" required><option value="obra_social">Obra Social</option><option value="prepaga">Prepaga</option><option value="particular">Particular</option></select></div><div class="form-group"><label>CUIT:</label><input type="text" class="form-control input-lg" name="cuit_nuevo"></div></div></div><div class="modal-footer"><button type="button" class="btn btn-default pull-left" data-dismiss="modal">Salir</button><button type="submit" class="btn btn-primary">Guardar</button></div><?php $crearOS = new ObrasSocialesC(); $crearOS->CrearObraSocialC(); ?></div></form></div></div>

<div id="modalEditarOS" class="modal fade" role="dialog"><div class="modal-dialog"><form role="form" method="post"><div class="modal-content"><div class="modal-header" style="background:#f39c12; color:white"><button type="button" class="close" data-dismiss="modal">&times;</button><h4 class="modal-title">Editar Obra Social</h4></div><div class="modal-body"><div class="box-body"><input type="hidden" id="id_editar" name="id_editar"><div class="form-group"><label>Nombre:</label><input type="text" class="form-control input-lg" id="nombre_editar" name="nombre_editar" required></div><div class="form-group"><label>Sigla:</label><input type="text" class="form-control input-lg" id="sigla_editar" name="sigla_editar"></div><div class="form-group"><label>Tipo:</label><select class="form-control input-lg" id="tipo_editar" name="tipo_editar" required><option value="obra_social">Obra Social</option><option value="prepaga">Prepaga</option><option value="particular">Particular</option></select></div><div class="form-group"><label>CUIT:</label><input type="text" class="form-control input-lg" id="cuit_editar" name="cuit_editar"></div></div></div><div class="modal-footer"><button type="button" class="btn btn-default pull-left" data-dismiss="modal">Salir</button><button type="submit" class="btn btn-warning">Guardar Cambios</button></div><?php $editarOS = new ObrasSocialesC(); $editarOS->EditarObraSocialC(); ?></div></form></div></div>

<?php 
$borrarPlan = new ObrasSocialesC(); $borrarPlan->BorrarPlanC(); 
$borrarOS = new ObrasSocialesC(); $borrarOS->BorrarObraSocialC(); 
?>

<script>
$(function(){
    // JS PARA CARGAR PLANES EN EL MODAL
    $(".btnGestionarPlanes").click(function(){
        var idOS = $(this).attr("data-id");
        var nombre = $(this).attr("data-nombre");
        var planes = JSON.parse($(this).attr("data-planes"));

        $("#titulo_os_planes").text(nombre);
        $("#id_os_plan").val(idOS);
        
        var tbody = $("#tabla_planes_body");
        tbody.empty();

        if(planes.length === 0){
            tbody.append('<tr><td colspan="2" class="text-center">No hay planes cargados</td></tr>');
        } else {
            planes.forEach(function(plan){
                var fila = `<tr>
                    <td>${plan.nombre_plan}</td>
                    <td>
                        <button class="btn btn-danger btn-xs btnEliminarPlan" idPlan="${plan.id}">
                            <i class="fa fa-times"></i>
                        </button>
                    </td>
                </tr>`;
                tbody.append(fila);
            });
        }
    });

    // Borrar Plan con Alerta
    $(document).on("click", ".btnEliminarPlan", function(){
        var idPlan = $(this).attr("idPlan");
        Swal.fire({
            title: '¿Borrar plan?', text: "Se eliminará de la lista de opciones", icon: 'warning', showCancelButton: true, confirmButtonText: 'Sí, borrar'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location = "index.php?ruta=obras-sociales&idPlan=" + idPlan;
            }
        })
    });

    // (Lógica existente de editar/borrar OS...)
    $(".btnEditarOS").click(function(){
        $("#id_editar").val($(this).attr("data-id"));
        $("#nombre_editar").val($(this).attr("data-nombre"));
        $("#sigla_editar").val($(this).attr("data-sigla"));
        $("#tipo_editar").val($(this).attr("data-tipo"));
        $("#cuit_editar").val($(this).attr("data-cuit"));
    });
    $(".btnEliminarOS").click(function(){
        var idOS = $(this).attr("data-id");
        Swal.fire({title: '¿Borrar?', icon: 'warning', showCancelButton: true, confirmButtonText: 'Sí'}).then((result) => {
            if (result.isConfirmed) { window.location = "index.php?ruta=obras-sociales&idObraSocial=" + idOS; }
        })
    });
    
    $('.tablas').DataTable({ "language": { "url": "Vistas/plugins/datatables/Spanish.json" } });
});
</script>