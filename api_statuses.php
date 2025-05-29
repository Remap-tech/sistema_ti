<?php
require_once 'config.php';

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        $sql = "SELECT id, name FROM custom_statuses ORDER BY name ASC";
        $result = $conn->query($sql);

        $statuses_data = [];
        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $statuses_data[] = $row;
            }
        }
        echo json_encode($statuses_data);
        break;

    case 'POST':
        $input = json_decode(file_get_contents('php://input'), true);

        $name = $input['name'] ?? '';

        $stmt = $conn->prepare("INSERT INTO custom_statuses (name) VALUES (?)");
        $stmt->bind_param("s", $name);

        if ($stmt->execute()) {
            echo json_encode(["success" => true, "message" => "Status adicionado com sucesso!", "id" => $conn->insert_id]);
        } else {
            echo json_encode(["success" => false, "message" => "Erro ao adicionar status: " . $stmt->error]);
        }
        $stmt->close();
        break;

    case 'PUT':
        $input = json_decode(file_get_contents('php://input'), true);
        $id = $input['id'] ?? '';
        $name = $input['name'] ?? '';

        $stmt = $conn->prepare("UPDATE custom_statuses SET name = ? WHERE id = ?");
        $stmt->bind_param("si", $name, $id);

        if ($stmt->execute()) {
            echo json_encode(["success" => true, "message" => "Status atualizado com sucesso!"]);
        } else {
            echo json_encode(["success" => false, "message" => "Erro ao atualizar status: " . $stmt->error]);
        }
        $stmt->close();
        break;

    case 'DELETE':
        $input = json_decode(file_get_contents('php://input'), true);
        $id = $input['id'] ?? '';

        $stmt = $conn->prepare("DELETE FROM custom_statuses WHERE id = ?");
        $stmt->bind_param("i", $id);

        if ($stmt->execute()) {
            echo json_encode(["success" => true, "message" => "Status excluído com sucesso!"]);
        } else {
            echo json_encode(["success" => false, "message" => "Erro ao excluir status: " . $stmt->error]);
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
