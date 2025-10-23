/* variavel:
é como uma caixa que serve para
 armazenar informações. é um espaço no
  programa/computador que armazena dados.
*/

// pode mudar o valor a qualquer momento
let nomevariavel = 1; //inteiro
let nomevariavel2 = "jhessik"; //varchar
let nomevariavel3 = 2.7; //duble
let nomevariavel4 = true //booleano


// váriavel constante, que não altera o valor
 const nome ="jhessik";

 let soma = 3+5; // 8
let subtracao= 5-3; //2
let multiplicacao =3*5; //15
let divisao = 10/2 //5

// jnutar textos
let primeironome = "jhessik ";
let sobrenome = "Leal";
let nomecompleto = primeironome + sobrenome;


// função ela imprime o Olá mundo
/*função sem parametro 
que não recebe dados dentro do ()*/
function imprimirMsg(){
// console é utilizado para mostrar textos
console.log("Hello Word!");
console.log(primeironome +"Bem vindo!");
}
// função com parametro
function somarvalores(valor1,valor2){
    let soma = valor1 + valor2;
    console.log("O Resultado da soma é: " + soma);
}

imprimirMsg();

somarvalores(20,40);
somarvalores(20,40);

// condicional

/*
É uma ação que é executada com base em um critério
se chover irei ao cinema, se fizer sol irei à praia
hoje choveu!

- hoje choveu! (ir ao cinema)
- hoje fez sol! (ir à praia)

Se fizer sol e eu tiver dinheiro, irei à praia, senão ficarei em casa.

- Fez sol e tenho dinheiro (Irei à praia)
- Fez sol mas não tenho dinheiro(casa)
- Choveu mas eu tenho dinheiro (casa)
*/

let n1 = 15;
let n2 = 45;
// if = se else = senão

// se o n1 for igual a 10
if(n1=10){
    console.log("Irei à praia");

}else{
    console.log("Ficarei em casa");
}

// se n1 for maior que 10
if(n1>10){
console.log("Irei à praia!");
}else{
console.log("Fico em casa!");
}

// se n1 for maior que 10 E n2 for menor que 40
if(n1>10 & n2<40) {
console.log("Irei à praia!");
}else{
console.log("Fico em casa!");
}

// se n1 for maior que 10 OU n2 for menor que 40
if(n1>10 || n2<40) {
console.log("Irei à praia!");
}else{
console.log("Fico em casa!");
}

// se n1 for maior que 10 E n1 for maior que n2 E n2 for maior que 45
if(n1>10 & n1>n2 & n2<40) {
console.log("Irei à praia!");
}else{
console.log("Fico em casa!");
}


 /*
1° condição n1 menor que 10 E n2 maior que n1
OU
2º condição n2 maior que 40 e n2 menor que 46
*/
if((n1<10 & n1<n2) || (n2>40 & n2<46)) {
console.log("Irei à praia!");
}else{
console.log("Fico em casa!");
}
//if aninhado
// se n1 for maior que 12 E o dobro de n1 for maior que 48
if(n1>12 & n * 2 > 48 ) {
console.log("Irei à praia!");
// se n1 for maior ou igual a 15 E n2 menor que 45
}else if( n * 1 >= 15 & n * 2 < 45 ) {
console.log("Vou ao cinema!");
/*se n1 for maior que 14 E n2 for igual a 45
E
se n2 maior que n1 ou n1 maior ou igual a 15 */
}else if((n1>14 && n2==45) & (n2>n1 || n * 1 >= 15 ) ){
console.log("Vou ao shopping!");
}else{
console.log("Fico em casa!");
}

// objeto carro
let carro = {
cor: "preto",
placa: "KJH9876",
modelo: "fusca",
kmRodados:120000,
som: true,
arcondicionado: false
};
console.log(carro.cor+carro.modelo+carro.placa);