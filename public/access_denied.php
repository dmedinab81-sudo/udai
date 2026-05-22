<?php
require_once __DIR__ . '/../app/auth.php';
include __DIR__ . '/_header.php';

// En teoría el header ya asegura que el usuario esté autenticado,
// por lo que aquí mostramos un mensaje más amigable.
?>
<div class="row">
  <div class="col-md-8 mx-auto">
    <div class="card border-danger">
      <div class="card-body">
        <h3 class="card-title text-danger">Acceso denegado</h3>
        <p class="card-text">No tienes permisos suficientes para ver esta página o realizar esta acción.</p>
        <p>Si piensas que deberías tener acceso, contacta al administrador del sistema.</p>
        <div class="mt-3">
          <a href="index.php" class="btn btn-primary">Volver al panel</a>
          <a href="logout.php" class="btn btn-outline-secondary">Cerrar sesión</a>
        </div>
      </div>
    </div>
  </div>
</div>
<?php include __DIR__ . '/_footer.php'; ?>