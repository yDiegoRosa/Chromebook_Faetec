<?php
// Inclua no topo de toda página que exige login.
require_once __DIR__ . '/config.php';
iniciar_sessao();

if (!isset($_SESSION['id'])) {
    header('Location: Login.html');
    exit();
}

// Quem entrou com a senha temporária precisa trocá-la antes de usar o sistema.
if (!empty($_SESSION['trocar_senha']) && basename($_SERVER['SCRIPT_NAME']) !== 'MudarSenha.php') {
    header('Location: MudarSenha.php');
    exit();
}
