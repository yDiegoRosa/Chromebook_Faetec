<?php
// Configuração central do sistema: banco de dados, sessão, CSRF e horários.
// As credenciais do banco vêm de variáveis de ambiente (usadas no Docker).
// Sem elas, valem os padrões do XAMPP (root sem senha), só para desenvolvimento local.

date_default_timezone_set('America/Sao_Paulo');

// Nunca mostrar erros do PHP para o usuário final (eles vão para o log do servidor).
ini_set('display_errors', getenv('APP_DEBUG') === '1' ? '1' : '0');
error_reporting(E_ALL);

define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') !== false ? getenv('DB_PASS') : '');
define('DB_NAME', getenv('DB_NAME') ?: 'chromebook');

// Senha temporária de primeiro acesso. Não pode ser reutilizada como senha definitiva.
define('SENHA_PADRAO', 'faeterj123');
define('SENHA_MIN', 8);

// Quantidade de caixas de Chromebooks (cada caixa tem uma cor na agenda).
define('CAIXAS', [1 => 'laranja', 2 => 'verde', 3 => 'amarelo', 4 => 'azul', 5 => 'vermelho']);

// Horários de início das aulas. Precisam ser iguais aos do main.js.
define('HORARIOS_MANHA', ['7:10', '8:00', '8:50', '9:50', '10:40', '11:30']);
define('HORARIOS_NOITE', ['18:00', '18:40', '19:20', '20:00', '20:50', '21:30']);

function db(): mysqli
{
    static $conn = null;
    if ($conn === null) {
        mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
        try {
            $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
            $conn->set_charset('utf8mb4');
        } catch (mysqli_sql_exception $e) {
            error_log('Falha na conexão com o banco: ' . $e->getMessage());
            http_response_code(500);
            exit('Não foi possível conectar ao banco de dados. Tente novamente mais tarde.');
        }
    }
    return $conn;
}

function iniciar_sessao(): void
{
    if (session_status() === PHP_SESSION_NONE) {
        session_set_cookie_params([
            'httponly' => true,
            'samesite' => 'Lax',
            'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        ]);
        session_start();
    }
}

// Escapa texto para HTML.
function e($valor): string
{
    return htmlspecialchars((string) $valor, ENT_QUOTES, 'UTF-8');
}

function csrf_token(): string
{
    iniciar_sessao();
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_campo(): string
{
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}

// Interrompe a requisição se o token CSRF enviado não for o da sessão.
function csrf_verificar(): void
{
    iniciar_sessao();
    $enviado = $_POST['csrf'] ?? '';
    if (!is_string($enviado) || empty($_SESSION['csrf']) || !hash_equals($_SESSION['csrf'], $enviado)) {
        http_response_code(403);
        exit('Sessão expirada ou requisição inválida. Recarregue a página e tente novamente.');
    }
}

function horario_valido(string $hora): bool
{
    return in_array($hora, HORARIOS_MANHA, true) || in_array($hora, HORARIOS_NOITE, true);
}
