<?php
class Cliente {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function getClienteById($id) {
        $stmt = $this->db->prepare('SELECT * FROM clientes WHERE id = :id');
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function createCliente($nombre, $email) {
        $stmt = $this->db->prepare('INSERT INTO clientes (nombre, email) VALUES (:nombre, :email)');
        $stmt->bindParam(':nombre', $nombre);
        $stmt->bindParam(':email', $email);
        $stmt->execute();
    }

    // Additional methods with security improvements...
}
?>