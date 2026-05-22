<?php
require_once __DIR__ . '/../app/db.php';
require_once __DIR__ . '/../app/auth.php';
include __DIR__ . '/_header.php';

$pdo = getPDO();

$sql = "SELECT rn.*,
               u.mes as ubicacion_mes, u.zona as ubicacion_zona, u.distrito as ubicacion_distrito,
               da.nombres as docente_apoyo_nom,
               i.nombre as institucion_nom,
               e.nombres as estudiante_nom,
               dt.nombres as docente_tutor_nom,
               rl.nombres as representante_nom,
               uc.nombre as creado_por_nombre,
               uu.nombre as actualizado_por_nombre
        FROM registro_nee rn
        LEFT JOIN ubicacion u ON rn.id_ubicacion=u.id
        LEFT JOIN docente_apoyo da ON rn.id_docente_apoyo=da.id
        LEFT JOIN institucion i ON rn.id_institucion=i.id
        LEFT JOIN estudiante e ON rn.id_estudiante=e.id
        LEFT JOIN docente_tutor dt ON rn.id_docente_tutor=dt.id
        LEFT JOIN representante_legal rl ON rn.id_representante=rl.id
        LEFT JOIN usuarios uc ON rn.creado_por = uc.id
        LEFT JOIN usuarios uu ON rn.actualizado_por = uu.id
        ORDER BY rn.id DESC";

$stmt = $pdo->query($sql);
$items = $stmt->fetchAll();

// función auxiliar para listar asignaturas activas en un registro
function asignaturas_list(array $r): string {
    $map = [
        'docente_tutor' => 'Docente Tutor',
        'lengua_y_literatura' => 'Lengua y Literatura',
        'matematica' => 'Matemática',
        'ciencias_naturales' => 'Ciencias Naturales',
        'fisica' => 'Física',
        'quimica' => 'Química',
        'biologia' => 'Biología',
        'estudios_sociales_historia' => 'Estudios Sociales / Historia',
        'ingles' => 'Inglés',
        'educacion_fisica' => 'Educación Física',
        'gestion_para_el_emprendimiento' => 'Gestión para el emprendimiento',
        'filosofia' => 'Filosofía'
    ];
    $out = [];
    foreach ($map as $field => $label) {
        if (!empty($r[$field])) $out[] = $label;
    }
    return implode(', ', $out);
}
?>
<div class="row"><div class="col-12">
<h2>Registros NEE <a href="registro_nee_create.php" class="btn btn-sm btn-success float-end">Crear</a></h2>

<table class="table table-sm table-striped">
  <thead>
    <tr>
      <th>ID</th>
      <th>Ubicación</th>
      <th>Docente Apoyo</th>
      <th>Institución</th>
      <th>Estudiante</th>
      <th>Asignaturas</th>
      <th>Creado por</th>
      <th>Creado en</th>
      <th>Actualizado por</th>
      <th>Actualizado en</th>
      <th>Acciones</th>
    </tr>
  </thead>
  <tbody>
    <?php foreach($items as $it): ?>
      <tr>
        <td><?= $it['id'] ?></td>
        <td><?= htmlspecialchars($it['ubicacion_mes'] ?? '') ?></td>
        <td><?= htmlspecialchars($it['docente_apoyo_nom'] ?? '') ?></td>
        <td><?= htmlspecialchars($it['institucion_nom'] ?? '') ?></td>
        <td><?= htmlspecialchars($it['estudiante_nom'] ?? '') ?></td>
        <td style="max-width:320px;"><?= htmlspecialchars(asignaturas_list($it)) ?></td>

        <td><?= htmlspecialchars($it['creado_por_nombre'] ?? '') ?></td>
        <td><?= htmlspecialchars($it['creado_en'] ?? '') ?></td>
        <td><?= htmlspecialchars($it['actualizado_por_nombre'] ?? '') ?></td>
        <td><?= htmlspecialchars($it['actualizado_en'] ?? '') ?></td>

        <td>
          <a class="btn btn-sm btn-primary" href="registro_nee_edit.php?id=<?= $it['id'] ?>">Editar</a>

          <form method="post" action="registro_nee_delete.php" style="display:inline" onsubmit="return confirm('Eliminar registro?')">
            <?= csrf_input_html() ?>
            <input type="hidden" name="id" value="<?= $it['id'] ?>">
            <button class="btn btn-sm btn-danger">Eliminar</button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
  </tbody>
</table>
</div></div>
<?php include __DIR__ . '/_footer.php'; ?>
