<?php
require_once 'config.php';

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        $sql = "SELECT id, occurrence_number AS ocorrenciaNumber, occurrence_date AS date, occurrence_type AS tipoOcorrencia, 
                       equipment, other_equipment_spec AS otherEquipment, description, involved_people AS pessoasEnvolvidas, 
                       responsible_person AS responsavel, status, resolution, responsible_signature_name AS responsibleSignatureName, 
                       occurrence_party_signature_name AS occurrencePartySignatureName
                FROM occurrences ORDER BY created_at DESC";
        $result = $conn->query($sql);

        $ocorrencias_data = [];
        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $row['date'] = date('d/m/Y', strtotime($row['date']));
                $row['fotos'] = []; // Placeholder, pois o frontend espera um array. Upload real exigiria mais lógica.
                $ocorrencias_data[] = $row;
            }
        }
        echo json_encode($ocorrencias_data);
        break;

    case 'POST':
        $input = json_decode(file_get_contents('php://input'), true);

        $ocorrenciaNumber = generateUniqueId('OC-');
        $date = $input['date'] ?? '';
        $tipoOcorrencia = $input['tipoOcorrencia'] ?? '';
        $equipment = $input['equipment'] ?? '';
        $otherEquipment = $input['otherEquipment'] ?? null;
        $description = $input['description'] ?? '';
        $pessoasEnvolvidas = $input['pessoasEnvolvidas'] ?? null;
        $responsavel = $input['responsavel'] ?? '';
        $status = $input['status'] ?? '';
        $resolution = $input['resolution'] ?? null;
        $responsibleSignatureName = $input['responsibleSignatureName'] ?? null;
        $occurrencePartySignatureName = $input['occurrencePartySignatureName'] ?? null;
        // Fotos seriam tratadas aqui se fosse um upload real (multipart/form-data)
        // Por enquanto, o frontend só envia nomes de arquivos, que não seriam persistidos aqui.

        $formatted_date = implode('-', array_reverse(explode('/', $date)));

        $stmt = $conn->prepare("INSERT INTO occurrences (occurrence_number, occurrence_date, occurrence_type, equipment, other_equipment_spec, description, involved_people, responsible_person, status, resolution, responsible_signature_name, occurrence_party_signature_name) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssssssssss", $ocorrenciaNumber, $formatted_date, $tipoOcorrencia, $equipment, $otherEquipment, $description, $pessoasEnvolvidas, $responsavel, $status, $resolution, $responsibleSignatureName, $occurrencePartySignatureName);

        if ($stmt->execute()) {
            echo json_encode(["success" => true, "message" => "Ocorrência adicionada com sucesso!", "id" => $conn->insert_id, "ocorrenciaNumber" => $ocorrenciaNumber]);
        } else {
            echo json_encode(["success" => false, "message" => "Erro ao adicionar Ocorrência: " . $stmt->error]);
        }
        $stmt->close();
        break;

    case 'PUT':
        $input = json_decode(file_get_contents('php://input'), true);
        $id = $input['id'] ?? '';

        $tipoOcorrencia = $input['tipoOcorrencia'] ?? '';
        $equipment = $input['equipment'] ?? '';
        $otherEquipment = $input['otherEquipment'] ?? null;
        $description = $input['description'] ?? '';
        $pessoasEnvolvidas = $input['pessoasEnvolvidas'] ?? null;
        $responsavel = $input['responsavel'] ?? '';
        $status = $input['status'] ?? '';
        $resolution = $input['resolution'] ?? null;
        $responsibleSignatureName = $input['responsibleSignatureName'] ?? null;
        $occurrencePartySignatureName = $input['occurrencePartySignatureName'] ?? null;

        $stmt = $conn->prepare("UPDATE occurrences SET occurrence_type = ?, equipment = ?, other_equipment_spec = ?, description = ?, involved_people = ?, responsible_person = ?, status = ?, resolution = ?, responsible_signature_name = ?, occurrence_party_signature_name = ? WHERE id = ?");
        $stmt->bind_param("ssssssssssi", $tipoOcorrencia, $equipment, $otherEquipment, $description, $pessoasEnvolvidas, $responsavel, $status, $resolution, $responsibleSignatureName, $occurrencePartySignatureName, $id);

        if ($stmt->execute()) {
            echo json_encode(["success" => true, "message" => "Ocorrência atualizada com sucesso!"]);
        } else {
            echo json_encode(["success" => false, "message" => "Erro ao atualizar Ocorrência: " . $stmt->error]);
        }
        $stmt->close();
        break;

    case 'DELETE':
        $input = json_decode(file_get_contents('php://input'), true);
        $id = $input['id'] ?? '';

        $stmt = $conn->prepare("DELETE FROM occurrences WHERE id = ?");
        $stmt->bind_param("i", $id);

        if ($stmt->execute()) {
            echo json_encode(["success" => true, "message" => "Ocorrência excluída com sucesso!"]);
        } else {
            echo json_encode(["success" => false, "message" => "Erro ao excluir Ocorrência: " . $stmt->error]);
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
