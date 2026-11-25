<?php
require_once __DIR__ . '/auth.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['id_agendamento'])) {
    http_response_code(400);
    exit('Erro: ID do agendamento não informado.');
}
csrf_verificar();

$id_agendamento = (int) $_POST['id_agendamento'];
$id_professor_logado = (int) $_SESSION['id'];

// Só apaga se o agendamento for do professor logado.
$stmt = db()->prepare('SELECT data FROM agendamento WHERE id = ? AND id_professor = ?');
$stmt->bind_param('ii', $id_agendamento, $id_professor_logado);
$stmt->execute();
$agendamento = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$agendamento) {
    http_response_code(403);
    exit('Erro: Agendamento não encontrado ou você não tem permissão para cancelá-lo.');
}

$stmt = db()->prepare('DELETE FROM agendamento WHERE id = ? AND id_professor = ?');
$stmt->bind_param('ii', $id_agendamento, $id_professor_logado);
$stmt->execute();
$stmt->close();

// Volta para a semana do agendamento cancelado.
$destino = 'AgendaSemanal.php?data=' . urlencode($agendamento['data']);
echo "<script>alert('Agendamento cancelado com sucesso.'); window.location.href = " . json_encode($destino) . ";</script>";
