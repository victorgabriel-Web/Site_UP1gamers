<?php
require_once __DIR__ . "/conexao.php";

/* ===========================================================
   FUNÇÃO DE REDIRECIONAMENTO
=========================================================== */
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

/* ===========================================================
   LISTAR FRETES
=========================================================== */
if ($_SERVER["REQUEST_METHOD"] === "GET" && isset($_GET["listar"])) {
    try {
        $sql = "SELECT idFretes AS id, bairro, valor, transportadora
                FROM Fretes
                ORDER BY bairro, valor";

        $stmt = $pdo->query($sql);
        $fretes = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $formato = strtolower($_GET["format"] ?? "option");

        if ($formato === "json") {
            $saida = array_map(function ($item) {
                return [
                    "id"            => (int)$item["id"],
                    "bairro"        => $item["bairro"],
                    "valor"         => (float)$item["valor"],
                    "transportadora"=> $item["transportadora"]
                ];
            }, $fretes);

            header("Content-Type: application/json; charset=utf-8");
            echo json_encode(["ok" => true, "fretes" => $saida], JSON_UNESCAPED_UNICODE);
            exit;
        }

        // Saída HTML (para SELECT)
        header("Content-Type: text/html; charset=utf-8");
        foreach ($fretes as $f) {
            $id = (int)$f["id"];
            $bairro = htmlspecialchars($f["bairro"], ENT_QUOTES, "UTF-8");
            $transp = $f["transportadora"] ? " (" . htmlspecialchars($f["transportadora"], ENT_QUOTES, "UTF-8") . ")" : "";
            $valorFmt = number_format((float)$f["valor"], 2, ",", ".");
            echo "<option value=\"{$id}\">{$bairro}{$transp} - R$ {$valorFmt}</option>\n";
        }
        exit;
    } catch (Throwable $e) {
        if (isset($_GET["format"]) && strtolower($_GET["format"]) === "json") {
            header("Content-Type: application/json; charset=utf-8", true, 500);
            echo json_encode(["ok" => false, "error" => $e->getMessage()], JSON_UNESCAPED_UNICODE);
        } else {
            header("Content-Type: text/html; charset=utf-8", true, 500);
            echo "<option disabled>Erro ao carregar fretes</option>";
        }
        exit;
    }
}

/* ===========================================================
   CADASTRAR FRETE
=========================================================== */
if ($_SERVER["REQUEST_METHOD"] === "POST" && ($_POST["acao"] ?? "") === "cadastrar") {
    try {
        $bairro = trim($_POST["bairro"] ?? "");
        $valor = (float)($_POST["valor"] ?? 0);
        $transportadora = trim($_POST["transportadora"] ?? "");

        // Validação
        $erros = [];
        if ($bairro === "" || $valor <= 0) {
            $erros[] = "Preencha todos os campos obrigatórios.";
        }

        if ($erros) {
            redirecWith("../paginas_logista/frete_pagamento_logista.html", ["erro" => implode(" ", $erros)]);
        }

        $sql = "INSERT INTO Fretes (bairro, valor, transportadora)
                VALUES (:bairro, :valor, :transportadora)";
        $stmt = $pdo->prepare($sql);
        $ok = $stmt->execute([
            ":bairro"        => $bairro,
            ":valor"         => $valor,
            ":transportadora"=> $transportadora
        ]);

        if ($ok) {
            redirecWith("../paginas_logista/frete_pagamento_logista.html", ["cadastro" => "ok"]);
        } else {
            redirecWith("../paginas_logista/frete_pagamento_logista.html", ["erro" => "Erro ao cadastrar."]);
        }
    } catch (Throwable $e) {
        redirecWith("../paginas_logista/frete_pagamento_logista.html", ["erro" => "Erro no banco: " . $e->getMessage()]);
    }
}

/* ===========================================================
   EXCLUIR FRETE
=========================================================== */
if ($_SERVER["REQUEST_METHOD"] === "POST" && ($_POST["acao"] ?? "") === "excluir") {
    try {
        $id = (int)($_POST["id"] ?? 0);
        if ($id <= 0) {
            redirecWith("../paginas_logista/frete_pagamento_logista.html", ["erro" => "ID inválido."]);
        }

        $stmt = $pdo->prepare("DELETE FROM Fretes WHERE idFretes = :id");
        $stmt->bindValue(":id", $id, PDO::PARAM_INT);
        $stmt->execute();

        redirecWith("../paginas_logista/frete_pagamento_logista.html", ["excluir" => "ok"]);
    } catch (Throwable $e) {
        redirecWith("../paginas_logista/frete_pagamento_logista.html", ["erro" => "Erro ao excluir: " . $e->getMessage()]);
    }
}
?>
