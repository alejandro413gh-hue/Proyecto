<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../models/Usuario.php';

class AuthController {
    private $m;
    public function __construct() { $this->m = new Usuario(); }

    public function login() {
        if($_SERVER['REQUEST_METHOD']!=='POST') return['error'=>'Método no permitido'];
        $email=trim($_POST['email']??'');
        $pass=$_POST['password']??'';
        if(empty($email)||empty($pass)) return['error'=>'Completa todos los campos'];
        if(!filter_var($email,FILTER_VALIDATE_EMAIL)) return['error'=>'Correo inválido'];
        $u=$this->m->findByEmail($email);
        if(!$u||!$this->m->verifyPassword($pass,$u['password'])) return['error'=>'Credenciales incorrectas'];
        $_SESSION['user_id']=$u['id'];
        $_SESSION['user_nombre']=$u['nombre'];
        $_SESSION['user_email']=$u['email'];
        $_SESSION['user_rol']=$u['rol'];
        return['success'=>true];
    }
    public function logout() { session_destroy(); header('Location:'.BASE_URL.'/index.php'); exit(); }
}

if(isset($_GET['action'])&&$_GET['action']==='logout'){
    $c=new AuthController();$c->logout();
}
?>
