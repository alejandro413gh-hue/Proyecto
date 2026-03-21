<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../models/Producto.php';

define('IMG_DIR', __DIR__ . '/../assets/img/productos/');
define('IMG_MAX', 3 * 1024 * 1024); // 3MB
define('IMG_TYPES', ['image/jpeg','image/jpg','image/png','image/webp']);

class ProductoController {
    private $m;
    public function __construct() { requireLogin(); $this->m = new Producto(); }

    private function subirImagen($file) {
        if (!$file || $file['error'] !== UPLOAD_ERR_OK) return [null, null];

        if ($file['size'] > IMG_MAX)
            return [null, 'La imagen no puede pesar más de 3MB'];

        if (!in_array($file['type'], IMG_TYPES))
            return [null, 'Solo se permiten imágenes JPG, PNG o WEBP'];

        // Crear directorio si no existe
        if (!is_dir(IMG_DIR)) mkdir(IMG_DIR, 0755, true);

        $ext      = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = uniqid('prod_') . '.' . strtolower($ext);
        $dest     = IMG_DIR . $filename;

        if (!move_uploaded_file($file['tmp_name'], $dest))
            return [null, 'Error al guardar la imagen'];

        return [$filename, null];
    }

    private function borrarImagen($filename) {
        if ($filename && file_exists(IMG_DIR . $filename)) {
            unlink(IMG_DIR . $filename);
        }
    }

    public function create() {
        requireAdmin();
        $nombre = trim($_POST['nombre'] ?? '');
        $desc   = trim($_POST['descripcion'] ?? '');
        $precio = floatval($_POST['precio'] ?? 0);
        $stock  = intval($_POST['stock'] ?? 0);
        $cat    = intval($_POST['categoria_id'] ?? 0);

        if (empty($nombre) || $precio <= 0)
            return ['error' => 'Nombre y precio son obligatorios'];

        $imagen = null;
        if (!empty($_FILES['imagen']['name'])) {
            [$imagen, $err] = $this->subirImagen($_FILES['imagen']);
            if ($err) return ['error' => $err];
        }

        $conn = Database::getInstance()->getConnection();
        $s = $conn->prepare("INSERT INTO productos (nombre, descripcion, precio, stock, categoria_id, imagen) VALUES (?,?,?,?,?,?)");
        $s->bind_param("ssdiss", $nombre, $desc, $precio, $stock, $cat, $imagen);

        if ($s->execute()) return ['success' => 'Producto registrado correctamente'];
        return ['error' => 'Error al registrar el producto'];
    }

    public function update() {
        requireAdmin();
        $id     = intval($_POST['id'] ?? 0);
        $nombre = trim($_POST['nombre'] ?? '');
        $desc   = trim($_POST['descripcion'] ?? '');
        $precio = floatval($_POST['precio'] ?? 0);
        $stock  = intval($_POST['stock'] ?? 0);
        $cat    = intval($_POST['categoria_id'] ?? 0);

        if ($id <= 0 || empty($nombre) || $precio <= 0)
            return ['error' => 'Datos inválidos'];

        $conn = Database::getInstance()->getConnection();

        // Subir nueva imagen si se envió
        if (!empty($_FILES['imagen']['name'])) {
            [$nuevaImg, $err] = $this->subirImagen($_FILES['imagen']);
            if ($err) return ['error' => $err];

            // Borrar imagen anterior
            $old = $conn->prepare("SELECT imagen FROM productos WHERE id=?");
            $old->bind_param("i", $id); $old->execute();
            $row = $old->get_result()->fetch_assoc();
            if ($row) $this->borrarImagen($row['imagen']);

            $s = $conn->prepare("UPDATE productos SET nombre=?,descripcion=?,precio=?,stock=?,categoria_id=?,imagen=? WHERE id=?");
            $s->bind_param("ssdissi", $nombre, $desc, $precio, $stock, $cat, $nuevaImg, $id);
        } else {
            $s = $conn->prepare("UPDATE productos SET nombre=?,descripcion=?,precio=?,stock=?,categoria_id=? WHERE id=?");
            $s->bind_param("ssdiii", $nombre, $desc, $precio, $stock, $cat, $id);
        }

        if ($s->execute()) return ['success' => 'Producto actualizado correctamente'];
        return ['error' => 'Error al actualizar'];
    }

    public function delete() {
        requireAdmin();
        $id = intval($_POST['id'] ?? 0);
        if ($id <= 0) return ['error' => 'ID inválido'];

        // Borrar imagen si existe
        $conn = Database::getInstance()->getConnection();
        $s = $conn->prepare("SELECT imagen FROM productos WHERE id=?");
        $s->bind_param("i", $id); $s->execute();
        $row = $s->get_result()->fetch_assoc();
        if ($row) $this->borrarImagen($row['imagen']);

        if ($this->m->delete($id)) return ['success' => 'Producto eliminado'];
        return ['error' => 'Error al eliminar'];
    }
}

// Dispatch — IMPORTANTE: usar enctype multipart para imágenes
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    $c = new ProductoController();
    switch ($_POST['action']) {
        case 'create': echo json_encode($c->create()); break;
        case 'update': echo json_encode($c->update()); break;
        case 'delete': echo json_encode($c->delete()); break;
        default: echo json_encode(['error' => 'Acción inválida']);
    }
    exit();
}
?>
