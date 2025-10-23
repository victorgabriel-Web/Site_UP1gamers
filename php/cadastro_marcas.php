<?php

require_once __DIR__ . '/conexao.php';

function redirect_with(string $url, array $params = []): void {
  if ($params) {
    $qs  = http_build_query($params);
    $url .= (strpos($url, '?') === false ? '?' : '&') . $qs;
  }
  header("Location: $url");
  exit;
}

function read_image_to_blob(?array $file): ?string {
  if (!$file || !isset($file['tmp_name']) || $file['error'] !== UPLOAD_ERR_OK) return null;
  $bin = file_get_contents($file['tmp_name']);
  return $bin === false ? null : $bin;
}



// LISTAGEM DE MARCAS COM IMAGEM
if ($_SERVER["REQUEST_METHOD"] === "GET" && isset($_GET["listar"])){
// Define o tipo de resposta: JSON e com codificação UTF-8
  header('Content-Type: application/json; charset=utf-8');

  try {
    // Faz a consulta no banco — busca id, nome e imagem (blob)
    $stmt = $pdo->query("SELECT idMarcas, nome, imagem FROM Marcas ORDER BY idMarcas DESC");

    // Pega todas as linhas retornadas como array associativo
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Mapeia cada linha para o formato desejado:
    //  - converte o id para inteiro
    //  - mantém o nome como texto
    //  - converte o blob da imagem para base64 (ou null se não houver imagem)
    $marcas = array_map(function ($r) {
      return [
        'idMarcas' => (int)$r['idMarcas'],
        'nome'     => $r['nome'],
        'imagem'   => !empty($r['imagem']) ? base64_encode($r['imagem']) : null
      ];
    }, $rows);

    // Retorna o JSON com:
    //  - ok: true  → indica sucesso
    //  - count: quantidade de marcas encontradas
    //  - marcas: array com todos os dados
    echo json_encode(
      ['ok'=>true,'count'=>count($marcas),'marcas'=>$marcas],
      JSON_UNESCAPED_UNICODE // mantém acentos corretamente
    );

  } catch (Throwable $e) {
    // Se acontecer qualquer erro (ex: problema no banco),
    // envia código HTTP 500 e o erro no formato JSON
    http_response_code(500);
    echo json_encode(['ok'=>false,'error'=>$e->getMessage()]);
  }

  //  Interrompe a execução do restante do arquivo.
  
  exit;

}


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
/* CADASTRAR */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  redirect_with('../PAGINAS_LOGISTA/cadastro_produtos_logista.html', [
    'erro_marca' => 'Método inválido'
  ]);
}


  $nome = trim($_POST['nomemarca'] ?? '');
  $imgBlob = read_image_to_blob($_FILES['imagemmarca'] ?? null);

  if ($nome === '') {
    redirect_with('../PAGINAS_LOGISTA/cadastro_produtos_logista.html', [
      'erro_marca' => 'Preencha o nome da marca.'
    ]);
  }

  $sql = "INSERT INTO Marcas (nome, imagem) VALUES (:n, :i)";
  $st  = $pdo->prepare($sql);
  $st->bindValue(':n', $nome, PDO::PARAM_STR);
  if ($imgBlob === null) $st->bindValue(':i', null, PDO::PARAM_NULL);
  else $st->bindValue(':i', $imgBlob, PDO::PARAM_LOB);
  $st->execute();

  redirect_with('../PAGINAS_LOGISTA/cadastro_produtos_logista.html', [
    'cadastro_marca' => 'ok'
  ]);
} catch (Throwable $e) {
  redirect_with('../PAGINAS_LOGISTA/cadastro_produtos_logista.html', [
    'erro_marca' => 'Erro no banco de dados: ' . $e->getMessage()
  ]);
}
