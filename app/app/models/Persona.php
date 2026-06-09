<?php
// require_once __DIR__ . '/../config/Database.php';
class Persona
{
  public static function all(string $q = ''): array
  {
    $pdo = Database::pdo();
    if ($q !== '') {
      $stmt = $pdo->prepare("SELECT * FROM personas WHERE nombres LIKE :q1 OR apellidos LIKE :q2 OR correo LIKE :q3 OR tipo_persona LIKE :q4 ORDER BY id DESC");
      $p = "%$q%";
      $stmt->execute([':q1' => $p, ':q2' => $p, ':q3' => $p, ':q4' => $p]);
    } else {
      $stmt = $pdo->query("SELECT * FROM personas ORDER BY id DESC");
    }
    return $stmt->fetchAll();
  }
  public static function find(int $id): ?array
  {
    $pdo = Database::pdo();
    $st = $pdo->prepare("SELECT * FROM personas WHERE id=:id");
    $st->execute([':id' => $id]);
    $r = $st->fetch();
    return $r ?: null;
  }
  public static function create(array $d): int
  {
    $pdo = Database::pdo();
    $st = $pdo->prepare("INSERT INTO personas (nombres,apellidos,telefono,correo,tipo_persona) VALUES (:n,:a,:t,:c,:tp)");
    $st->execute([':n' => $d['nombres'], ':a' => $d['apellidos'], ':t' => $d['telefono'] ?? '', ':c' => $d['correo'], ':tp' => $d['tipo_persona']]);
    return (int)$pdo->lastInsertId();
  }
  public static function updateFull(int $id, array $d): bool
  {
    $pdo = Database::pdo();
    $sql = "UPDATE personas SET nombres=:n, apellidos=:a, telefono=:t, correo=:c, tipo_persona=:tp WHERE id=:id";
    $st = $pdo->prepare($sql);
    return $st->execute([':n' => $d['nombres'], ':a' => $d['apellidos'], ':t' => $d['telefono'] ?? '', ':c' => $d['correo'], ':tp' => $d['tipo_persona'], ':id' => $id]);
  }
  public static function delete(int $id): bool
  {
    $pdo = Database::pdo();
    $st = $pdo->prepare("DELETE FROM personas WHERE id=:id");
    return $st->execute([':id' => $id]);
  }
  public static function tipo_persona_options(): array
  {
    $pdo = Database::pdo();
    $st = $pdo->prepare("SELECT COLUMN_TYPE FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = 'empresa' AND TABLE_NAME = 'personas' AND COLUMN_NAME = 'tipo_persona';");
    $st->execute();
    $r = $st->fetch();
    return $r ?: ['Cliente', 'Proveedor', 'Empleado'];
  }
}
