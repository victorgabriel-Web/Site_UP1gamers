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


/*  ============================ATUALIZAÇÃO=========================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['acao'] ?? '') === 'atualizar') {
  try {
    $id        = (int)($_POST['id'] ?? 0);
    $descricao = trim($_POST['descricao'] ?? '');
    $dataVal   = trim($_POST['data_validade'] ?? '');
    $link      = trim($_POST['link'] ?? '');
    $categoria = $_POST['categorialistar'] ?? null;
    $categoria = ($categoria === '' || $categoria === null) ? null : (int)$categoria;

    if ($id <= 0) {
      redirect_with('../PAGINAS_LOGISTA/banners_logista.html', ['erro_banner' => 'ID inválido para edição.']);
    }
    // Lê (se houver) nova imagem
    $imgBlob = read_image_to_blob($_FILES['foto'] ?? null);
    // validações mínimas (iguais ao cadastro)
    $erros = [];
    if ($descricao === '') { $erros[] = 'Informe a descrição.'; }
    elseif (mb_strlen($descricao) > 45) { $erros[] = 'Descrição deve ter no máximo 45 caracteres.'; }
    $dt = DateTime::createFromFormat('Y-m-d', $dataVal);
    if (!($dt && $dt->format('Y-m-d') === $dataVal)) { $erros[] = 'Data de validade inválida (use YYYY-MM-DD).'; }
    if ($link !== '' && mb_strlen($link) > 45) { $erros[] = 'Link deve ter no máximo 45 caracteres.'; }
    if ($erros) {
      redirect_with('../PAGINAS_LOGISTA/banners_logista.html', ['erro_banner' => implode(' ', $erros)]);
    }

    // Monta UPDATE dinâmico (atualiza imagem só se uma nova foi enviada)
    $setSql = "descricao = :desc, data_validade = :dt, link = :lnk, CategoriasProdutos_id = :cat";
    if ($imgBlob !== null) {
      $setSql = "imagem = :img, " . $setSql;
    }

    $sql = "UPDATE Banners SET $setSql WHERE idBanners = :id";

    $st = $pdo->prepare($sql);
    if ($imgBlob !== null) {
      $st->bindValue(':img', $imgBlob, PDO::PARAM_LOB);
    }
    if ($categoria === null) {
      $st->bindValue(':idCategoria', null, PDO::PARAM_NULL);
    } else {
      $st->bindValue(':idCategoria', $categoria, PDO::PARAM_INT);
    }
    $st->bindValue(':id', $id, PDO::PARAM_INT);
    $st->execute();
    redirect_with('../PAGINAS_LOGISTA/promocoes_logista.html', ['editar_banner' => 'ok']);
  } catch (Throwable $e) {
    redirect_with('../PAGINAS_LOGISTA/promocoes_logista.html', ['erro_banner' => 'Erro ao editar: ' . $e->getMessage()]);
  }
}

/*  ============================EXCLUSÃO=========================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['acao'] ?? '') === 'excluir') {
  try {
    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) {
      redirect_with('../PAGINAS_LOGISTA/promocoes_logista.html', ['erro_banner' => 'ID inválido para exclusão.']);
    }

    $st = $pdo->prepare("DELETE FROM Banners WHERE idBanners = :id");
    $st->bindValue(':id', $id, PDO::PARAM_INT);
    $st->execute();

    redirect_with('../PAGINAS_LOGISTA/promocoes_logista.html', ['excluir_banner' => 'ok']);

  } catch (Throwable $e) {
    redirect_with('../PAGINAS_LOGISTA/promocoes_logista.html', ['erro_banner' => 'Erro ao excluir: ' . $e->getMessage()]);
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

