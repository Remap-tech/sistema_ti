<?php
require_once 'config.php';

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        $sql = "SELECT id, os_number, order_date AS date, user_sector AS setorUsuario, user_name AS nomeUsuario, 
                       equipment_type AS tipoEquipamento, call_type AS tipoChamado, status, description AS descricao, 
                       responsible_technician AS tecnicoResponsavel, resolution, services_performed AS servicesPerformed, 
                       user_signature_name AS userSignatureName
                FROM service_orders ORDER BY created_at DESC";
        $result = $conn->query($sql);

        $os_data = [];
        if ($result->num_rows > 0) {
            while($row = $result->fetch_assoc()) {
                $row['date'] = date('d/m/Y', strtotime($row['date']));
                // Peças utilizadas (assumindo que são armazenadas como JSON ou string para simplificar aqui)
                // Em um sistema real, você faria um JOIN com a tabela os_parts_used
                $row['partsUsed'] = []; // Placeholder, pois o frontend espera um array
                $os_data[] = $row;
            }
        }
        echo json_encode($os_data);
        break;

    case 'POST':
        $input = json_decode(file_get_contents('php://input'), true);

        $osNumber = generateUniqueId('OS-'); // Gerado no backend PHP
        $date = $input['date'] ?? ''; 
        $setorUsuario = $input['setorUsuario'] ?? '';
        $nomeUsuario = $input['nomeUsuario'] ?? '';
        $tipoEquipamento = $input['tipoEquipamento'] ?? '';
        $tipoChamado = $input['tipoChamado'] ?? '';
        $status = $input['status'] ?? '';
        $descricao = $input['descricao'] ?? '';
        $tecnicoResponsavel = $input['tecnicoResponsavel'] ?? null;
        $resolution = $input['resolution'] ?? null;
        $servicesPerformed = $input['servicesPerformed'] ?? null;
        $userSignatureName = $input['userSignatureName'] ?? null;
        
        $formatted_date = implode('-', array_reverse(explode('/', $date)));

        $stmt = $conn->prepare("INSERT INTO service_orders (os_number, order_date, user_sector, user_name, equipment_type, call_type, status, description, responsible_technician, resolution, services_performed, user_signature_name) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssssssssss", $osNumber, $formatted_date, $setorUsuario, $nomeUsuario, $tipoEquipamento, $tipoChamado, $status, $descricao, $tecnicoResponsavel, $resolution, $servicesPerformed, $userSignatureName);

        if ($stmt->execute()) {
            echo json_encode(["success" => true, "message" => "Ordem de Serviço adicionada com sucesso!", "id" => $conn->insert_id, "osNumber" => $osNumber]);
        } else {
            echo json_encode(["success" => false, "message" => "Erro ao adicionar Ordem de Serviço: " . $stmt->error]);
        }
        $stmt->close();
        break;

    case 'PUT':
        $input = json_decode(file_get_contents('php://input'), true);
        $id = $input['id'] ?? ''; // O ID da OS a ser atualizada

        $setorUsuario = $input['setorUsuario'] ?? '';
        $nomeUsuario = $input['nomeUsuario'] ?? '';
        $tipoEquipamento = $input['tipoEquipamento'] ?? '';
        $tipoChamado = $input['tipoChamado'] ?? '';
        $status = $input['status'] ?? '';
        $descricao = $input['descricao'] ?? '';
        $tecnicoResponsavel = $input['tecnicoResponsavel'] ?? null;
        $resolution = $input['resolution'] ?? null;
        $servicesPerformed = $input['servicesPerformed'] ?? null;
        $userSignatureName = $input['userSignatureName'] ?? null;
        $partsUsed = $input['partsUsed'] ?? []; // Array de objetos {name, quantity}

        $conn->begin_transaction(); // Inicia uma transação para garantir a integridade

        try {
            // Atualiza a tabela service_orders
            $stmt = $conn->prepare("UPDATE service_orders SET user_sector = ?, user_name = ?, equipment_type = ?, call_type = ?, status = ?, description = ?, responsible_technician = ?, resolution = ?, services_performed = ?, user_signature_name = ? WHERE id = ?");
            $stmt->bind_param("ssssssssssi", $setorUsuario, $nomeUsuario, $tipoEquipamento, $tipoChamado, $status, $descricao, $tecnicoResponsavel, $resolution, $servicesPerformed, $userSignatureName, $id);
            $stmt->execute();

            // Lógica para Peças Utilizadas (os_parts_used)
            // Primeiro, remove todas as peças antigas para esta OS
            $stmt_delete_parts = $conn->prepare("DELETE FROM os_parts_used WHERE service_order_id = ?");
            $stmt_delete_parts->bind_param("i", $id);
            $stmt_delete_parts->execute();
            $stmt_delete_parts->close();

            // Em seguida, insere as novas peças
            foreach ($partsUsed as $part) {
                $partName = $part['name'];
                $quantityUsed = $part['quantity'];

                // Diminui a quantidade no estoque (simulado)
                $stmt_update_stock = $conn->prepare("UPDATE stock_items SET quantity = quantity - ? WHERE name = ? AND quantity >= ?");
                $stmt_update_stock->bind_param("isi", $quantityUsed, $partName, $quantityUsed);
                $stmt_update_stock->execute();

                if ($stmt_update_stock->affected_rows === 0) {
                    throw new Exception("Estoque insuficiente ou peça não encontrada para: " . $partName);
                }
                $stmt_update_stock->close();

                // Insere a peça utilizada na tabela de peças da OS
                $stmt_insert_part = $conn->prepare("INSERT INTO os_parts_used (service_order_id, part_name, quantity_used) VALUES (?, ?, ?)");
                $stmt_insert_part->bind_param("isi", $id, $partName, $quantityUsed);
                $stmt_insert_part->execute();
                $stmt_insert_part->close();
            }

            $conn->commit(); // Confirma a transação
            echo json_encode(["success" => true, "message" => "Ordem de Serviço atualizada com sucesso!"]);

        } catch (Exception $e) {
            $conn->rollback(); // Reverte a transação em caso de erro
            echo json_encode(["success" => false, "message" => "Erro ao atualizar Ordem de Serviço: " . $e->getMessage()]);
        }
        $stmt->close();
        break;

    case 'DELETE':
        $input = json_decode(file_get_contents('php://input'), true);
        $id = $input['id'] ?? '';

        // Em um sistema real, você também precisaria reverter o estoque das peças utilizadas
        // se a OS for excluída, ou ter uma lógica de "cancelamento" de OS.
        $stmt = $conn->prepare("DELETE FROM service_orders WHERE id = ?");
        $stmt->bind_param("i", $id);

        if ($stmt->execute()) {
            echo json_encode(["success" => true, "message" => "Ordem de Serviço excluída com sucesso!"]);
        } else {
            echo json_encode(["success" => false, "message" => "Erro ao excluir Ordem de Serviço: " . $stmt->error]);
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
