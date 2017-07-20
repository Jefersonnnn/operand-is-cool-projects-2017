/**
 * Created by Jeferson on 17/07/2017.
 */
document.getElementById("texto1").innerHTML = "My First Javascript";

var texto = 'Meu texto';
console.log(texto.toUpperCase());


var user = {
    firstName: 'Jeferson',
    lastName: 'Machado',
    fullName: function () {
        return this.firstName + ' ' + this.lastName
    }
};

console.log(user, user.fullName());

var objetos = {
    chave1:  'valor1',
    chave2: 'valor2',
};

for(var chave in objetos) {
    console.log(chave + ': ' + objetos[chave]);
}