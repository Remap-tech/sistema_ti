<?php
require_once 'config.php';

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        $sql = "SELECT id, username, role FROM users ORDER BY username ASC";
        $result = $conn->query($sql);

        $users_data = [];
        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $users_data[] = $row;
            }
        }
        echo json_encode($users_data);
        break;

    case 'POST':
        $input = json_decode(file_get_contents('php://input'), true);

        $username = $input['username'] ?? '';
        $password = $input['password'] ?? '';
        $role = $input['role'] ?? '';

        // IMPORTANTE: Em um ambiente de produção, a senha DEVE ser hashada antes de ser salva!
        // Ex: $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $hashed_password = $password; // Apenas para demonstração

        $stmt = $conn->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $username, $hashed_password, $role);

        if ($stmt->execute()) {
            echo json_encode(["success" => true, "message" => "Usuário adicionado com sucesso!", "id" => $conn->insert_id]);
        } else {
            echo json_encode(["success" => false, "message" => "Erro ao adicionar usuário: " . $stmt->error]);
        }
        $stmt->close();
        break;

    case 'PUT':
        $input = json_decode(file_get_contents('php://input'), true);
        $id = $input['id'] ?? '';

        $username = $input['username'] ?? '';
        $password = $input['password'] ?? ''; // Nova senha, se fornecida
        $role = $input['role'] ?? '';

        $sql = "UPDATE users SET username = ?, role = ? ";
        $params = "ss";
        $values = [$username, $role];

        if (!empty($password)) {
            // Se uma nova senha for fornecida, hashá-la
            // IMPORTANTE: Em produção, hashar a senha!
            // $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $hashed_password = $password; // Apenas para demonstração
            $sql .= ", password = ? ";
            $params .= "s";
            $values[] = $hashed_password;
        }

        $sql .= "WHERE id = ?";
        $params .= "i";
        $values[] = $id;

        $stmt = $conn->prepare($sql);
        $stmt->bind_param($params, ...$values);

        if ($stmt->execute()) {
            echo json_encode(["success" => true, "message" => "Usuário atualizado com sucesso!"]);
        } else {
            echo json_encode(["success" => false, "message" => "Erro ao atualizar usuário: " . $stmt->error]);
        }
        $stmt->close();
        break;

    case 'DELETE':
        $input = json_decode(file_get_contents('php://input'), true);
        $id = $input['id'] ?? '';

        $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        $stmt->bind_param("i", $id);

        if ($stmt->execute()) {
            echo json_encode(["success" => true, "message" => "Usuário excluído com sucesso!"]);
        } else {
            echo json_encode(["success" => false, "message" => "Erro ao excluir usuário: " . $stmt->error]);
        }
        $stmt->close();
        break;

    default:
        http_response_code(405);
        echo json_encode(["message" => "Método não permitido."]);
        break;
}

$conn->close();
?>
