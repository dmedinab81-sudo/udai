<?php
require_once __DIR__ . '/../app/db.php';
require_once __DIR__ . '/../app/auth.php';
$pdo=getPDO();

$id=intval($_GET['id']??0);
$stmt=$pdo->prepare("SELECT * FROM registro_nee WHERE id=? LIMIT 1"); $stmt->execute([$id]); $row=$stmt->fetch();
if(!$row){ header('Location: registro_nee.php'); exit; }

$ubicaciones=$pdo->query("SELECT id, mes FROM ubicacion ORDER BY mes")->fetchAll();
$docentes_apoyo=$pdo->query("SELECT id, nombres FROM docente_apoyo ORDER BY nombres")->fetchAll();
$instituciones=$pdo->query("SELECT id, nombre FROM institucion ORDER BY nombre")->fetchAll();
$estudiantes=$pdo->query("SELECT id, nombres FROM estudiante ORDER BY nombres")->fetchAll();
$docentes_tutor=$pdo->query("SELECT id, nombres FROM docente_tutor ORDER BY nombres")->fetchAll();
$representantes=$pdo->query("SELECT id, nombres FROM representante_legal ORDER BY nombres")->fetchAll();

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

    // nuevos campos
    $docente_tutor = intval($_POST['docente_tutor'] ?? 0);
    $lengua_y_literatura = intval($_POST['lengua_y_literatura'] ?? 0);
    $matematica = intval($_POST['matematica'] ?? 0);
    $ciencias_naturales = intval($_POST['ciencias_naturales'] ?? 0);
    $fisica = intval($_POST['fisica'] ?? 0);
    $quimica = intval($_POST['quimica'] ?? 0);
    $biologia = intval($_POST['biologia'] ?? 0);
    $estudios_sociales_historia = intval($_POST['estudios_sociales_historia'] ?? 0);
    $ingles = intval($_POST['ingles'] ?? 0);
    $educacion_fisica = intval($_POST['educacion_fisica'] ?? 0);
    $gestion_para_el_emprendimiento = intval($_POST['gestion_para_el_emprendimiento'] ?? 0);
    $filosofia = intval($_POST['filosofia'] ?? 0);

    $enlace_gestion=trim($_POST['enlace_gestion']??'');
    $observaciones=trim($_POST['observaciones']??'');

    if(!$id_estudiante) $errors[]='Seleccione un estudiante.';
    require_login(); // ✅ obligar sesión
	$u = currentUser();
	$usuario_id = $u ? intval($u['id']) : null;


	if(empty($errors)){
		$stmt=$pdo->prepare("UPDATE registro_nee SET
			id_ubicacion=?, id_docente_apoyo=?, id_institucion=?, id_estudiante=?, id_docente_tutor=?, id_representante=?,
			docente_tutor=?, lengua_y_literatura=?, matematica=?, ciencias_naturales=?, fisica=?, quimica=?, biologia=?, estudios_sociales_historia=?, ingles=?, educacion_fisica=?, gestion_para_el_emprendimiento=?, filosofia=?,
			enlace_gestion=?, observaciones=?,
			actualizado_por=?, actualizado_en=NOW()
		WHERE id=?");

		$stmt->execute([
			$id_ubicacion?:null,$id_docente_apoyo?:null,$id_institucion?:null,$id_estudiante?:null,$id_docente_tutor?:null,$id_representante?:null,
			$docente_tutor,$lengua_y_literatura,$matematica,$ciencias_naturales,$fisica,$quimica,$biologia,$estudios_sociales_historia,$ingles,$educacion_fisica,$gestion_para_el_emprendimiento,$filosofia,
			$enlace_gestion?:null,$observaciones?:null,
			$usuario_id,
			$id
		]);

		set_flash('Registro NEE actualizado.');
		header('Location: registro_nee.php'); exit;
    }
}
include __DIR__ . '/_header.php';

function select_si_no($name, $value) {
    $val = $value ? '1' : '0';
    return '<select class="form-select" name="'.htmlspecialchars($name).'"><option value="1" '.($val==='1'?'selected':'').'>SI</option><option value="0" '.($val==='0'?'selected':'').'>NO</option></select>';
}
?>
<div class="row"><div class="col-md-8 mx-auto">
<h2>Editar Registro NEE</h2>
<?php foreach($errors as $er):?><div class="alert alert-danger"><?= htmlspecialchars($er) ?></div><?php endforeach; ?>
<form method="post">
  <?= csrf_input_html() ?>
  <div class="mb-3"><label class="form-label">Periodo</label>
    <select class="form-select" name="id_ubicacion">
      <option value="">--</option>
      <?php foreach($ubicaciones as $u): ?><option value="<?= $u['id'] ?>" <?= (($row['id_ubicacion']==$u['id'])|| (($_POST['id_ubicacion']??'')==$u['id']))?'selected':'' ?>><?= htmlspecialchars($u['mes']) ?></option><?php endforeach; ?>
    </select>
  </div>

  <div class="mb-3"><label class="form-label">Docente Apoyo</label>
    <select class="form-select" name="id_docente_apoyo"><option value="">--</option><?php foreach($docentes_apoyo as $d):?><option value="<?= $d['id'] ?>" <?= (($row['id_docente_apoyo']==$d['id'])|| (($_POST['id_docente_apoyo']??'')==$d['id']))?'selected':'' ?>><?= htmlspecialchars($d['nombres']) ?></option><?php endforeach;?></select>
  </div>

  <div class="mb-3"><label class="form-label">Institución</label>
    <select class="form-select" name="id_institucion"><option value="">--</option><?php foreach($instituciones as $i):?><option value="<?= $i['id'] ?>" <?= (($row['id_institucion']==$i['id'])|| (($_POST['id_institucion']??'')==$i['id']))?'selected':'' ?>><?= htmlspecialchars($i['nombre']) ?></option><?php endforeach;?></select>
  </div>

  <div class="mb-3"><label class="form-label">Estudiante</label>
    <select class="form-select" name="id_estudiante"><option value="">--</option><?php foreach($estudiantes as $e):?><option value="<?= $e['id'] ?>" <?= (($row['id_estudiante']==$e['id'])|| (($_POST['id_estudiante']??'')==$e['id']))?'selected':'' ?>><?= htmlspecialchars($e['nombres']) ?></option><?php endforeach;?></select>
  </div>

  <div class="mb-3"><label class="form-label">Docente Tutor</label>
    <select class="form-select" name="id_docente_tutor"><option value="">--</option><?php foreach($docentes_tutor as $dt):?><option value="<?= $dt['id'] ?>" <?= (($row['id_docente_tutor']==$dt['id'])|| (($_POST['id_docente_tutor']??'')==$dt['id']))?'selected':'' ?>><?= htmlspecialchars($dt['nombres']) ?></option><?php endforeach;?></select>
  </div>

  <div class="mb-3"><label class="form-label">Representante</label>
    <select class="form-select" name="id_representante"><option value="">--</option><?php foreach($representantes as $r):?><option value="<?= $r['id'] ?>" <?= (($row['id_representante']==$r['id'])|| (($_POST['id_representante']??'')==$r['id']))?'selected':'' ?>><?= htmlspecialchars($r['nombres']) ?></option><?php endforeach;?></select>
  </div>

  <fieldset class="border rounded p-3 mb-3">
    <legend class="small px-2">Atención a docentes por asignatura</legend>
    <div class="row g-2">
      <?php
      $fields = [
        'docente_tutor'=>'Docente Tutor','lengua_y_literatura'=>'Lengua y Literatura','matematica'=>'Matemática',
        'ciencias_naturales'=>'Ciencias Naturales','fisica'=>'Física','quimica'=>'Química','biologia'=>'Biología',
        'estudios_sociales_historia'=>'Estudios Sociales / Historia','ingles'=>'Inglés','educacion_fisica'=>'Educación Física',
        'gestion_para_el_emprendimiento'=>'Gestión para el emprendimiento','filosofia'=>'Filosofía'
      ];
      foreach ($fields as $fname => $flabel):
        $val = isset($_POST[$fname]) ? intval($_POST[$fname]) : intval($row[$fname] ?? 0);
      ?>
      <div class="col-md-6">
        <label class="form-label"><?= htmlspecialchars($flabel) ?></label>
        <?= select_si_no($fname, $val) ?>
      </div>
      <?php endforeach; ?>
    </div>
  </fieldset>

  <div class="mb-3"><label class="form-label">Enlace Gestión</label><textarea class="form-control" name="enlace_gestion"><?= htmlspecialchars($_POST['enlace_gestion'] ?? $row['enlace_gestion']) ?></textarea></div>
  <div class="mb-3"><label class="form-label">Observaciones</label><textarea class="form-control" name="observaciones"><?= htmlspecialchars($_POST['observaciones'] ?? $row['observaciones']) ?></textarea></div>
  <button class="btn btn-primary">Guardar</button>
  <a class="btn btn-secondary" href="registro_nee.php">Cancelar</a>
</form>
</div></div>
<?php include __DIR__ . '/_footer.php'; ?>