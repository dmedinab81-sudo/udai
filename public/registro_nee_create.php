<?php
require_once __DIR__ . '/../app/db.php';
require_once __DIR__ . '/../app/auth.php';
$pdo=getPDO();

function labelById(PDO $pdo, string $table, int $id, string $labelExpr='nombres'): string {
    if ($id <= 0) return '';
    $stmt = $pdo->prepare("SELECT $labelExpr as label FROM $table WHERE id=? LIMIT 1");
    $stmt->execute([$id]);
    return (string)($stmt->fetchColumn() ?: '');
}

$errors=[];
if($_SERVER['REQUEST_METHOD']==='POST'){
    $token = $_POST['csrf_token'] ?? null;
    if (!verify_csrf_token($token)) { set_flash('Error de seguridad: token CSRF inválido.'); header('Location: registro_nee.php'); exit; }

    $id_ubicacion=intval($_POST['id_ubicacion']??0);
    $id_docente_apoyo=intval($_POST['id_docente_apoyo']??0);
    $id_institucion=intval($_POST['id_institucion']??0);
    $id_estudiante=intval($_POST['id_estudiante']??0);
    $id_docente_tutor=intval($_POST['id_docente_tutor']??0);
    $id_representante=intval($_POST['id_representante']??0);

    $num_atenciones_estudiante=intval($_POST['num_atenciones_estudiante'] ?? 0);
    $num_atenciones_representante=intval($_POST['num_atenciones_representante'] ?? 0);
    $num_atenciones_docente=intval($_POST['num_atenciones_docente'] ?? 0);
    $num_docentes_estudiante=intval($_POST['num_docentes_estudiante'] ?? 0);

    // nuevos campos booleanos, vienen como '1' o '0'

    $enlace_gestion=trim($_POST['enlace_gestion']??'');
    $observaciones=trim($_POST['observaciones']??'');

    if(!$id_estudiante) $errors[]='Seleccione un estudiante.';
	
    require_login(); // ✅
    $u = currentUser();
    $usuario_id = $u ? intval($u['id']) : null;

	if(empty($errors)){
		$stmt=$pdo->prepare("INSERT INTO registro_nee (
			id_ubicacion,id_docente_apoyo,id_institucion,id_estudiante,id_docente_tutor,id_representante,
			num_atenciones_estudiante,num_atenciones_representante,num_atenciones_docente,num_docentes_estudiante,
			enlace_gestion,observaciones,
			creado_por, creado_en, actualizado_por, actualizado_en
		) VALUES (?,?,?,?,?,?,?,?,?,?,?, ?,?,
				  ?, NOW(), ?, NOW())");

		$stmt->execute([
			$id_ubicacion?:null,$id_docente_apoyo?:null,$id_institucion?:null,$id_estudiante?:null,$id_docente_tutor?:null,$id_representante?:null,
			$num_atenciones_estudiante,$num_atenciones_representante,$num_atenciones_docente,$num_docentes_estudiante,
			$enlace_gestion?:null,$observaciones?:null,
			$usuario_id, $usuario_id
		]);

		set_flash('Registro Atención creado.');
		header('Location: registro_nee.php'); exit;
    }
}
include __DIR__ . '/_header.php';
$id_ubicacion_v = intval($_POST['id_ubicacion'] ?? 0);
$id_docente_apoyo_v = intval($_POST['id_docente_apoyo'] ?? 0);
$id_institucion_v = intval($_POST['id_institucion'] ?? 0);
$id_estudiante_v = intval($_POST['id_estudiante'] ?? 0);
$id_docente_tutor_v = intval($_POST['id_docente_tutor'] ?? 0);
$id_representante_v = intval($_POST['id_representante'] ?? 0);

// helper para render select SI/NO
function select_si_no($name, $value) {
    $val = $value ? '1' : '0';
    return '<select class="form-select" name="'.htmlspecialchars($name).'"><option value="1" '.($val==='1'?'selected':'').'>SI</option><option value="0" '.($val==='0'?'selected':'').'>NO</option></select>';
}
?>
<div class="row"><div class="col-md-8 mx-auto">
<h2>Crear Registro de Atención</h2>
<?php foreach($errors as $er):?><div class="alert alert-danger"><?= htmlspecialchars($er) ?></div><?php endforeach; ?>
<form method="post">
  <?= csrf_input_html() ?>

  <div class="mb-3"><label class="form-label">Periodo</label><input class="form-control ajax-buscar" data-tipo="periodo" data-target="id_ubicacion" value="<?= htmlspecialchars(labelById($pdo,'ubicacion',$id_ubicacion_v,'mes')) ?>" placeholder="Buscar periodo..."><input type="hidden" name="id_ubicacion" id="id_ubicacion" value="<?= $id_ubicacion_v ?>"><div class="list-group mt-1 ajax-lista"></div></div>

  <div class="mb-3"><label class="form-label">Docente Apoyo</label><input class="form-control ajax-buscar" data-tipo="docente_apoyo" data-target="id_docente_apoyo" value="<?= htmlspecialchars(labelById($pdo,'docente_apoyo',$id_docente_apoyo_v)) ?>" placeholder="Buscar docente apoyo..."><input type="hidden" name="id_docente_apoyo" id="id_docente_apoyo" value="<?= $id_docente_apoyo_v ?>"><div class="list-group mt-1 ajax-lista"></div></div>

  <div class="mb-3"><label class="form-label">Institución</label><input class="form-control ajax-buscar" data-tipo="institucion" data-target="id_institucion" value="<?= htmlspecialchars(labelById($pdo,'institucion',$id_institucion_v,'nombre')) ?>" placeholder="Buscar institución..."><input type="hidden" name="id_institucion" id="id_institucion" value="<?= $id_institucion_v ?>"><div class="list-group mt-1 ajax-lista"></div></div>

  <div class="mb-3"><label class="form-label">Estudiante</label><input class="form-control ajax-buscar" data-tipo="estudiante" data-target="id_estudiante" value="<?= htmlspecialchars(labelById($pdo,'estudiante',$id_estudiante_v)) ?>" placeholder="Buscar estudiante..."><input type="hidden" name="id_estudiante" id="id_estudiante" value="<?= $id_estudiante_v ?>"><div class="list-group mt-1 ajax-lista"></div></div>

  <div class="mb-3"><label class="form-label">Docente Tutor</label><input class="form-control ajax-buscar" data-tipo="docente_tutor" data-target="id_docente_tutor" value="<?= htmlspecialchars(labelById($pdo,'docente_tutor',$id_docente_tutor_v)) ?>" placeholder="Buscar docente tutor..."><input type="hidden" name="id_docente_tutor" id="id_docente_tutor" value="<?= $id_docente_tutor_v ?>"><div class="list-group mt-1 ajax-lista"></div></div>

  <div class="mb-3"><label class="form-label">Representante</label><input class="form-control ajax-buscar" data-tipo="representante" data-target="id_representante" value="<?= htmlspecialchars(labelById($pdo,'representante_legal',$id_representante_v)) ?>" placeholder="Buscar representante..."><input type="hidden" name="id_representante" id="id_representante" value="<?= $id_representante_v ?>"><div class="list-group mt-1 ajax-lista"></div></div>

  <fieldset class="border rounded p-3 mb-3">
    <legend class="small px-2">Número de atenciones mensuales realizadas</legend>
    <div class="row g-2">
      <div class="col-md-6"><label class="form-label">Estudiante</label><input type="number" min="0" step="1" class="form-control" name="num_atenciones_estudiante" value="<?= htmlspecialchars($_POST['num_atenciones_estudiante'] ?? ($row['num_atenciones_estudiante'] ?? 0)) ?>"></div>
      <div class="col-md-6"><label class="form-label">Padre, Madre de familia o Representante legal</label><input type="number" min="0" step="1" class="form-control" name="num_atenciones_representante" value="<?= htmlspecialchars($_POST['num_atenciones_representante'] ?? ($row['num_atenciones_representante'] ?? 0)) ?>"></div>
      <div class="col-md-6"><label class="form-label">Docente</label><input type="number" min="0" step="1" class="form-control" name="num_atenciones_docente" value="<?= htmlspecialchars($_POST['num_atenciones_docente'] ?? ($row['num_atenciones_docente'] ?? 0)) ?>"></div>
      <div class="col-md-6"><label class="form-label">Número de docentes del estudiante</label><input type="number" min="0" step="1" class="form-control" name="num_docentes_estudiante" value="<?= htmlspecialchars($_POST['num_docentes_estudiante'] ?? ($row['num_docentes_estudiante'] ?? 0)) ?>"></div>
    </div>
  </fieldset>

  <div class="mb-3"><label class="form-label">Enlace Gestión</label><textarea class="form-control" name="enlace_gestion"><?= htmlspecialchars($_POST['enlace_gestion'] ?? '') ?></textarea></div>
  <div class="mb-3"><label class="form-label">Observaciones</label><textarea class="form-control" name="observaciones"><?= htmlspecialchars($_POST['observaciones'] ?? '') ?></textarea></div>
  <button class="btn btn-primary">Crear</button>
  <a class="btn btn-secondary" href="registro_nee.php">Cancelar</a>
</form>
</div></div>

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

<?php include __DIR__ . '/_footer.php'; ?>