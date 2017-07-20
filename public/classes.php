<?php
header('Content-Type: text/html; charset=utf-8');

//function comparar($num) {
//
//    if($num > 10)
//        return $num . ' é maior que 10';
//
//    if ($num < 10)
//        return $num . ' é menor que 10';
//
//    return $num . ' é 10';
//}
//
//
//echo comparar(5);

class Usuario
{
    protected $id;
    protected $nome;
    protected $email;

    public function getId()
    {
        return $this->id;
    }

    public function setId($id)
    {
        if(!is_int($id)){
            throw new InvalidArgumentException('O parametro deve ser do tipo inteiro');
        }
        $this->id = $id;
    }

    public function getNome()
    {
        return $this->nome;
    }

    public function setNome($nome)
    {
        $this->nome = $nome;
    }

    public function getEmail()
    {
        return $this->email;
    }

    public function setEmail($email)
    {
        $this->email = $email;
    }


}
//
//$user = new Usuario();
//
//try {
////$user->id = 1;
//    $user->setId(4);
////$user->nome = 'Marvin';
//    $user->setNome("Marvin");
////$user->email = 'marvin@marvin.com.br';
//    $user->setEmail("marvin@marvin.com.br");
//}catch (InvalidArgumentException $e) {
//    die($e->getMessage());
//} catch (Exception $e) {
//    die($e->getMessage());
//
//}
//echo $user->getId() . '<br>';
//echo $user->getNome() . '<br>';
//echo $user->getEmail() . '<br>';

class Admin extends Usuario
{
    private $senha;

    public function setSenha($senha) {
        $this->senha = md5($senha);
    }

    public function getSenha() {
        return $this->senha;
    }

}


$adm = new Admin();
$adm->setId(1);
$adm->setNome('Jeferson');
$adm->setEmail('jeferson@univille.br');
$adm->setSenha('123');
var_dump($adm);

