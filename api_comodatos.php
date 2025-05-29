<?php
require_once 'config.php';

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        $sql = "SELECT id, delivery_datetime AS dataHorario, professor_name AS nomeProfessor, 
                       notebook_number AS numeroNotebook, projector_control_number AS numeroProjetor, 
                       return_date AS dataDevolucao
                FROM comodatos ORDER BY created_at DESC";
        $result = $conn->query($sql);

        $comodatos_data = [];
        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $row['dataHorario'] = date('d/m/Y H:i:s', strtotime($row['dataHorario']));
                $row['dataDevolucao'] = $row['dataDevolucao'] ? date('d/m/Y', strtotime($row['dataDevolucao'])) : '';
                $comodatos_data[] = $row;
            }
        }
        echo json_encode($comodatos_data);
        break;

    case 'POST':
        $input = json_decode(file_get_contents('php://input'), true);

        $dataHorario = $input['dataHorario'] ?? ''; // Já vem formatado, converter para DATETIME do MySQL
        $nomeProfessor = $input['nomeProfessor'] ?? '';
        $numeroNotebook = $input['numeroNotebook'] ?? null;
        $numeroProjetor = $input['numeroProjetor'] ?? null;
        $dataDevolucao = $input['dataDevolucao'] ?? null; // Pode ser vazia

        // Converte a data e hora para o formato DATETIME do MySQL
        $formatted_datetime = date('Y-m-d H:i:s', strtotime(str_replace('/', '-', $dataHorario)));
        $formatted_return_date = $dataDevolucao ? implode('-', array_reverse(explode('/', $dataDevolucao))) : null;

        $stmt = $conn->prepare("INSERT INTO comodatos (delivery_datetime, professor_name, notebook_number, projector_control_number, return_date) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $formatted_datetime, $nomeProfessor, $numeroNotebook, $numeroProjetor, $formatted_return_date);

        if ($stmt->execute()) {
            echo json_encode(["success" => true, "message" => "Comodato adicionado com sucesso!", "id" => $conn->insert_id]);
        } else {
            echo json_encode(["success" => false, "message" => "Erro ao adicionar Comodato: " . $stmt->error]);
        }
        $stmt->close();
        break;

    case 'PUT':
        $input = json_decode(file_get_contents('php://input'), true);
        $id = $input['id'] ?? '';

        $nomeProfessor = $input['nomeProfessor'] ?? '';
        $numeroNotebook = $input['numeroNotebook'] ?? null;
        $numeroProjetor = $input['numeroProjetor'] ?? null;
        $dataDevolucao = $input['dataDevolucao'] ?? null; // Pode ser vazia ou preenchida

        $formatted_return_date = $dataDevolucao ? implode('-', array_reverse(explode('/', $dataDevolucao))) : null;

        $stmt = $conn->prepare("UPDATE comodatos SET professor_name = ?, notebook_number = ?, projector_control_number = ?, return_date = ? WHERE id = ?");
        $stmt->bind_param("ssssi", $nomeProfessor, $numeroNotebook, $numeroProjetor, $formatted_return_date, $id);

        if ($stmt->execute()) {
            echo json_encode(["success" => true, "message" => "Comodato atualizado com sucesso!"]);
        } else {
            echo json_encode(["success" => false, "message" => "Erro ao atualizar Comodato: " . $stmt->error]);
        }
        $stmt->close();
        break;

    case 'DELETE':
        $input = json_decode(file_get_contents('php://input'), true);
        $id = $input['id'] ?? '';

        $stmt = $conn->prepare("DELETE FROM comodatos WHERE id = ?");
        $stmt->bind_param("i", $id);

        if ($stmt->execute()) {
            echo json_encode(["success" => true, "message" => "Comodato excluído com sucesso!"]);
        } else {
            echo json_encode(["success" => false, "message" => "Erro ao excluir Comodato: " . $stmt->error]);
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
