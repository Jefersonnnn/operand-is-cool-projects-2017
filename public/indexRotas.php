<?php
header('Content-Type: text/html; charset=utf-8');
$app = new Phalcon\Mvc\Micro();

$app->get('/diga/ola/{name}', function ($nome) {
    echo json_encode(array($nome, "bem", "vindo"));
});

$app->notFound(function () use ($app) {
    $app->response->setStatusCode(404, "Not Found")->
        sendHeaders();
    echo 'NADA ENCONTRADO!';
});

$app->handle();
