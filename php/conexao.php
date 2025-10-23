<?php
/*conexao.php*/

$host="localhost";     /*servidor do banco*/
$db= "up1gamers";        /*nome do banco de dados*/
$user= "root";      /*usuario do MySQL*/
$pass= "";          /*senha do MySQL (ajuste se houver)*/ 

try {
    // estabelecendo conexao
    $pdo = new PDO("mysql:host=$host; dbname=$db;
    charset=utf8mb4", $user, $pass);
    // verificando se deu certo ou não
    $pdo->setAttribute (PDO::ATTR_ERRMODE,
    PDO:: ERRMODE_EXCEPTION);
  
}catch (PDOException $e) {
    // caso de erro, ele executa o catch e imprime a me
    die("Erro ao conectar ao banco de dados: "
    . $e->getMessage());
}

?>
