<h2 class="h5 mb-3">Nuevo Persona</h2>
<form method="post" action="?action=store" class="row g-3 needs-validation" novalidate>
  <div class="col-md-6"><label class="form-label">Primer Nombre</label><input required class="form-control" name="primer_nombre"></div>
  <div class="col-md-6"><label class="form-label">Segundo Nombre</label><input class="form-control" name="segundo_nombre"></div>
  <div class="col-md-6"><label class="form-label">Primer Apellido</label><input required class="form-control" name="primer_apellido"></div>
  <div class="col-md-6"><label class="form-label">Segundo Apellido</label><input class="form-control" name="segundo_apellido"></div>
  <div class="col-md-6"><label class="form-label">Correo</label><input class="form-control" name="correo" type="email"></div>
  <div class="col-md-6"><label class="form-label">Teléfono Personal</label><input class="form-control" name="telefono_personal"></div>
  <div class="col-md-6"><label class="form-label">Teléfono de Casa</label><input class="form-control" name="telefono_casa"></div>   
  <div class="col-md-6"><label class="form-label">Fecha de Nacimiento</label><input required class="form-control" name="fecha_nacimiento" type="date" data-date-format="DD/MM/YYYY"></div>
  <div class="col-md-6"><label class="form-label">Dirección</label><input required class="form-control" name="direccion"></div>
  <div class="col-md-6"><label class="form-label">Número de Documento</label><input required class="form-control" name="numero_documento"></div>
  <div class="col-md-6">
    <label class="form-label">Tipo de Documento</label>
    <select id="id_tipo_documento" name="id_tipo_documento" class="form-select" required title="Seleccione el tipo de documento">
      <?php 
      foreach($tiposDocumento as $tipo){
            echo "<option value=\"".($tipo['id_catalogo'])."\">".htmlspecialchars($tipo['descripcion'])."</option>";
      }
      ?>
    </select>
  </div>
  <div class="col-md-6">
    <label class="form-label">Género</label>
    <select id="id_genero" name="id_genero" class="form-select" required title="Seleccione el género">
      <?php 
      foreach($generos as $genero){
            echo "<option value=\"".($genero['id_catalogo'])."\">".htmlspecialchars($genero['descripcion'])."</option>";
      }
      ?>
    </select>
  </div>
  <div class="col-md-6">
    <label class="form-label">Estado Civil</label>
    <select id="id_estado_civil" name="id_estado_civil" class="form-select" required title="Seleccione el estado civil">
      <?php 
      foreach($estadosCiviles as $estado){
            echo "<option value=\"".($estado['id_catalogo'])."\">".htmlspecialchars($estado['descripcion'])."</option>";
      }
      ?>
    </select>
  </div>
  <div class="col-md-6">
    <label class="form-label">Nacionalidad</label>
    <select id="id_nacionalidad" name="id_nacionalidad" class="form-select" required title="Seleccione la nacionalidad">
      <?php 
      foreach($nacionalidades as $nacionalidad){
            echo "<option value=\"".($nacionalidad['id_catalogo'])."\">".htmlspecialchars($nacionalidad['descripcion'])."</option>";
      }
      ?>
    </select>
  </div>
  <div class="col-md-6">
    <label class="form-label">Municipio</label>
    <select id="id_municipio" name="id_municipio" class="form-select" required title="Seleccione el municipio">
      <?php 
      foreach($municipios as $municipio){
            echo "<option value=\"".($municipio['id_catalogo'])."\">".htmlspecialchars($municipio['descripcion'])."</option>";
      }
      ?>
    </select>
  </div>
  <div class="col-md-6">
    <label class="form-label">Profesión</label>
    <select id="id_profesion" name="id_profesion" class="form-select" required title="Seleccione la profesión">
      <?php 
      foreach($profesiones as $profesion){
            echo "<option value=\"".($profesion['id_catalogo'])."\">".htmlspecialchars($profesion['descripcion'])."</option>";
      }
      ?>
    </select>
  </div>
  <div class="col-12 d-flex gap-2"><a href="?action=index" class="btn btn-outline-secondary">Cancelar</a><button class="btn btn-primary">Guardar</button></div>
</form>
