# Kart Center — Sistema de agendamento de corridas

Aplicação acadêmica em PHP 8 + MySQL, preparada para execução local com XAMPP.

## Requisitos
- XAMPP com Apache e MySQL/MariaDB ativos
- PHP 8.0 ou superior (extensões PDO e pdo_mysql)
- Navegador atualizado

## Instalação
1. Extraia a pasta `kart-center` dentro de `C:\xampp\htdocs\`.
2. Abra o XAMPP Control Panel e inicie **Apache** e **MySQL**.
3. Acesse `http://localhost/phpmyadmin`.
4. Importe o arquivo `database.sql` (ele cria o banco `kart_center`, as tabelas, a conta administrativa e baterias futuras de exemplo).
5. Confira as credenciais em `config.php`. O padrão do XAMPP é host `127.0.0.1`, usuário `root` e senha vazia.
6. Acesse `http://localhost/kart-center/`.

## Conta administrativa de demonstração
- E-mail: `admin@kartcenter.local`
- Senha: `KartAdmin#2026`

Altere a senha após a primeira utilização em **Perfil**. O cadastro público sempre cria contas comuns; somente o administrador pode alterar o tipo de uma conta pelo painel.

## Funcionalidades implementadas
- Home pública e home autenticada com navegação conforme o perfil.
- Cadastro com validação, e-mail único e hash de senha (`password_hash`).
- Login, sessão protegida e encerramento de sessão.
- Perfil, edição de nome/e-mail e alteração de senha com conferência da senha atual.
- Listagem de baterias futuras e reserva com cálculo automático do total.
- Verificação de vagas no servidor e transação com bloqueio `SELECT ... FOR UPDATE` para evitar excesso de reservas concorrentes.
- Página com as cinco reservas mais recentes do usuário autenticado.
- Solicitação de redefinição registrada com token aleatório armazenado como hash e validade de 30 minutos.
- Painel administrativo, edição e exclusão de usuários, impedindo autoexclusão.
- Proteção de páginas administrativas no servidor, consultas preparadas com PDO e token CSRF nos formulários POST.

## Observação importante sobre redefinição de senha
A estrutura segura para registrar a solicitação e armazenar o hash do token está pronta. O envio do link por e-mail e o endpoint de consumo/validação do token não foram ativados porque exigem configurar um serviço SMTP e uma URL pública/local para o fluxo. Por isso, o sistema **não finge alterar a senha**: a página informa que a solicitação foi registrada, sem realizar troca de senha.

## Testes manuais sugeridos
1. Criar usuário com dados válidos; tentar repetir o mesmo e-mail.
2. Tentar cadastrar com nome vazio, e-mail inválido, senha curta e confirmação diferente.
3. Entrar com credenciais corretas e incorretas.
4. Abrir `admin/` como usuário comum e confirmar o bloqueio.
5. Alterar nome/e-mail no perfil; testar e-mail duplicado.
6. Alterar senha com senha atual errada e com confirmação divergente.
7. Reservar uma bateria com uma ou mais vagas; verificar o valor total e a redução de vagas.
8. Tentar reservar mais vagas do que as disponíveis.
9. Verificar que cada usuário vê somente as próprias reservas.
10. Como administrador, editar uma conta, alterar seu tipo e excluir outro usuário.
11. Tentar excluir a própria conta administrativa; a operação deve ser impedida.
12. Registrar solicitação de redefinição com e-mail existente e inexistente.

## Estrutura
- `index.php`: página inicial
- `cadastro.php`, `login.php`, `logout.php`: autenticação
- `perfil.php`: dados e senha
- `agendar.php`, `minhas-reservas.php`: reservas
- `esqueci-senha.php`: registro de solicitação de redefinição
- `admin/`: gerenciamento de usuários
- `config.php`: conexão PDO, sessão, autorização, CSRF e helpers
- `database.sql`: criação do banco e dados de demonstração
- `assets/`: CSS e JavaScript

## Observações técnicas
- O projeto usa fonte do Google Fonts e uma imagem de fundo hospedada no Unsplash na página inicial; sem internet, a aplicação continua funcional, mas esses recursos visuais externos podem não carregar.
- Para um ambiente de produção, configure HTTPS, SMTP, política de senha mais forte, limitação de tentativas de login, logs de auditoria e fluxo completo de redefinição de senha.
