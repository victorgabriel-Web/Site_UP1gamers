<?php
include 'conexao.php';
$nome = $_POST['nome'];
$img = !empty($_FILES['imagem']['tmp_name']) ? file_get_contents($_FILES['imagem']['tmp_name']) : null;

$stmt = $conn->prepare("INSERT INTO Marcas (nome, imagem) VALUES (?, ?)");
$stmt->bind_param("sb", $nome, $img);
$stmt->send_long_data(1, $img);
$stmt->execute();

header("Location: cadastro_marcas.php");
?>
