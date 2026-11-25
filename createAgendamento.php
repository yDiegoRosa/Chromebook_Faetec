<?php
require_once __DIR__ . '/auth.php';
header('Content-Type: text/plain; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Método não permitido.');
}
csrf_verificar();

function falhar(string $mensagem): void
{
    http_response_code(400);
    exit($mensagem);
}

$id_professor = (int) $_SESSION['id'];
$data = (string) ($_POST['data'] ?? '');
$horarios = array_values(array_unique(array_filter(explode(',', (string) ($_POST['horarios'] ?? '')))));
$preferencia = (int) ($_POST['preferencia'] ?? 0);

// ---- Validação ----
$dataSelecionada = DateTime::createFromFormat('!d-m-Y', $data);
if (!$dataSelecionada || $dataSelecionada->format('d-m-Y') !== $data) {
    falhar('Selecione uma data no calendário.');
}
if (empty($horarios)) {
    falhar('Selecione pelo menos um horário.');
}
if ($preferencia < 1 || $preferencia > count(CAIXAS)) {
    falhar('Quantidade de caixas inválida.');
}
if ((int) $dataSelecionada->format('N') >= 6) {
    falhar('Não há aulas aos sábados e domingos. Escolha um dia de segunda a sexta.');
}

$agora = new DateTime();
if ($dataSelecionada < (clone $agora)->setTime(0, 0)) {
    falhar('A data selecionada já passou. Escolha uma data válida.');
}

foreach ($horarios as $hora) {
    if (!horario_valido($hora)) {
        falhar("Horário inválido: $hora");
    }
    $inicio = DateTime::createFromFormat('d-m-Y H:i', "$data $hora");
    if ($inicio < $agora) {
        falhar("O horário $hora já passou. Escolha um horário válido.");
    }
}

// ---- Reserva ----
// O índice único (data, horario, idCor) no banco garante que duas pessoas
// reservando ao mesmo tempo não fiquem com a mesma caixa.
$conn = db();
$insert = $conn->prepare('INSERT INTO agendamento (id_professor, data, horario, idCor) VALUES (?, ?, ?, ?)');
$mensagens = [];
$total = 0;

foreach ($horarios as $hora) {
    $reservadas = [];
    foreach (CAIXAS as $cor => $nomeCor) {
        if (count($reservadas) >= $preferencia) {
            break;
        }
        try {
            $insert->bind_param('issi', $id_professor, $data, $hora, $cor);
            $insert->execute();
            $reservadas[] = $nomeCor;
        } catch (mysqli_sql_exception $e) {
            if ($e->getCode() !== 1062) { // 1062 = caixa já reservada nesse horário
                throw $e;
            }
        }
    }
    $total += count($reservadas);

    if (empty($reservadas)) {
        $mensagens[] = "$hora: nenhuma caixa livre.";
    } elseif (count($reservadas) < $preferencia) {
        $mensagens[] = "$hora: só havia " . count($reservadas) . " caixa(s) livre(s). Reservada(s): " . implode(', ', $reservadas) . '.';
    } else {
        $mensagens[] = "$hora: caixa(s) " . implode(', ', $reservadas) . '.';
    }
}
$insert->close();

if ($total === 0) {
    http_response_code(409);
    echo "Não foi possível fazer nenhum agendamento. Todos os horários escolhidos estão ocupados.\n\n";
} else {
    echo "$total agendamento(s) realizado(s) para $data.\n\n";
}
echo implode("\n", $mensagens);
