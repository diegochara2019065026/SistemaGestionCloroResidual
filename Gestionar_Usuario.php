<?php
// Configuración de conexión
$host = 'localhost';
$db   = 'tu_base_de_datos';
$user = 'tu_usuario';
$pass = 'tu_contraseña';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
     $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
     throw new \PDOException($e->getMessage(), (int)$e->getCode());
}

// Procesar datos del formulario
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nombre = trim($_POST['nombre']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    // Encriptar contraseña
    $password_hash = password_hash($password, PASSWORD_DEFAULT);

    // Insertar en la base de datos
    $sql = "INSERT INTO usuarios (nombre, email, password_hash) VALUES (?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    
    try {
        $stmt->execute([$nombre, $email, $password_hash]);
        echo "Usuario registrado exitosamente.";
    } catch (Exception $e) {
        echo "Error: El email ya está registrado.";
    }
}
?>

<form action="register.php" method="POST">
    <label>Nombre:</label> <input type="text" name="nombre" required><br>
    <label>Email:</label> <input type="email" name="email" required><br>
    <label>Contraseña:</label> <input type="password" name="password" required><br>
    <button type="submit">Registrar</button>
</form>
