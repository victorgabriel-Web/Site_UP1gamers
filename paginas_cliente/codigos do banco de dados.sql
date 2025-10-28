/*comentário de muitas linhas*/

-- comentário de 1 linha só --

-- criar o banco de dados - CREATE DATABASE
CREATE DATABASE UP1gamers ;
-- Excluir o Banco de daods;
Drop Database UP1gamers;
-- comando para usar no banco de dados
use UP1gamers;

-- criar tabelas 

create table cliente(
idCliente INT PRIMARY KEY auto_increment,
nome varchar(150) NOT NULL,
cpf int not null unique,
telefone varchar (20) not null,
email varchar (200) not null,
senha varchar (12) not null,
foto_perfil longblob
);

create table Endereco(
idEndereco int primary key auto_increment,
cep int not null,
cidade varchar(100) not null,
estado varchar(100) not null,
numero varchar(20) not null,
complemento varchar(100),
logradouro varchar(100) not null,
bairro varchar(100) not null,
tipo varchar(20)
);

-- Tabela com chave estrangeira FK - Foreign
create table Cliente_e_Endereco(
Cliente_idCliente int,
endereco_idEndereco int,
constraint foreign key (cliente_idcliente) references
cliente(idCliente),
constraint foreign key (endereco_idEndereco) references
Endereco(idEndereco)

);

CREATE table categoria_produtos(
idCategoriaProdutos int primary key auto_increment,
nome varchar(50) not null,
desconto double
);

create table Bannes(
idBanners INT PRIMARY KEY AUTO_INCREMENT,
imagem LONGBLOB NOT NULL,
data_validade DATE NOT NULL,
descricao VARCHAR(45) NOT NULL,
 link VARCHAR(45),
CategoriasProdutos_id INT,
constraint foreign key (CategoriasProdutos_id) REFERENCES
categoria_produtos(idCategoriaProduto)

);

create table cupom(
idcupom INT PRIMARY KEY AUTO_INCREMENT,
nome varchar(45) not null,
valor double not null,
data_validade varchar(45) not null,
quatidade INT 
);

create table fretes(
idfrete INT PRIMARY KEY AUTO_INCREMENT,
bairro varchar(45) not null,
valor double not null,
transportadora varchar(45)

);

create table forma_pagamento(
idforma_pagamento INT PRIMARY KEY AUTO_INCREMENT,
nome VARCHAR(45) not null
);

create table vendas(
idvendas INT PRIMARY KEY AUTO_INCREMENT,
dataVenda DATE not null,
formapagamento VARCHAR(45) not null,
valor_frete DOUBLE not null,
valor_produto DOUBLE not null,
valor_total DOUBLE not null,
data_entrega DATE,
situacao VARCHAR(45),
cod_pix VARCHAR(45),
cod_barras VARCHAR(45),
valor_total_desconto VARCHAR(45),
cliente_idcliente INT,
cupom_idcupom_desconto INT,
frete_idfrete INT,
forma_pagamento_idforma_pagamento INT,
constraint foreign key (cliente_idcliente) REFERENCES
cliente(idcliente),
constraint foreign key (cupom_idcupom_desconto) REFERENCES
cupom(idcupom),
foreign key (frete_idfrete ) REFERENCES
fretes(idfrete),
 constraint foreign key (forma_pagamento_idforma_pagamento) REFERENCES
forma_pagamento(idforma_pagamento)
);

create table imagem_produtos(
    idimagem_produtos INT PRIMARY KEY AUTO_INCREMENT,
    foto LONGBLOB not null,
    descricao VARCHAR(45)
);

create table produtos(
    idprodutos INT PRIMARY KEY AUTO_INCREMENT,
    nome VARCHAR(500) not null,
    descricao TEXT(500),
    quantidade INT not null,
    preco DOUBLE not null,
    tamanho VARCHAR(45),
    cor VARCHAR(45),
    codigo INT,
    preco_promocional DOUBLE,
    marcas_idmarcas INT,
    categoria_produtos_idcategoria_produtos INT,
	constraint foreign key (marcas_idmarcas) REFERENCES
	marcas(idmarcas),
    constraint foreign key ( categoria_produtos_idcategoria_produtos) REFERENCES
	categoria_produtos(idcategoriaproduto)
);

create table marcas(
idmarcas INT PRIMARY KEY AUTO_INCREMENT,
nome varchar(50) not null,
imagem varbinary(8000) not null

);

create table empresa(
    idEmpresa INT PRIMARY KEY AUTO_INCREMENT,
    nome_fantasia VARCHAR(100) not null,
    cnpj_cpf VARCHAR(45) not null,
    telefone VARCHAR(20) not null,
    instagram VARCHAR(100) ,
    linkedin VARCHAR(100),
    facebook VARCHAR(100) ,
    whatsapp VARCHAR(100),
    logo VARCHAR(8000) not null,
    usuario VARCHAR(45) not null,
    senha VARCHAR(12) not null
);

-- COMANDOS SQL
/*
CREATE DATABASE CRIA BANCO DE DADOS
CREATE TABLE CRIA TABELAS
USE INICIA A EXECUÇÃO DO BANCO DE DADOS
*/
/* EXCLUSÃO:
DROP DATABASE SERVE PARA EXCLUIR BANCO DE DAD
DROP TABLE EXCLUI TABELAS
*/
DROP DATABASE nomeBancoDeDados;
DROP TABLE nomeTabela;

/*só é possivel excluir uma tabela caso não tenha
 nenhuma outra tabela conectada a ela*/
 
 /* SELECT VISUALIZAR/LISTAR OS DADOS REGISTRADOS DENTRO DA TABELA */

SELECT * FROM Cliente;
SELECT idCliente,nome,cpf FROM Cliente;
/* insert - cadastrar dados dentro da tabela*/

INSERT INTO nomeTabela (campol, campo2, campo3)
VALUES ("", 2.7,2);
INSERT INTO Cliente(nome,cpf,email,telefone,senha)
values("Victor Gabriel",0455466721,"vg@gmail.com",
"63 99254-4451","270429abc");

-- COMANDO PARA  ALTERAR O CPF DO TIPO INT PARA VARCHAR
alter table Cliente  modify cpf varchar(11) UNIQUE;

-- INSRIDO DADOS NA TABELA ENDERECO
INSERT INTO Endereco(cep,cidade,estado,numero,complemento,
logradouro,bairro,tipo) values(77819090,"Araguaina","TO",
"2900","Q11 L9","AVENIDA JK","SETOR SUL","CASA");

select * FROM ENDERECO;
-- CONECTANDO O CLIENTE COM SEU ENDERECO 
INSERT INTO CLIENTE_E_ENDERECO
(CLIENTE_IDCLIENTE,ENDERECO_IDENDERECO) values
(1,1); 

-- INSERINDO DADOS PARA MARCAS
insert INTO MARCAS(NOME,IMAGEM)
VALUES ("PICHAU","image/png;base64,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");

SELECT * FROM Marcas;

-- INSERINDO DADOS PARA A TABELA PRODUTOS
INSERT INTO Produtos (nome, descricao, quantidade
,preco,tamanho,cor, codigo, marcas_idmarcas) VALUES 
("gabinete","munitor""video games","jogos","sku0989",1);

select * from produtos;

-- COMANDO PARA ALTERAR O CPF DO TIPO INT PARA VARCHAR
ALTER TABLE Cliente MODIFY cpf VARCHAR(11) UNIQUE;

-- CRIAR UM INSERT PARA O RESTANTE DAS TABELAS
-- data - "2025-09-10"

