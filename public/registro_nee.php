<?php
require_once __DIR__ . '/../app/db.php';
require_once __DIR__ . '/../app/auth.php';
include __DIR__ . '/_header.php';

$pdo = getPDO();

// -------------------- Filtros (GET) --------------------
$id_ubicacion = isset($_GET['id_ubicacion']) && $_GET['id_ubicacion'] !== '' ? (int)$_GET['id_ubicacion'] : null;
$id_docente_apoyo = isset($_GET['id_docente_apoyo']) && $_GET['id_docente_apoyo'] !== '' ? (int)$_GET['id_docente_apoyo'] : null;
$id_institucion   = isset($_GET['id_institucion']) && $_GET['id_institucion'] !== '' ? (int)$_GET['id_institucion'] : null;
$id_estudiante    = isset($_GET['id_estudiante']) && $_GET['id_estudiante'] !== '' ? (int)$_GET['id_estudiante'] : null;
$id_docente_tutor = isset($_GET['id_docente_tutor']) && $_GET['id_docente_tutor'] !== '' ? (int)$_GET['id_docente_tutor'] : null;
$id_representante = isset($_GET['id_representante']) && $_GET['id_representante'] !== '' ? (int)$_GET['id_representante'] : null;

// Listas para combos
function labelById(PDO $pdo, string $table, ?int $id, string $label='nombres'): string {
  if (!$id) return '';
  $stmt=$pdo->prepare("SELECT $label FROM $table WHERE id=? LIMIT 1"); $stmt->execute([$id]); return (string)($stmt->fetchColumn() ?: '');
}

// Construir WHERE dinámico
$where = [];
$params = [];

if ($id_ubicacion) { $where[] = "rn.id_ubicacion = ?"; $params[] = $id_ubicacion; }
if ($id_docente_apoyo) {
  $where[] = "rn.id_docente_apoyo = ?";
  $params[] = $id_docente_apoyo;
}
if ($id_institucion) {
  $where[] = "rn.id_institucion = ?";
  $params[] = $id_institucion;
}
if ($id_estudiante) { $where[] = "rn.id_estudiante = ?"; $params[] = $id_estudiante; }
if ($id_docente_tutor) { $where[] = "rn.id_docente_tutor = ?"; $params[] = $id_docente_tutor; }
if ($id_representante) { $where[] = "rn.id_representante = ?"; $params[] = $id_representante; }

$where_sql = !empty($where) ? ("WHERE " . implode(" AND ", $where)) : "";

// -------------------- Query principal --------------------
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
        $where_sql
        ORDER BY rn.id DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<div class="row"><div class="col-12">
  <h2>Registros de Atención
    <a href="registro_nee_create.php" class="btn btn-sm btn-success float-end">Crear</a>
  </h2>

  <!-- -------------------- Filtros UI -------------------- -->
  <form method="get" class="row gy-2 gx-2 align-items-end mb-3">
    <div class="col-md-4"><label class="form-label">Periodo</label><input class="form-control ajax-buscar" data-tipo="periodo" data-target="id_ubicacion" value="<?= htmlspecialchars(labelById($pdo,'ubicacion',$id_ubicacion,'mes')) ?>"><input type="hidden" name="id_ubicacion" id="id_ubicacion" value="<?= (int)$id_ubicacion ?>"><div class="list-group ajax-lista mt-1"></div></div>
    <div class="col-md-4"><label class="form-label">Docente Apoyo</label><input class="form-control ajax-buscar" data-tipo="docente_apoyo" data-target="id_docente_apoyo" value="<?= htmlspecialchars(labelById($pdo,'docente_apoyo',$id_docente_apoyo)) ?>"><input type="hidden" name="id_docente_apoyo" id="id_docente_apoyo" value="<?= (int)$id_docente_apoyo ?>"><div class="list-group ajax-lista mt-1"></div></div>
    <div class="col-md-4"><label class="form-label">Institución</label><input class="form-control ajax-buscar" data-tipo="institucion" data-target="id_institucion" value="<?= htmlspecialchars(labelById($pdo,'institucion',$id_institucion,'nombre')) ?>"><input type="hidden" name="id_institucion" id="id_institucion" value="<?= (int)$id_institucion ?>"><div class="list-group ajax-lista mt-1"></div></div>
    <div class="col-md-4"><label class="form-label">Estudiante</label><input class="form-control ajax-buscar" data-tipo="estudiante" data-target="id_estudiante" value="<?= htmlspecialchars(labelById($pdo,'estudiante',$id_estudiante)) ?>"><input type="hidden" name="id_estudiante" id="id_estudiante" value="<?= (int)$id_estudiante ?>"><div class="list-group ajax-lista mt-1"></div></div>
    <div class="col-md-4"><label class="form-label">Docente Tutor</label><input class="form-control ajax-buscar" data-tipo="docente_tutor" data-target="id_docente_tutor" value="<?= htmlspecialchars(labelById($pdo,'docente_tutor',$id_docente_tutor)) ?>"><input type="hidden" name="id_docente_tutor" id="id_docente_tutor" value="<?= (int)$id_docente_tutor ?>"><div class="list-group ajax-lista mt-1"></div></div>
    <div class="col-md-4"><label class="form-label">Representante</label><input class="form-control ajax-buscar" data-tipo="representante" data-target="id_representante" value="<?= htmlspecialchars(labelById($pdo,'representante_legal',$id_representante)) ?>"><input type="hidden" name="id_representante" id="id_representante" value="<?= (int)$id_representante ?>"><div class="list-group ajax-lista mt-1"></div></div>

    <div class="col-md-3 d-flex gap-2">
      <button type="submit" class="btn btn-primary w-100">Filtrar</button>
      <a href="registro_nee.php" class="btn btn-secondary w-100">Limpiar</a>
    </div>
  </form>

  <table class="table table-sm table-striped">
    <thead>
      <tr>
        <th>ID</th>
        <th>Ubicación</th>
        <th>Docente Apoyo</th>
        <th>Institución</th>
        <th>Estudiante</th>
        <th>Atenciones (E/R/D/ND)</th>
        <th>Creado por</th>
        <th>Creado en</th>
        <th>Actualizado por</th>
        <th>Actualizado en</th>
        <th>Acciones</th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($items)): ?>
        <tr><td colspan="11" class="text-center">No se encontraron registros.</td></tr>
      <?php else: foreach($items as $it): ?>
        <tr>
          <td><?= (int)$it['id'] ?></td>
          <td><?= htmlspecialchars($it['ubicacion_mes'] ?? '') ?></td>
          <td><?= htmlspecialchars($it['docente_apoyo_nom'] ?? '') ?></td>
          <td><?= htmlspecialchars($it['institucion_nom'] ?? '') ?></td>
          <td><?= htmlspecialchars($it['estudiante_nom'] ?? '') ?></td>
          <td><?= (int)($it['num_atenciones_estudiante'] ?? 0) ?>/<?= (int)($it['num_atenciones_representante'] ?? 0) ?>/<?= (int)($it['num_atenciones_docente'] ?? 0) ?>/<?= (int)($it['num_docentes_estudiante'] ?? 0) ?></td>

          <td><?= htmlspecialchars($it['creado_por_nombre'] ?? '') ?></td>
          <td><?= htmlspecialchars($it['creado_en'] ?? '') ?></td>
          <td><?= htmlspecialchars($it['actualizado_por_nombre'] ?? '') ?></td>
          <td><?= htmlspecialchars($it['actualizado_en'] ?? '') ?></td>

          <td>
            <a class="btn btn-sm btn-primary" href="registro_nee_edit.php?id=<?= (int)$it['id'] ?>">Editar</a>

            <form method="post" action="registro_nee_delete.php" style="display:inline" onsubmit="return confirm('Eliminar registro?')">
              <?= csrf_input_html() ?>
              <input type="hidden" name="id" value="<?= (int)$it['id'] ?>">
              <button class="btn btn-sm btn-danger">Eliminar</button>
            </form>
          </td>
        </tr>
      <?php endforeach; endif; ?>
    </tbody>
  </table>
</div></div>

<?php include __DIR__ . '/_footer.php'; ?>
<script>
document.querySelectorAll('.ajax-buscar').forEach(input => {
  const lista = input.parentElement.querySelector('.ajax-lista');
  const target = document.getElementById(input.dataset.target);
  input.addEventListener('input', function() {
    const q = this.value.trim(); if (q.length < 2) { lista.innerHTML=''; return; }
    fetch('api/buscar_catalogo.php?tipo='+encodeURIComponent(this.dataset.tipo)+'&q='+encodeURIComponent(q))
      .then(r=>r.json()).then(d=>{ lista.innerHTML=''; (d.items||[]).forEach(it=>{ const b=document.createElement('button'); b.type='button'; b.className='list-group-item list-group-item-action'; b.textContent=it.label; b.onclick=()=>{input.value=it.label; target.value=it.id; lista.innerHTML='';}; lista.appendChild(b); });});
  });
});
</script>