<?php
// config.php - Configurações de Conexão com o Banco de Dados

// Defina as credenciais do seu banco de dados MySQL
define('DB_SERVER', 'localhost'); // Geralmente 'localhost' para ambiente local
define('DB_USERNAME', 'root');    // Usuário padrão do MySQL no XAMPP/WAMP
define('DB_PASSWORD', '');        // Senha padrão do MySQL no XAMPP/WAMP (geralmente vazia)
define('DB_NAME', 'school_system'); // **MUDE PARA O NOME DO SEU BANCO DE DADOS**

// Tentativa de conexão com o banco de dados
$conn = new mysqli(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);

// Verifica a conexão
if ($conn->connect_error) {
    // Em produção, você pode querer logar o erro e mostrar uma mensagem genérica ao usuário
    die("Erro na conexão com o banco de dados: " . $conn->connect_error);
}

// Define o charset para garantir que caracteres especiais sejam tratados corretamente
$conn->set_charset("utf8mb4");

// Configurações para CORS (Cross-Origin Resource Sharing)
// IMPORTANTE: Em ambiente de produção, substitua '*' pelo domínio do seu frontend
header('Access-Control-Allow-Origin: *'); 
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Content-Type: application/json'); // Respostas sempre em JSON

// Trata requisições OPTIONS (necessário para preflight CORS em alguns casos)
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Função para gerar um ID único (PHP-side, para consistência)
function generateUniqueId($prefix) {
    return $prefix . time() . '-' . mt_rand(100, 999);
}

?>
