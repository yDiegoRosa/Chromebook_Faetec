<?php
require_once __DIR__ . '/auth.php';

// O professor só pode trocar a própria senha: o id vem da sessão, nunca do formulário.
$id_professor = (int) $_SESSION['id'];
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verificar();
    $nova_senha = (string) ($_POST['nova_senha'] ?? '');
    $confirmar_senha = (string) ($_POST['confirmar_senha'] ?? '');

    if ($nova_senha !== $confirmar_senha) {
        $error = 'As senhas não coincidem!';
    } elseif (mb_strlen($nova_senha) < SENHA_MIN) {
        $error = 'A nova senha precisa ter pelo menos ' . SENHA_MIN . ' caracteres.';
    } elseif ($nova_senha === SENHA_PADRAO) {
        $error = 'A nova senha não pode ser a senha padrão. Escolha outra senha.';
    } else {
        $hash = password_hash($nova_senha, PASSWORD_DEFAULT);
        $stmt = db()->prepare('UPDATE professor SET senha = ?, deve_trocar_senha = 0 WHERE id = ?');
        $stmt->bind_param('si', $hash, $id_professor);
        $stmt->execute();
        $stmt->close();

        $_SESSION['trocar_senha'] = false;
        session_regenerate_id(true);
        header('Location: AgendaSemanal.php');
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">

<head>
    <meta charset="UTF-8">
    <title>Mudar Senha</title>
    <link rel="stylesheet" href="css/StyleLogin.css">
    <style>
        body,
        html {
            margin: 0;
            padding: 0;
            height: 100%;
            background-color: #1B98E0;
            font-family: 'Roboto', sans-serif;
            text-align: center;
        }

        .posi-caixa {
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            /* Garante que o formulário ocupe o centro verticalmente */
            padding: 20px;
            /* Evita que o conteúdo encoste nas bordas */
        }

        /* 
        .caixa_alt_senha {
            border-radius: 8px;
            background-color: #fff;
            width: 100%;
            max-width: 450px;
            box-shadow: rgba(164, 232, 255, 0.4) 5px 5px;
            padding: 20px;
            max-height: auto;
        }

        @media screen and (max-width: 768px) {
            .caixa_alt_senha {
                width: 90%; 
                max-width: none; 
                padding: 30px; 
            }
        }

        @media screen and (max-width: 480px) {
            .caixa_alt_senha {
                width: 100%;
                padding: 20px;
            }
        }
 */

        /* form {
            max-width: 400px;
            margin: auto;
            text-align: center;
            margin: 15px;
        }

        label,
        input {
            display: block;
            width: 100%;
            margin-bottom: 10px;
        } */

        /* input[type="submit"] {
            padding: 10px;
            background-color: #1B98E0;
            color: white;
            border: none;
            cursor: pointer;
        } */

        #error-message {
            color: white;
            background-color: red;
            padding: 10px;
            border-radius: 5px;
            position: fixed;
            top: 20px;
            /* Distância do topo */
            left: 50%;
            transform: translateX(-50%);
            z-index: 1000;
            /* Para aparecer sobre outros elementos */
            display: none;
            /* Inicialmente escondido */
            transition: opacity 0.5s ease;
            /* Transição suave */
            opacity: 0;
            /* Começa invisível */
        }

        #error-message.show {
            display: block;
            /* Exibe quando necessário */
            opacity: 1;
            /* Torna visível */
        }

        .caixa_alt_senha {
            border-radius: 8px;
            background-color: #fff;
            width: 80%;
            max-width: 500px;
            height: auto;
            max-height: 90vh;
            box-shadow: rgba(164, 232, 255, 0.4) 5px 5px;
            padding: 40px;
            overflow-y: auto;
        }

        @media screen and (max-width: 768px) {
            .caixa_alt_senha {
                width: 85%;
                /* Aumenta a largura em dispositivos menores */
                max-width: 500px;
                /* Ajusta o limite máximo de largura */
            }
        }

        @media screen and (max-width: 480px) {
            .caixa_alt_senha {
                width: 95%;
                /* Aumenta ainda mais a largura para dispositivos muito pequenos */
                max-width: 400px;
                /* Limita o tamanho máximo para telas pequenas */
            }
        }

        form label {
            font-size: 20px;
            /* Aumenta o tamanho dos textos de label */
            margin-bottom: 10px;
        }

        form input[type="password"],
        form input[type="submit"] {
            font-size: 18px;
            /* Aumenta o tamanho da fonte dos inputs */
            height: 50px;
            /* Aumenta a altura dos campos de texto */
            margin-bottom: 15px;
        }

        input[type="submit"] {
            padding: 15px;
            /* Aumenta o tamanho do botão */
            font-size: 20px;
            /* Aumenta o tamanho da fonte do botão */
            background-color: #1B98E0;
            color: white;
            border: none;
            cursor: pointer;
        }

        input[type="submit"]:hover {
            background-color: #13293D;
            /* Cor de fundo ao passar o mouse */
        }
    </style>
    <script>
        window.onload = function() {
            const errorMessage = <?php echo json_encode($error, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>; // Captura a mensagem de erro do PHP
            const erroDiv = document.querySelector('#error-message');

            if (errorMessage) {
                erroDiv.textContent = errorMessage; // Define a mensagem de erro
                erroDiv.classList.add('show'); // Adiciona classe para exibir
                setTimeout(() => {
                    erroDiv.classList.remove('show'); // Remove a classe após 3 segundos
                }, 3000); // Tempo que o pop-up fica visível
            }
        };
    </script>
</head>

<body>
    <div class="posi-caixa">
        <section class="caixa_alt_senha">
            <h1 class="login5">Alterar Senha</h1>
            <div class="login-card-cont">
                <form method="post" action="MudarSenha.php">
                    <?php echo csrf_campo(); ?>
                    <label for="nova_senha">Nova Senha:</label>
                    <input type="password" id="nova_senha" name="nova_senha" minlength="<?php echo SENHA_MIN; ?>" required>

                    <label for="confirmar_senha">Confirmar Senha:</label>
                    <input type="password" id="confirmar_senha" name="confirmar_senha" required>
                    <br>
                    <input type="submit" value="Alterar Senha">
                </form>
            </div>
        </section>
    </div>
    <div id="error-message"></div> <!-- Div para a mensagem de erro -->
</body>

</html>