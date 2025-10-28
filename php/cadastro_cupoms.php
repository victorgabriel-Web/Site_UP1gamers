<?php
require_once __DIR__ . "/conexao.php";

/* ============================ FUNÇÕES AUXILIARES ============================ */

// Redirecionamento com parâmetros
function redirecWith($url, $params = [])
{
    if (!empty($params)) {
        $qs = http_build_query($params);
        $sep = (strpos($url, '?') === false) ? '?' : '&';
        $url .= $sep . $qs;
    }
    header("Location: $url");
    exit;
}

// Lê a imagem enviada e transforma em blob
function readImageToBlob($file)
{
    if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
        return null;
    }
    return file_get_contents($file['tmp_name']);
}

/* ============================ LISTAGEM ============================ */
if ($_SERVER["REQUEST_METHOD"] === "GET" && isset($_GET["listar"])) {
    try {
        $sql = "SELECT idCategoriaProduto AS id, nome FROM categorias_produtos ORDER BY nome";
        $stmt = $pdo->query($sql);
        $categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $formato = isset($_GET["format"]) ? strtolower($_GET["format"]) : "option";

        if ($formato === "json") {
            header("Content-Type: application/json; charset=utf-8");
            echo json_encode(["ok" => true, "categorias" => $categorias], JSON_UNESCAPED_UNICODE);
            exit;
        }

        header("Content-Type: text/html; charset=utf-8");
        foreach ($categorias as $cat) {
            $id = (int)$cat["id"];
            $nome = htmlspecialchars($cat["nome"], ENT_QUOTES, "UTF-8");
            echo "<option value=\"$id\">$nome</option>\n";
        }
        exit;
    } catch (Throwable $e) {
        header('Content-Type: text/html; charset=utf-8', true, 500);
        echo "<option disabled>Erro ao carregar categorias</option>";
        exit;
    }
}

/* ============================ ATUALIZAÇÃO ============================ */
if ($_SERVER["REQUEST_METHOD"] === "POST" && ($_POST["acao"] ?? "") === "atualizar") {
    try {
        // Captura dos dados do formulário
        $id = (int)($_POST["id"] ?? 0);
        $nome = trim($_POST["nome_cupom"] ?? "");
        $valor = trim($_POST["Valor"] ?? "");
        $validade = trim($_POST["Validade"] ?? "");
        $quantidade = trim($_POST["Quantidade"] ?? "");

        // Validações básicas
        $erros = [];

        if ($id <= 0) $erros[] = "ID inválido para edição.";
        if ($nome === "") $erros[] = "Informe o nome do cupom.";
        if (!is_numeric($valor) || $valor <= 0) $erros[] = "Informe um valor válido.";
        if (!is_numeric($quantidade) || $quantidade <= 0) $erros[] = "Informe uma quantidade válida.";

        $dt = DateTime::createFromFormat("Y-m-d", $validade);
        if (!($dt && $dt->format("Y-m-d") === $validade)) $erros[] = "Data de validade inválida.";

        // Caso tenha erros, redireciona com mensagem
        if ($erros) {
            redirecWith("../paginas_logista/promocoes_logista.html", ["erro" => implode(" ", $erros)]);
        }

        // Monta e executa a atualização
        $sql = "UPDATE Cupom 
                SET nome = :nome, 
                    valor = :valor, 
                    data_validade = :validade, 
                    quantidade = :quantidade
                WHERE idCupom = :id";

        $st = $pdo->prepare($sql);
        $st->bindValue(":nome", $nome);
        $st->bindValue(":valor", $valor);
        $st->bindValue(":validade", $validade);
        $st->bindValue(":quantidade", $quantidade, PDO::PARAM_INT);
        $st->bindValue(":id", $id, PDO::PARAM_INT);
        $st->execute();

        // Redireciona com sucesso
        redirecWith("../paginas_logista/promocoes_logista.html", ["editar_cupom" => "ok"]);
    } catch (Throwable $e) {
        redirecWith("../paginas_logista/promocoes_logista.html", ["erro" => "Erro ao editar: " . $e->getMessage()]);
    }
}


/* ============================ EXCLUSÃO ============================ */
if ($_SERVER["REQUEST_METHOD"] === "POST" && ($_POST["acao"] ?? "") === "excluir") {
    try {
        $id = (int)($_POST["id"] ?? 0);
        if ($id <= 0) {
            redirecWith("../paginas_logista/promocoes_logista.html", ["erro" => "ID inválido para exclusão."]);
        }

        $st = $pdo->prepare("DELETE FROM Banners WHERE idBanners = :id");
        $st->bindValue(":id", $id, PDO::PARAM_INT);
        $st->execute();

        redirecWith("../paginas_logista/promocoes_logista.html", ["excluir_banner" => "ok"]);
    } catch (Throwable $e) {
        redirecWith("../paginas_logista/promocoes_logista.html", ["erro" => "Erro ao excluir: " . $e->getMessage()]);
    }
}

/* ============================ CADASTRO ============================ */
/* ============================ CADASTRO DE CUPOM ============================ */
if ($_SERVER["REQUEST_METHOD"] === "POST" ) {
    try {
        // Captura dos dados do formulário
        $nome = trim($_POST["nome_cupom"] ?? "");
        $valor = trim($_POST["Valor"] ?? "");
        $validade = trim($_POST["Validade"] ?? "");
        $quantidade = trim($_POST["Quantidade"] ?? "");

        // Validações
        $erros = [];

        if ($nome === "") $erros[] = "Informe o nome do cupom.";
        if (!is_numeric($valor) || $valor <= 0) $erros[] = "Informe um valor válido.";
        if (!is_numeric($quantidade) || $quantidade <= 0) $erros[] = "Informe uma quantidade válida.";

        $dt = DateTime::createFromFormat("Y-m-d", $validade);
        if (!($dt && $dt->format("Y-m-d") === $validade)) $erros[] = "Data de validade inválida.";

        if ($erros) {
            redirecWith("../paginas_logista/promocoes_logista.html", ["erro" => implode(" ", $erros)]);
        }

        // Inserção no banco
        $sql = "INSERT INTO Cupom (nome, valor, data_validade, quantidade)
                VALUES (:nome, :valor, :validade, :quantidade)";
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(":nome", $nome);
        $stmt->bindValue(":valor", $valor);
        $stmt->bindValue(":validade", $validade);
        $stmt->bindValue(":quantidade", $quantidade, PDO::PARAM_INT);
        $stmt->execute();

        // Redireciona com sucesso
        redirecWith("../paginas_logista/promocoes_logista.html", ["cadastro_cupom" => "ok"]);
    } catch (Throwable $e) {
        redirecWith("../paginas_logista/promocoes_logista.html", ["erro" => "Erro ao cadastrar: " . $e->getMessage()]);
    }
}

?>