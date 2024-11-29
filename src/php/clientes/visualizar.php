<?php
session_start();

$host = "localhost";
$dbname = "santos_dinelli";
$user = "root";
$pass = "";

try {
    $conn = new PDO("mysql:host=$host;dbname=$dbname", $user, $pass);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    echo "Erro na conexão: " . $e->getMessage();
    exit;
}

// Função para buscar eventos do cliente
function getClientEvents($conn, $id_cliente) {
    try {
        $query = "SELECT * FROM events WHERE id_cliente = :id_cliente ORDER BY start ASC";
        $stmt = $conn->prepare($query);
        $stmt->bindValue(':id_cliente', $id_cliente, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return [];
    }
}

// Função para pesquisar clientes
function searchClientes($conn, $searchTerm) {
    $query = "SELECT * FROM clientes WHERE 
              nome_cliente LIKE :searchTerm 
              OR razao_social LIKE :searchTerm
              OR cpf_cliente LIKE :searchTerm
              OR cnpj LIKE :searchTerm";
    
    $stmt = $conn->prepare($query);
    $stmt->bindValue(':searchTerm', '%' . $searchTerm . '%');
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Função para excluir cliente
function deleteCliente($conn, $id) {
    try {
        // Primeiro exclui os eventos relacionados
        $queryEvents = "DELETE FROM events WHERE id_cliente = :id";
        $stmtEvents = $conn->prepare($queryEvents);
        $stmtEvents->bindValue(':id', $id, PDO::PARAM_INT);
        $stmtEvents->execute();

        // Depois exclui o cliente
        $queryCliente = "DELETE FROM clientes WHERE id = :id";
        $stmtCliente = $conn->prepare($queryCliente);
        $stmtCliente->bindValue(':id', $id, PDO::PARAM_INT);
        $stmtCliente->execute();
        
        return true;
    } catch (PDOException $e) {
        return false;
    }
}

// Processar exclusão se solicitado
if (isset($_POST['action']) && $_POST['action'] == 'delete' && isset($_POST['id'])) {
    if (deleteCliente($conn, $_POST['id'])) {
        header("Location: " . $_SERVER['PHP_SELF'] . "?msg=Cliente excluído com sucesso");
        exit;
    } else {
        header("Location: " . $_SERVER['PHP_SELF'] . "?error=Erro ao excluir cliente");
        exit;
    }
}

// Inicializa o array de clientes e verifica se há uma consulta de pesquisa
$clientes = [];
$searchTerm = "";
if (isset($_GET['search'])) {
    $searchTerm = $_GET['search'];
    $clientes = searchClientes($conn, $searchTerm);
} else {
    $query = "SELECT * FROM clientes";
    $stmt = $conn->prepare($query);
    $stmt->execute();
    $clientes = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Filtra os clientes por tipo de pessoa
$clientes_filtrados = ['fisicos' => [], 'juridicos' => []];
foreach ($clientes as $cliente) {
    if (isset($cliente['tipo_pessoa'])) {
        if ($cliente['tipo_pessoa'] == 1) {
            $clientes_filtrados['fisicos'][] = $cliente;
        } elseif ($cliente['tipo_pessoa'] == 2) {
            $clientes_filtrados['juridicos'][] = $cliente;
        }
    }
}

?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="../../style/clientes/pesquisar.css">
    <link rel="stylesheet" href="../../style/clientes/visualizar.css">
    <link rel="stylesheet" href="../../style/layout-header.css">
    <link rel="shortcut icon" href="../../images/icons/logo.ico" type="image/x-icon">
    <title>Visualizar Clientes</title>
    <style>
        .acoes {
    margin-top: 10px;
    display: flex;
    gap: 10px;
}

.btn-editar, .btn-excluir {
    padding: 10px 15px;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    font-weight: bold;
    text-decoration: none;
    text-align: center;
    text-transform: uppercase;
    font-size: 0.9em;
    transition: all 0.3s ease;
    display: inline-block;
    box-shadow: 0 2px 5px rgba(0,0,0,0.2);
}

.btn-editar {
    background-color: #4CAF50;
    color: white;
}

.btn-editar:hover {
    background-color: #45a049;
    box-shadow: 0 4px 8px rgba(0,0,0,0.3);
}

.btn-excluir {
    background-color: #f44336;
    color: white;
}

.btn-excluir:hover {
    background-color: #d32f2f;
    box-shadow: 0 4px 8px rgba(0,0,0,0.3);
}
    </style>
</head>
<body>

<header class="header">
    <nav class="menu-lateral">
        <input type="checkbox" class="fake-tres-linhas">
        <div><img class="tres-linhas" src="../../images/menu-tres-linhas.png" alt="menu de três linhas"></div>

        <ul>
            <li><a class="link" href="../../pages/home.php">ÍNICIO</a></li>
            <li><a class="link" href="../../pages/agenda.php">AGENDA</a></li>
            <li><a class="link" href="../../pages/validar_codigo_financeiro.php">FINANCEIRO</a></li>
            <li><a class="link" href="../../pages/client.php">CLIENTES</a></li>
            <li><a class="link" href="https://WA.me/+5511947295062/?text=Olá, preciso de ajuda com o software." target="_blank">SUPORTE</a></li>
            <li><a class="link" href="../../../login/sair.php">SAIR</a></li>
        </ul>
    </nav>

    <nav>
        <ul class="menu-fixo">
            <li><a class="link" style="margin-left: 18px;" href="../../pages/agenda.php">AGENDA</a></li>
            <li><a class="link" href="../../pages/validar_codigo_financeiro.php">FINANCEIRO</a></li>
            <li><a class="link" href="../../pages/client.php">CLIENTES</a></li>
        </ul>
    </nav>

    <nav> <!-- finalizar com a logo da empresa na direita-->

                <a href="https://www.santosedinelli.com.br" target="_blank">
                <img class="logo" src="../../images/santos-dinelli.png"  alt="logo da empresa"></a>

    </nav> <!-- final da div da logo-->

</header> <!-- fim header fixo -->

<div class="container-search-form">
    <form class="search-form" method="get" action="<?php echo $_SERVER['PHP_SELF']; ?>">
        <label class="label-form" for="search">Pesquisar clientes:</label>
        <input type="text" id="search" name="search" placeholder="Nome, CPF, razão social, CNPJ" value="<?php echo htmlspecialchars($searchTerm); ?>">
        <button type="submit">Pesquisar</button>
    </form>
</div>

<?php
// Exibir mensagens de sucesso ou erro
if (isset($_GET['msg'])) {
    echo '<div class="mensagem sucesso">' . htmlspecialchars($_GET['msg']) . '</div>';
}
if (isset($_GET['error'])) {
    echo '<div class="mensagem erro">' . htmlspecialchars($_GET['error']) . '</div>';
}
?>

<h2>Clientes Físicos</h2>

<?php foreach ($clientes_filtrados['fisicos'] as $cliente): ?>
    <div class="cliente">
        <h3><?php echo htmlspecialchars($cliente['nome_cliente'] ?? ''); ?></h3>
        <p>Email: <?php echo htmlspecialchars($cliente['email_cliente'] ?? ''); ?></p>
        <p>Telefone: <?php echo htmlspecialchars($cliente['telefone'] ?? ''); ?></p>
        <p>CPF: <?php echo htmlspecialchars($cliente['cpf_cliente'] ?? ''); ?></p>
        <p>Endereço: <?php echo htmlspecialchars($cliente['endereco'] ?? ''); ?></p>
        
        <h4>Serviços Agendados</h4>
        <table>
            <thead>
                <tr>
                    <th>Título</th>
                    <th>Início</th>
                    <th>Fim</th>
                    <th>Serviço</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $events = getClientEvents($conn, $cliente['id']);
                foreach ($events as $event): 
                ?>
                    <tr>
                        <td><?php echo htmlspecialchars($event['title']); ?></td>
                        <td><?php echo htmlspecialchars($event['start']); ?></td>
                        <td><?php echo htmlspecialchars($event['end']); ?></td>
                        <td><?php echo htmlspecialchars($event['servico']); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <div class="acoes">
            <a href="../clientes/editar.php?id=<?php echo $cliente['id']; ?>" class="btn-editar">Editar</a>
            <form method="post" style="display: inline;" onsubmit="return confirm('Tem certeza que deseja excluir este cliente?');">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="<?php echo $cliente['id']; ?>">
                <button type="submit" class="btn-excluir">Excluir</button>
            </form>
        </div>
    </div>
<?php endforeach; ?>

<h2>Clientes Jurídicos</h2>
<?php foreach ($clientes_filtrados['juridicos'] as $cliente): ?>
    <div class="cliente">
        <h3><?php echo htmlspecialchars($cliente['razao_social'] ?? ''); ?></h3>
        <p>Email: <?php echo htmlspecialchars($cliente['email_cliente_pj'] ?? ''); ?></p>
        <p>Telefone: <?php echo htmlspecialchars($cliente['telefone_pj'] ?? ''); ?></p>
        <p>CNPJ: <?php echo htmlspecialchars($cliente['cnpj'] ?? ''); ?></p>
        <p>Endereço: <?php echo htmlspecialchars($cliente['endereco_pj'] ?? ''); ?></p>

        <h4>Serviços Agendados</h4>
        <table>
            <thead>
                <tr>
                    <th>Título</th>
                    <th>Início</th>
                    <th>Fim</th>
                    <th>Serviço</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $events = getClientEvents($conn, $cliente['id']);
                foreach ($events as $event): 
                ?>
                    <tr>
                        <td><?php echo htmlspecialchars($event['title']); ?></td>
                        <td><?php echo htmlspecialchars($event['start']); ?></td>
                        <td><?php echo htmlspecialchars($event['end']); ?></td>
                        <td><?php echo htmlspecialchars($event['servico']); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <div class="acoes">
            <a href="../clientes/editar.php?id=<?php echo $cliente['id']; ?>" class="btn-editar">Editar</a>
            <form method="post" style="display: inline;" onsubmit="return confirm('Tem certeza que deseja excluir este cliente?');">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="<?php echo $cliente['id']; ?>">
                <button type="submit" class="btn-excluir">Excluir</button>
            </form>
        </div>
    </div>
<?php endforeach; ?>

</body>
</html>