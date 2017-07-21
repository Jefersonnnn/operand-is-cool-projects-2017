/**
 * Created by Jeferson Machado on 19/07/2017.
 */

var app = new Vue({
    el: '#app',
    data: {
        titulo: 'Banco App',
        withdrawlValueModel:0,
        depositValueModel:0,

        depositAccountModel: null ,
        withdrawlAccountModel: null,

        listAccounts:[],

        transferValueModel: 0,
        selectOriginTransfModel: 1,
        selectDestinyTransfModel: 2,

        selectAccountView1 : 0,
        selectAccountView2 : 0,

        bankAccount1: {
            id: 0,
            name: 'Conta 1',
            balance: 0,
            operations: []
        },
        bankAccount2: {
            id: 0,
            name: 'Conta 2',
            balance: 0,
            operations: []
        },
    },
	watch: {
		selectAccountView1: function(val) {
			this.bankAccount1 = listAccounts[selectAccountView1];
		}
	},
    created: function () {
        console.log('Instância Vue.js criada!');
    },

    methods: {
        loadAccounts: function () {
          var vm = this;
          //v1/bankaccounts/listAndOp
            axios.get('/v1/bankaccounts/listAndOp')
                .then(function (response) {
                    vm.listAccounts = response.data;
                })
                .catch(function (error) {
                    console.log(error);
                })

        },

        transferencia: function(){
            var vm = this;
            axios.post('/v1/bankaccounts/transfer', {
                origin_bank_account_id: vm.selectOriginTransfModel,
                destiny_bank_account_id: vm.selectDestinyTransfModel,
                value: vm.transferValueModel
            })
                .then(function(response){
                    console.log(response);
                    vm.loadAccounts();
                })
                .catch(function(error){
                    console.log(error);
                });
        },
        deposito: function () {
            var vm = this;
            axios.post('/v1/bankaccounts/deposit', {
                bank_account_id : vm.depositAccountModel,
                value : vm.depositValueModel,
            })
                .then(function (response) {
                    console.log(response);
					vm.loadAccounts();
                    
                })
                .catch(function (error) {
                    console.log(error)
                });
        },
        saque: function () {
            var vm = this;
            axios.post('/v1/bankaccounts/withdrawal', {
                bank_account_id : vm.withdrawlAccountModel,
                value : vm.withdrawlValueModel,
            })
                .then(function (response) {
                    console.log(response);
                    vm.loadAccounts();
                })
                .catch(function (error) {
                    console.log(error)
                });
        }
    },
    //Quando a aplicação Vue.js está montada(pronta).
    mounted: function () {
        var vm = this;

        setInterval(function () {
            vm.loadAccounts();

        }, 30000);
        this.loadAccounts();
		
		setInterval(function () {
            vm.bankAccount1 = vm.listAccounts[vm.selectAccountView1];
            vm.bankAccount2 = vm.listAccounts[vm.selectAccountView2];

        }, 500);
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