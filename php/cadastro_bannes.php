<?php
require_once __DIR__ . "/conexao.php";

// Função de redirecionamento
function redirecWith($url, $params = []) {
    if (!empty($params)) {
        $qs = http_build_query($params);
        $sep = (strpos($url, '?') === false) ? '?' : '&';
        $url .= $sep . $qs;
    }
    header("Location: $url");
    exit;
}

// Função para ler imagem como BLOB
function readImageToBlob(?array $file): ?string {
    if (!$file || !isset($file['tmp_name']) || $file['error'] !== UPLOAD_ERR_OK) return null;
    $bin = file_get_contents($file['tmp_name']);
    return $bin === false ? null : $bin;
}

function json_response($data, $status = 200) {
    header("Content-Type: application/json; charset=utf-8", true, $status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

// LISTAGEM GET - JSON
if ($_SERVER["REQUEST_METHOD"] === "GET" && isset($_GET["listar"])) {
    try {
        // Busca também o campo imagem (BLOB)
        $sqlListar = "SELECT idBanners AS id, descricao, data_validade, link, imagem 
                      FROM Banners ORDER BY idBanners DESC";
        $stmtListar = $pdo->query($sqlListar);
        $listar = $stmtListar->fetchAll(PDO::FETCH_ASSOC);

        // Converte imagem BLOB para base64 (data URL)
        foreach ($listar as &$b) {
            if (!empty($b["imagem"])) {
                $b["imagem"] = "data:image/jpeg;base64," . base64_encode($b["imagem"]);
            } else {
                $b["imagem"] = null;
            }
        }

        // Retorna JSON
        header("Content-Type: application/json; charset=utf-8");
        echo json_encode(["ok" => true, "banners" => $listar], JSON_UNESCAPED_UNICODE);
        exit;

    } catch (Throwable $e) {
        header("Content-Type: application/json; charset=utf-8", true, 500);
        echo json_encode([
            "ok" => false,
            "error" => "Erro ao listar banners",
            "detail" => $e->getMessage()
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
}


/* ====================== EDITAR (POST) ====================== */
if ($_SERVER["REQUEST_METHOD"] === "POST" && ($_POST["acao"] ?? "") === "editar") {
    try {
        $id        = (int)($_POST["id"] ?? 0);
        $descricao = trim($_POST["descricao"] ?? "");
        $dataVal   = trim($_POST["data_validade"] ?? "");
        $link      = trim($_POST["link"] ?? "");
        $categoria = isset($_POST["categoria"]) && is_numeric($_POST["categoria"])
            ? (int)$_POST["categoria"] : null;
        $imagem    = readImageToBlob($_FILES["imagemb"] ?? null);

        if ($id <= 0) json_response(["ok" => false, "error" => "ID inválido."]);
        if ($descricao === "" || $dataVal === "") {
            json_response(["ok" => false, "error" => "Campos obrigatórios não preenchidos."]);
        }

        $sql = "UPDATE Banners 
                SET descricao = :desc, data_validade = :dt, link = :lnk, CategoriasProdutos_id = :cat"
              . ($imagem ? ", imagem = :img" : "")
              . " WHERE idBanners = :id";
        $st = $pdo->prepare($sql);
        $st->bindValue(":desc", $descricao);
        $st->bindValue(":dt", $dataVal);
        $st->bindValue(":lnk", $link);
        $st->bindValue(":cat", $categoria ?: null, $categoria ? PDO::PARAM_INT : PDO::PARAM_NULL);
        if ($imagem) $st->bindValue(":img", $imagem, PDO::PARAM_LOB);
        $st->bindValue(":id", $id, PDO::PARAM_INT);
        $st->execute();

        json_response(["ok" => true, "msg" => "Banner atualizado com sucesso!"]);
    } catch (Throwable $e) {
        json_response(["ok" => false, "error" => "Erro ao editar: " . $e->getMessage()], 500);
    }
}

/* ====================== EXCLUIR (POST) ====================== */
if ($_SERVER["REQUEST_METHOD"] === "POST" && ($_POST["acao"] ?? "") === "excluir") {
    try {
        $id = (int)($_POST["id"] ?? 0);
        if ($id <= 0) json_response(["ok" => false, "error" => "ID inválido."]);

        $st = $pdo->prepare("DELETE FROM Banners WHERE idBanners = :id");
        $st->bindValue(":id", $id, PDO::PARAM_INT);
        $st->execute();

        json_response(["ok" => true, "msg" => "Banner excluído com sucesso!"]);
    } catch (Throwable $e) {
        json_response(["ok" => false, "error" => "Erro ao excluir: " . $e->getMessage()], 500);
    }
}



try {
    if ($_SERVER["REQUEST_METHOD"] !== "POST") {
        redirecWith("../paginas_logista/promocoes_logista.html", ["erro" => "Método inválido"]);
    }

    // Captura os dados
    $imagem = readImageToBlob($_FILES["imagemb"] ?? null);
    $data_validade = $_POST["data_validade"] ?? null;
    $descricao = $_POST["descricao"] ?? null;
    $link = $_POST["link"] ?? null;

    // Categoria: só define se enviado e numérico, senão NULL
    $categoria = isset($_POST["categoria"]) && is_numeric($_POST["categoria"]) ? (int)$_POST["categoria"] : null;

    // Validação obrigatória
    $erros_validacao = [];
    if (empty($descricao)) $erros_validacao[] = "Descrição é obrigatória.";
    if (empty($data_validade)) $erros_validacao[] = "Data de validade é obrigatória.";
    

    if (!empty($erros_validacao)) {
        redirecWith("../paginas_logista/promocoes_logista.html", ["erro" => implode(", ", $erros_validacao)]);
    }

    // Inserir no banco
    $sql = "INSERT INTO Banners (imagem, data_validade, descricao, link, CategoriasProdutos_id)
            VALUES (:imagem, :data_validade, :descricao, :link, :CategoriasProdutos_id)";
    
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(":imagem", $imagem, PDO::PARAM_LOB);
    $stmt->bindParam(":data_validade", $data_validade);
    $stmt->bindParam(":descricao", $descricao);
    $stmt->bindParam(":link", $link);
    $stmt->bindParam(":CategoriasProdutos_id", $categoria, $categoria === null ? PDO::PARAM_NULL : PDO::PARAM_INT);

    if ($stmt->execute()) {
        redirecWith("../paginas_logista/promocoes_logista.html", ["cadastro" => "ok"]);
    } else {
        redirecWith("../paginas_logista/promocoes_logista.html", ["erro" => "Erro ao cadastrar no banco de dados"]);
    }

} catch (Throwable $e) {
    redirecWith("../paginas_logista/promocoes_logista.html", [
        "erro" => "Erro no banco de dados: " . $e->getMessage()
    ]);
}

