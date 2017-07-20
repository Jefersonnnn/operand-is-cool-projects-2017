<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
$di = new \Phalcon\DI\FactoryDefault();

$di->set('db', function () {
    return new \Phalcon\Db\Adapter\Pdo\Mysql(array(
        "host" => "192.168.106.84",
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