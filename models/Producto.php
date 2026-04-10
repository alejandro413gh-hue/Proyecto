<?php

class Producto {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function create($nombre, $precio, $cantidad) {
        $stmt = $this->db->prepare("INSERT INTO productos (nombre, precio, cantidad) VALUES (?, ?, ?)");
        if (!$stmt) {
            throw new Exception('Prepare failed: ' . $this->db->error);
        }

        $stmt->bind_param('sdi', $nombre, $precio, $cantidad);

        if (!$stmt->execute()) {
            throw new Exception('Execute failed: ' . $stmt->error);
        }

        return $stmt->insert_id;
    }

    // Additional methods and improvements here
}

?>