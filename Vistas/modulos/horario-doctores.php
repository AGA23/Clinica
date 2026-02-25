<?php
// Formulario para gestionar múltiples horarios por doctor
foreach($horarios as $horario): ?>
    <div class="horario-item">
        <select name="dia_semana[]">
            <option value="1" <?= $horario['dia_semana']==1?'selected':'' ?>>Lunes</option>
            <!-- ... otros días ... -->
        </select>
        <input type="time" name="hora_inicio[]" value="<?= $horario['hora_inicio'] ?>">
        <input type="time" name="hora_fin[]" value="<?= $horario['hora_fin'] ?>">
    </div>
<?php endforeach; ?>