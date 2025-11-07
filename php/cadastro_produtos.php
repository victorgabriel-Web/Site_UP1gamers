<?php
// Conectando este arquivo ao banco de dados
require_once __DIR__ . "/conexao.php";

// Função para redirecionar com parâmetros
function redirecWith($url, $params = [])
{
    if (!empty($params)) {
        $qs  = http_build_query($params);
        $sep = (strpos($url, '?') === false) ? '?' : '&';
        $url .= $sep . $qs;
    }
    header("Location: $url");
    exit;
}

/* Lê arquivo de upload como blob (ou null) */
function readImageToBlob(?array $file): ?string
{
    if (!$file || !isset($file['tmp_name']) || $file['error'] !== UPLOAD_ERR_OK) return null;
    $content = file_get_contents($file['tmp_name']);
    return $content === false ? null : $content;
}





// ===================== LISTAR PRODUTOS POR CATEGORIA ===================== //
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['listar_por_categoria'])) {
  // aceita idCategoria, idcategoria ou categoria_id
  $catId = (int)($_GET['idCategoria'] ?? $_GET['idcategoria'] ?? $_GET['categoria_id'] ?? 0);
  if ($catId <= 0) {
    json_err('idCategoria inválido');
  }

  try {
    /*
      Estrutura esperada:
        - Tabela Produtos
        - Tabela categorias_produtos (idCategoriaProduto, nome)
        - Tabela Produtos_e_Categorias_produtos (Produtos_idProdutos, Categorias_produtos_id)
        - (opcional) Imagem_produtos e Produtos_has_Imagem_produtos
    */

    $sql = "SELECT
              p.idProdutos,
              p.nome,
              p.descricao,
              p.quantidade,
              p.preco,
              p.preco_promocional,
              m.nome AS marca,
              c.nome AS categoria,
              (
                SELECT i2.foto
                FROM Imagem_produtos i2
                JOIN Produtos_has_Imagem_produtos pi2 
                  ON pi2.Imagem_produtos_idImagem_produtos = i2.idImagem_produtos
                WHERE pi2.Produtos_idProdutos = p.idProdutos
                ORDER BY i2.idImagem_produtos ASC
                LIMIT 1
              ) AS imagem,
              (
                SELECT i2.texto_alternativo
                FROM Imagem_produtos i2
                JOIN Produtos_has_Imagem_produtos pi2 
                  ON pi2.Imagem_produtos_idImagem_produtos = i2.idImagem_produtos
                WHERE pi2.Produtos_idProdutos = p.idProdutos
                ORDER BY i2.idImagem_produtos ASC
                LIMIT 1
              ) AS texto_alternativo
            FROM Produtos p
            INNER JOIN Produtos_e_Categorias_produtos pc 
              ON pc.Produtos_idProdutos = p.idProdutos
            INNER JOIN categorias_produtos c 
              ON c.idCategoriaProduto = pc.Categorias_produtos_id
            LEFT JOIN Marcas m 
              ON m.idMarcas = p.Marcas_idMarcas
            WHERE pc.Categorias_produtos_id = :catId
            ORDER BY p.idProdutos DESC";

    $st = $pdo->prepare($sql);
    $st->bindValue(':catId', $catId, PDO::PARAM_INT);
    $st->execute();
    $rows = $st->fetchAll(PDO::FETCH_ASSOC);

    $produtos = array_map(function ($r) {
      return [
        'idProdutos'        => (int)$r['idProdutos'],
        'nome'              => $r['nome'],
        'descricao'         => $r['descricao'],
        'quantidade'        => (int)$r['quantidade'],
        'preco'             => (float)$r['preco'],
        'preco_promocional' => isset($r['preco_promocional']) ? (float)$r['preco_promocional'] : null,
        'marca'             => $r['marca'] ?? null,
        'categoria'         => $r['categoria'] ?? null,
        // IMPORTANTE: converte BLOB para base64
        'imagem'            => $r['imagem'] ? base64_encode($r['imagem']) : null,
        'texto_alternativo' => $r['texto_alternativo'] ?? null
      ];
    }, $rows);

    json_ok(['count' => count($produtos), 'produtos' => $produtos]);
  } catch (Throwable $e) {
    json_err('Falha ao listar produtos por categoria: ' . $e->getMessage(), 500);
  }
}

// fallback




/*  ============================ATUALIZAÇÃO=========================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['acao'] ?? '') === 'atualizar') {
  try {
    $id        = (int)($_POST['id'] ?? 0);
    $descricao = trim($_POST['descricao'] ?? '');
    $dataVal   = trim($_POST['data'] ?? '');
    $link      = trim($_POST['link'] ?? '');
    $categoria = $_POST['categoriab'] ?? null;
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

    $sql = "UPDATE Banners
              SET $setSql
            WHERE idBanners = :id";

    $st = $pdo->prepare($sql);

    if ($imgBlob !== null) {
      $st->bindValue(':img', $imgBlob, PDO::PARAM_LOB);
    }

    $st->bindValue(':desc', $descricao, PDO::PARAM_STR);
    $st->bindValue(':dt',   $dataVal,   PDO::PARAM_STR);

    if ($link === '') {
      $st->bindValue(':lnk', null, PDO::PARAM_NULL);
    } else {
      $st->bindValue(':lnk', $link, PDO::PARAM_STR);
    }

    if ($categoria === null) {
      $st->bindValue(':cat', null, PDO::PARAM_NULL);
    } else {
      $st->bindValue(':cat', $categoria, PDO::PARAM_INT);
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
      redirect_with('../PAGINAS_LOGISTA/banners_logista.html', ['erro_banner' => 'ID inválido para exclusão.']);
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
    // SE O MÉTODO DE ENVIO FOR DIFERENTE DE POST
    if ($_SERVER["REQUEST_METHOD"] !== "POST") {
        redirecWith("../paginas_logista/cadastro_produtos_logista.html", [
            "erro" => "Método inválido"
        ]);
    }

    // Criar as variáveis do produto
    $nome = trim($_POST["nomeproduto"] ?? '');
    $descricao = trim($_POST["descricao"] ?? '');
    $quantidade = (int)($_POST["quantidade"] ?? 0);
    $preco = (double)($_POST["preco"] ?? 0);
    $tamanho = trim($_POST["tamanho"] ?? '');
    $cor = trim($_POST["cor"] ?? '');
    $codigo = (int)($_POST["codigo"] ?? 0);
    $preco_promocional = (double)($_POST["precopromocional"] ?? 0);
    $marcas_idMarcas = 1; // ID fixo de marca (pode ser substituído depois)

    // Criar as variáveis das imagens
    $img1 = readImageToBlob($_FILES["imgproduto1"] ?? null);
    $img2 = readImageToBlob($_FILES["imgproduto2"] ?? null);
    $img3 = readImageToBlob($_FILES["imgproduto3"] ?? null);

    // VALIDANDO OS CAMPOS
    $erros_validacao = [];

    if (empty($nome) || empty($descricao) || $quantidade <= 0 || $preco <= 0 || $marcas_idMarcas <= 0) {
        $erros_validacao[] = "Preencha todos os campos obrigatórios.";
    }

    // Se houver erros, volta para a tela com mensagem
    if (!empty($erros_validacao)) {
        redirecWith("../paginas_logista/cadastro_produtos_logista.html", [
            "erro" => implode(" ", $erros_validacao)
        ]);
    }

    // Inicia transação
    $pdo->beginTransaction();

    // Inserir produto
    $sqlProdutos = "INSERT INTO Produtos 
        (nome, descricao, quantidade, preco, tamanho, cor, codigo, preco_promocional, Marcas_idMarcas)
        VALUES 
        (:nome, :descricao, :quantidade, :preco, :tamanho, :cor, :codigo, :preco_promocional, :marcas_idMarcas)";

    $stmt = $pdo->prepare($sqlProdutos);
    $inserirProduto = $stmt->execute([
        ":nome" => $nome,
        ":descricao" => $descricao,
        ":quantidade" => $quantidade,
        ":preco" => $preco,
        ":tamanho" => $tamanho,
        ":cor" => $cor,
        ":codigo" => $codigo,
        ":preco_promocional" => $preco_promocional,
        ":marcas_idMarcas" => $marcas_idMarcas,
    ]);

    if (!$inserirProduto) {
        $pdo->rollBack();
        redirecWith("../paginas_logista/cadastro_produtos_logista.html", [
            "erro" => "Falha ao cadastrar o produto."
        ]);
    }

    $idproduto = $pdo->lastInsertId();

    // Inserir imagens (cada uma em um registro separado)
    $sqlImagens = "INSERT INTO Imagem_produtos (foto) VALUES (:imagem)";
    $stmtImg = $pdo->prepare($sqlImagens);

    $idImagens = [];

    foreach ([$img1, $img2, $img3] as $img) {
        if ($img !== null) {
            $stmtImg->bindParam(':imagem', $img, PDO::PARAM_LOB);
            $stmtImg->execute();
            $idImagens[] = $pdo->lastInsertId();
        }
    }

    // Vincular imagens ao produto
    $sqlVincular = "INSERT INTO Produtos_has_Imagem_produtos 
        (Produtos_idProdutos, Imagem_produtos_idImagem_produtos) 
        VALUES (:idpro, :idimg)";

    $stmtVincular = $pdo->prepare($sqlVincular);

    foreach ($idImagens as $idImg) {
        $stmtVincular->execute([
            ":idpro" => $idproduto,
            ":idimg" => $idImg
        ]);
    }

    // Confirmar transação
    $pdo->commit();

    redirecWith("../paginas_logista/cadastro_produtos_logista.html", [
        "sucesso" => "Produto cadastrado com sucesso!"
    ]);

} catch (Exception $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    redirecWith("../paginas_logista/cadastro_produtos_logista.html", [
        "erro" => "Erro no banco de dados: " . $e->getMessage()
    ]);
}
?>
