<?php
$name = trim($_GET['name'] ?? '');
?>

<!doctype html>
<html lang="es">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Listado de Personas</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="css/style.css" rel="stylesheet">
</head>

<body>
  <div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
      <h1 class="h3 m-0">Listado de Personas</h1>
      <div class="d-flex gap-2">
        <!-- <a class="btn btn-outline-secondary btn-anim" href="?action=pdf" target="_blank">📄 PDF</a> -->
        <a class="btn btn-primary btn-anim" href="?action=create">➕ Nueva Persona</a>
      </div>
    </div>