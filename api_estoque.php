<?php
require_once 'config.php';

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        $sql = "SELECT id, name, quantity, unit, location, description FROM stock_items ORDER BY name ASC";
        $result = $conn->query($sql);

        $stock_data = [];
        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $stock_data[] = $row;
            }
        }
        echo json_encode($stock_data);
        break;

    case 'POST':
        $input = json_decode(file_get_contents('php://input'), true);

        $name = $input['name'] ?? '';
        $quantity = $input['quantity'] ?? 0;
        $unit = $input['unit'] ?? '';
        $location = $input['location'] ?? '';
        $description = $input['description'] ?? null;

        $stmt = $conn->prepare("INSERT INTO stock_items (name, quantity, unit, location, description) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sisss", $name, $quantity, $unit, $location, $description);

        if ($stmt->execute()) {
            echo json_encode(["success" => true, "message" => "Item de estoque adicionado com sucesso!", "id" => $conn->insert_id]);
        } else {
            echo json_encode(["success" => false, "message" => "Erro ao adicionar item de estoque: " . $stmt->error]);
        }
        $stmt->close();
        break;

    case 'PUT':
        $input = json_decode(file_get_contents('php://input'), true);
        $id = $input['id'] ?? '';

        $name = $input['name'] ?? '';
        $quantity = $input['quantity'] ?? 0;
        $unit = $input['unit'] ?? '';
        $location = $input['location'] ?? '';
        $description = $input['description'] ?? null;

        $stmt = $conn->prepare("UPDATE stock_items SET name = ?, quantity = ?, unit = ?, location = ?, description = ? WHERE id = ?");
        $stmt->bind_param("sisssi", $name, $quantity, $unit, $location, $description, $id);

        if ($stmt->execute()) {
            echo json_encode(["success" => true, "message" => "Item de estoque atualizado com sucesso!"]);
        } else {
            echo json_encode(["success" => false, "message" => "Erro ao atualizar item de estoque: " . $stmt->error]);
        }
        $stmt->close();
        break;

    case 'DELETE':
        $input = json_decode(file_get_contents('php://input'), true);
        $id = $input['id'] ?? '';

        $stmt = $conn->prepare("DELETE FROM stock_items WHERE id = ?");
        $stmt->bind_param("i", $id);

        if ($stmt->execute()) {
            echo json_encode(["success" => true, "message" => "Item de estoque excluído com sucesso!"]);
        } else {
            echo json_encode(["success" => false, "message" => "Erro ao excluir item de estoque: " . $stmt->error]);
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
