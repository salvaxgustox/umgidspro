<?php /* listado */ ?>
<form class="row g-2 mb-3" method="get">
  <input type="hidden" name="action" value="index">
  <div class="col-sm-9 col-md-10"><input type="search" class="form-control" name="q" value="<?php echo htmlspecialchars($_GET['q'] ?? ''); ?>" placeholder="Buscar por nombre, apellido, correo, telefono o tipo de persona..."></div>
  <div class="col-sm-3 col-md-2 d-grid"><button class="btn btn-outline-primary btn-anim">Buscar</button></div>
</form>
<div class="table-responsive fade-in">
  <table class="table table-hover align-middle">
    <thead class="table-light">
      <tr>
        <th>ID</th>
        <th>Primer Nombre</th>
        <th>Segundo Nombre</th>
        <th>Primer Apellido</th>
        <th>Segundo Apellido</th>
        <th>Tipo Documento</th>
        <th>Número Documento</th> 
        <th>Fecha Nacimiento</th>
        <th>Género</th>
        <th>Dirección</th>
        <th>Estado Civil</th>
        <th>Nacionalidad</th>
        <th>Profesión</th>
        <th>Municipio</th>
        <th>Correo</th>
        <th>Teléfono Personal</th>
        <th>Teléfono Casa</th>
        <th>Fecha de creación</th>
        <th>Fecha de actualización</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($personas as $u): ?>
        <tr>
          <td><?php echo (int)$u['id_persona']; ?></td>
          <td><?php echo htmlspecialchars(($u['primer_nombre']??'')); ?></td>
          <td><?php echo htmlspecialchars(($u['segundo_nombre']??'')); ?></td>
          <td><?php echo htmlspecialchars(($u['primer_apellido']??'')); ?></td>
          <td><?php echo htmlspecialchars(($u['segundo_apellido']??'')); ?></td>
          <td><?php echo htmlspecialchars(($u['tipo_documento']??'')); ?></td>
          <td><?php echo htmlspecialchars(($u['numero_documento']??'')); ?></td>
          <td><?php echo htmlspecialchars(($u['fecha_nacimiento']??'')); ?></td>
          <td><?php echo htmlspecialchars(($u['genero']??'')); ?></td>
          <td><?php echo htmlspecialchars(($u['direccion']??'')); ?></td>
          <td><?php echo htmlspecialchars(($u['estado_civil']??'')); ?></td>
          <td><?php echo htmlspecialchars(($u['nacionalidad']??'')); ?></td>
          <td><?php echo htmlspecialchars(($u['profesion']??'')); ?></td>
          <td><?php echo htmlspecialchars(($u['municipio']??'')); ?></td>
          <td><?php echo htmlspecialchars(($u['correo']??'')); ?></td>
          <td><?php echo htmlspecialchars(($u['telefono_personal']??'')); ?></td>
          <td><?php echo htmlspecialchars(($u['telefono_casa']??'')); ?></td>
          <td><?php echo htmlspecialchars(($u['fecha_creacion']??'')); ?></td>
          <td><?php echo htmlspecialchars(($u['fecha_actualizacion']??'')); ?></td>
          <td class="d-flex gap-2">
            <a class="btn btn-primary btn-sm" href="?action=edit&id=<?php echo (int)$u['id_persona']; ?>"><i class="bi bi-pencil"></i>Editar</a>
            <form method="post" action="?action=destroy" onsubmit="return confirm('¿Eliminar el persona #<?php echo (int)$u['id_persona']; ?>?');">
              <input type="hidden" name="id" value="<?php echo (int)$u['id_persona']; ?>">
              <button class="btn btn-danger btn-sm btn-anim">Eliminar</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>