/**
 * Created by Jeferson Machado on 19/07/2017.
 */

var app = new Vue({
    el: '#app',
    data: {
        titulo: 'Banco Imobiliario',

        bankAccount1: {
            id: 0,
            name: 'Conta 1',
            balance: 0,
            operations: []
        }
    },
    created: function () {
        console.log('Instância Vue.js criada!');
    },
    methods: {
        loadDataFromAccount1: function () {
            //Instancia do APP (VUE.jS)
            var vm = this;
            axios.get('../v1/bankaccounts/search/1')
                .then(function (response) {
                    vm.bankAccount1 = response.data;
                })
                .catch(function (error) {
                    console.log(error);
                });
        }
    },
    //Quando a aplicação Vue.js está montada(pronta).
    mounted: function () {
        var vm = this;

        setInterval(function () {
            vm.loadDataFromAccount1();
        }, 30000);
        this.loadDataFromAccount1();
    }
});


function novaConta(name, balance) {
    axios.post('/v1/bankaccounts/', {
        name: name,
        balance: balance
    })
        .then(function (response) {
            if (response.status === 201) {
                console.log(response.data);
            }
        })
        .catch(function (error) {
            console.log(error);
        });
}

function listaContas() {
    axios.get('/v1/bankaccounts/')
        .then(function (response) {
            console.log(response.data);
        })
        .catch(function (error) {
            console.log(error)
        });

}