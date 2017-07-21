<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
$di = new \Phalcon\DI\FactoryDefault();

$di->set('db', function () {
    return new \Phalcon\Db\Adapter\Pdo\Mysql(array(
        "host" => "192.168.15.8",
        "username" => "jeferson",
        "password" => "123456",
        "dbname" => "operand_iscool"
    ));
});

$app = new \Phalcon\Mvc\Micro($di);

//Retrieves all bank accounts
//Nome da rota usando o método GET
$app->get('/v1/bankaccounts', function () use ($app){
    //Comando SQL para  listar todas as contas bancarias ordenadas por nome
    $sql = "SELECT id,name,balance FROM bank_account ORDER BY name";

    //Dependencia DB, retorna uma nova conexão com o banco de dados
    $result = $app->db->query($sql);

    //Retornar como objeto
    $result->setFetchMode(Phalcon\Db::FETCH_OBJ);

    //Array vazio ;D
    $data = array();

    //
    while($bankAccount = $result->fetch()) {
        $data[] = array(
            'id'      => $bankAccount->id,
            'name'    => $bankAccount->name,
            'balance' => $bankAccount->balance,
        );
    }

    $response = new Phalcon\Http\Response();

    //SE NAO TIVER CONTAS CADASTRADAS
    //Retornar 404-Not-Found
    if($data == false) {
        //Status retornado.
        $response->setStatusCode(404, "Not Found");
        $response->setJsonContent(array('status' => 'NOT-FOUND'));
    } else {
        $response->setJsonContent(array(
            'status' => 'FOUND',
            'data' => $data
        ));
    }

    return $response;
});

$app->options('/v1/bankaccounts', function () use ($app) {
   $app->response->setHeader('Access-Control-Allow-Origin', '*');
});

//Adds a new bank account
$app->post('/v1/bankaccounts', function() use ($app) {

    $bankAccount = $app->request->getPost();

    if(!$bankAccount){
        $bankAccount = (array) $app -> request -> getJsonRawBody();
    }

    $response = new Phalcon\Http\Response();

    try {
        $result = $app->db->insert("bank_account",
            //ARRAY COM OS VALORES A SEREM INSERIDOS
            array($bankAccount['name'], $bankAccount['balance']),
            //ARRAY COM OS NOMES DAS COLUNAS
            array("name", "balance")
        );

        $response->setStatusCode(201, "Created");
        $bankAccount['id'] = $app->db->lastInsertId();
        $response->setJsonContent(array(
                                        'status' => 'OK',
                                        'data'   => $bankAccount));

    } catch (Exception $e) {
        $response->setStatusCode(409, "Conflict");
        $errors[] = $e->getMessage();
        $response->setJsonContent(array(
                                        'status' => 'ERROR',
                                        'messages' => $errors));
    }

    return $response;

});

//Adds a new withdrawal
$app->post('/v1/bankaccounts/withdrawal', function() use ($app) {

    $bankAccountOp = $app->request->getPost();

    if(!$bankAccountOp){
        $bankAccountOp = (array) $app -> request -> getJsonRawBody();
    }

    $response = new Phalcon\Http\Response();

    if(intval($bankAccountOp['value']) <= 0 || intval($bankAccountOp['bank_account_id'] < 0) || !existAccount($bankAccountOp['bank_account_id'], $app)) {
        $response->setStatusCode(409, "Conflict");
        $response->setJsonContent(array('status' => 'ERROR', 'messages' => 'Valor de saque ou conta incorreta'));
        return $response;
    }

    try {
        $result = $app->db->insert("bank_account_operations",
            array('saque', $bankAccountOp['bank_account_id'], date('Y-m-d H:i:s'), $bankAccountOp['value'] * -1),
            array('operation', 'bank_account_id', 'date', 'value')

        );

        updateBalance($bankAccountOp['bank_account_id'], $app);

        $response->setStatusCode(201, "Created");
        $bankAccountOp['id'] = $app->db->lastInsertId();
        $response->setJsonContent(array('status' => 'OK', 'data' => $bankAccountOp));

    } catch (Exception $e) {
        $response->setStatusCode(409, "Conflict");
        $errors[] = $e->getMessage();
        $response->setJsonContent(array('status' => 'ERROR', 'messages' => $errors));
    }

    return $response;

});

//Adds a new deposit
$app->post('/v1/bankaccounts/deposit', function() use ($app) {

    $bankAccountOp = $app->request->getPost();

    if(!$bankAccountOp){
        $bankAccountOp = (array) $app -> request -> getJsonRawBody();
    }

    $response = new Phalcon\Http\Response();

    if(intval($bankAccountOp['value']) <= 0 || intval($bankAccountOp['bank_account_id'] < 0) || !existAccount($bankAccountOp['bank_account_id'], $app)) {
        $response->setStatusCode(409, "Conflict");
        $response->setJsonContent(array('status' => 'ERROR', 'messages' => 'Valor de saque ou conta incorreta'));
        return $response;
    }

    try {
        $result = $app->db->insert("bank_account_operations",
            array('deposito', $bankAccountOp['bank_account_id'], date('Y-m-d H:i:s'), $bankAccountOp['value']),
            array('operation', 'bank_account_id', 'date', 'value')

        );

        updateBalance($bankAccountOp['bank_account_id'], $app);

        $response->setStatusCode(201, "Created");
        $bankAccountOp['id'] = $app->db->lastInsertId();
        $response->setJsonContent(array('status' => 'OK', 'data' => $bankAccountOp));

    } catch (Exception $e) {
        $response->setStatusCode(409, "Conflict");
        $errors[] = $e->getMessage();
        $response->setJsonContent(array('status' => 'ERROR', 'messages' => $errors));
    }

    return $response;

});

//add new Transfer
$app->post('/v1/bankaccounts/transfer', function() use ($app) {

    $bankAccountOp = $app->request->getPost();

    if(!$bankAccountOp){
        $bankAccountOp = (array) $app -> request -> getJsonRawBody();
    }

    $response = new Phalcon\Http\Response();

    //Validação de valor informado e verifica a as contas são validas.
    if(intval($bankAccountOp['value']) <= 0 || intval($bankAccountOp['origin_bank_account_id'] < 0) ||
        !existAccount($bankAccountOp['origin_bank_account_id'], $app) || !existAccount($bankAccountOp['destiny_bank_account_id'], $app)) {
        $response->setStatusCode(409, "Conflict");
        $response->setJsonContent(array('status' => 'ERROR', 'messages' => 'Valor de saque ou conta incorreta'));
        return $response;
    }

    try {
        //Cria uma nova operação para a conta de origem
        $result1 = $app->db->insert("bank_account_operations",
            array('transfer', $bankAccountOp['origin_bank_account_id'], date('Y-m-d H:i:s'), $bankAccountOp['value']*-1),
            array('operation', 'bank_account_id', 'date', 'value')
        );

        //Cria uma nova operação para a conta de destino
        $result2 = $app->db->insert("bank_account_operations",
            array('transfer', $bankAccountOp['destiny_bank_account_id'], date('Y-m-d H:i:s'), $bankAccountOp['value']),
            array('operation', 'bank_account_id', 'date', 'value')

        );

        updateBalance($bankAccountOp['origin_bank_account_id'], $app);
        updateBalance($bankAccountOp['destiny_bank_account_id'], $app);

        $response->setStatusCode(201, "Created");
        $bankAccountOp['id'] = $app->db->lastInsertId();
        $response->setJsonContent(array('status' => 'OK', 'data' => $bankAccountOp));

    } catch (Exception $e) {
        $response->setStatusCode(409, "Conflict");
        $errors[] = $e->getMessage();
        $response->setJsonContent(array('status' => 'ERROR', 'messages' => $errors));
    }

    return $response;

});


function updateBalance($bank_id, $app){
    $sqlUpdate = "UPDATE bank_account set balance = ( SELECT SUM(value) AS balance FROM bank_account_operations WHERE bank_account_id = ? ) WHERE id = ?";
    $app->db->query($sqlUpdate, array($bank_id, $bank_id));
}

//Verifica se existe uma conta com o id informado
function existAccount($id, $app) {
    $sql = "SELECT id FROM bank_account WHERE id = $id LIMIT 1";
    //Dependencia DB, retorna uma nova conexão com o banco de dados
    $result = $app->db->query($sql);
    //Retornar como objeto
    $data = array();
    $result->setFetchMode(Phalcon\Db::FETCH_OBJ);
    return $result->fetch();
}
//Updates bank account based on primary key
$app->put('/v1/bankaccounts/{id:[0-9]+}', function($id) use ($app) {

    $bankAccount = $app->request->getPut();
    $bankAccount['id'] = $id;
    $response = new Phalcon\Http\Response();

    try {
        $result = $app->db->update("bank_account",
            array("name", "balance"),
            array($bankAccount['name'], $bankAccount['balance']),
            "id = $id"
        );

        $response->setJsonContent(array(
                                        'status' => 'OK',
                                        'data' => $bankAccount));

    } catch (Exception $e) {
        $response->setStatusCode(409, "Conflict");
        $errors[] = $e->getMessage();
        $response->setJsonContent(array('status' => 'ERROR', 'messages' => $errors));
    }

    return $response;

});

//Deletes bank account based on primary key
$app->delete('/v1/bankaccounts/{id:[0-9]+}', function($id) use ($app) {
    $response = new Phalcon\Http\Response();

    try {
        $result = $app->db->delete("bank_account",
            "id = $id"
        );

        $response->setJsonContent(array('status' => 'OK'));

    } catch (Exception $e) {
        $response->setStatusCode(409, "Conflict");
        $errors[] = $e->getMessage();
        $response->setJsonContent(array('status' => 'ERROR', 'messages' => $errors));
    }

    return $response;
});

//Retrieves bank account based on primary key
$app->get('/v1/bankaccounts/listAndOp', function() use ($app) {

    $sql = "SELECT id,name,balance FROM bank_account ORDER BY id";
    $result = $app->db->query($sql);
    $result->setFetchMode(Phalcon\Db::FETCH_OBJ);

    $data = array();


    while($bankAccount = $result->fetch()){
        $data[] = array(
            'id'     => $bankAccount->id,
            'name'   => $bankAccount->name,
            'balance'=> $bankAccount->balance,
            'operations' => []
        );
    }

    $response = new Phalcon\Http\Response();

    if ($data == false) {
        $response->setStatusCode(404, "Not Found");
        $response->setJsonContent(array('status' => 'NOT-FOUND'));
    } else {

        //Percorre todos as Contas, relacionando com a tabela 'bank_account_operation'.
        //retornando um objeto com as operações daquela conta.
        foreach ($data as $item => $value) {
            $sqlOperations = "SELECT id, operation, bank_account_id, date, value
            FROM bank_account_operations WHERE bank_account_id = " . $value['id'] . " ORDER BY id";
            $resultOperations = $app->db->query($sqlOperations);
            $resultOperations->setFetchMode(Phalcon\Db::FETCH_OBJ);
            //$bankAccountOperations = $resultOperations->fetchAll();
            $data[$item]['operations'] = $resultOperations->fetchAll();
        }
        $response->setJsonContent($data);


//        $response->setJsonContent(array(
//            'id' => $bankAccount->id,
//            'name' => $bankAccount->name,
//            'balance' => $bankAccount->balance,
//            'operations' => $bankAccountOperations,
//        ));
    }

    return $response;

});

//Retrieves bank account based on primary key
$app->get('/v1/bankaccounts/search/{id:[0-9]+}', function($id) use ($app) {

    $sql = "SELECT id,name,balance FROM bank_account WHERE id = ?";
    $result = $app->db->query($sql, array($id));
    $result->setFetchMode(Phalcon\Db::FETCH_OBJ);
    $data = array();
    $bankAccount = $result->fetch();

    $response = new Phalcon\Http\Response();

    if ($bankAccount == false) {
        $response->setStatusCode(404, "Not Found");
        $response->setJsonContent(array('status' => 'NOT-FOUND'));
    } else {
        $sqlOperations = "SELECT id, operation, bank_account_id, date, value
        FROM bank_account_operations WHERE bank_account_id = " . $id . " ORDER BY date";

        $resultOperations = $app->db->query($sqlOperations);
        $resultOperations->setFetchMode(Phalcon\Db::FETCH_OBJ);
        $bankAccountOperations = $resultOperations->fetchAll();

        $response->setJsonContent(array(
            'id' => $bankAccount->id,
            'name' => $bankAccount->name,
            'balance' => $bankAccount->balance,
            'operations' => $bankAccountOperations,
        ));
    }

    return $response;

});

//Searches for bank account with $name in their name
$app->get('/v1/bankaccounts/search/{id:[a-z]+}', function($name) use ($app) {

    $sql = "SELECT id,name,balance FROM bank_account WHERE name LIKE ? ORDER BY name";
    $result = $app->db->query($sql, array("%".$name."%"));
    $result->setFetchMode(Phalcon\Db::FETCH_OBJ);
    $data = array();
    while ($bankAccount = $result->fetch()) {
        $data[] = array(
            'id' => $bankAccount->id,
            'name' => $bankAccount->name,
            'balance' => $bankAccount->balance,
        );
    }

    $response = new Phalcon\Http\Response();

    if ($data == false) {
        $response->setStatusCode(404, "Not Found");
        $response->setJsonContent(array('status' => 'NOT-FOUND'));
    } else {
        $response->setJsonContent(array(
            'status' => 'FOUND',
            'data' => $data
        ));
    }

    return $response;

});

//ROTA 404
$app->notFound(function () use ($app) {
    $app->response->setStatusCode(404, "Not Found")->
    sendHeaders();
    echo 'This is crazy, but this page was not found! :x';
});

//INICIO
$app->get('/', function () use ($app){
    var_dump($app);
});

$app->handle();