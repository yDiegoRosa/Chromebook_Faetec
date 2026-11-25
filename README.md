# Agendamento de Chromebooks – FAETEC

Sistema web para os professores reservarem as caixas de Chromebooks por horário de aula.

## Como rodar

### Opção 1: Docker (recomendado, um comando)

1. Instale o [Docker Desktop](https://www.docker.com/products/docker-desktop/).
2. Na pasta do projeto, rode:
   ```bash
   docker compose up
   ```
3. Abra **http://localhost:8080**.

O banco é criado automaticamente a partir do `chromebook.sql`. Para apagar tudo e recomeçar do zero: `docker compose down -v`.

### Opção 2: XAMPP

1. Copie a pasta do projeto para `C:\xampp\htdocs\`.
2. No phpMyAdmin, importe `chromebook.sql` (instalação nova) **ou** rode `migracao_001_seguranca.sql` (se você já tinha o banco da versão antiga).
3. Abra `http://localhost/<nome-da-pasta>/`.

Sem variáveis de ambiente, o sistema usa o padrão do XAMPP (`root` sem senha). Em produção, defina `DB_HOST`, `DB_USER`, `DB_PASS` e `DB_NAME`, e use um usuário do MySQL com senha.

## Usuários de teste

| E-mail | Senha temporária |
| --- | --- |
| Adm@gmail.com | faeterj123 |
| Maria@gmail.com | faeterj123 |

No primeiro login o sistema obriga a criar uma senha nova (mínimo de 8 caracteres).

## Estrutura

| Arquivo | Função |
| --- | --- |
| `config.php` | Conexão com o banco, sessão, proteção CSRF e lista de horários |
| `auth.php` | Bloqueia páginas para quem não está logado |
| `Login.html` / `Login.php` | Tela e lógica de login |
| `MudarSenha.php` | Troca de senha do professor logado |
| `AgendaSemanal.php` | Agenda da semana com todas as reservas |
| `Agendamento.php` + `main.js` | Tela de nova reserva |
| `createAgendamento.php` | Grava a reserva |
| `cancelarAgendamento.php` | Cancela uma reserva própria |
