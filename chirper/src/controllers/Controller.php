<?php
/* 
Essa e uma classe auxiliar para quando for transformar algo em json,
 ou receber uma requisição em json, use esta classe para evitar redundancia
no codigo. A explicação do q cada metodo faz esta ABAIXO!! 
*/
class Controller
{
    protected function getBody(): array
    {
        return json_decode(file_get_contents("php://input"), true) ?? []; /*
        lê o corpo da requisição HTTP, converte o JSON enviado pelo frontend em um array 
        associativo do PHP e, caso não exista um corpo válido, retorna um array vazio.
        EXEMPLO:
        recebi um json {
           "nome": "caique",
           "telefone": "123456"
        } 
        isso vira um array associativo:
        [
            "nome" => "caique",
            "telefone"=>"1234566"
        ]
        */
    }

    protected function response($data, int $status = 200)
    {
        http_response_code($status);//Status e o tipo de resposta exemplo: 200 é ok , 404 e não encontrado
        header("Content-Type: application/json"); //Responsavel por avisar que a requisição q estou enviando e um json

        echo json_encode($data);//Converte um array associativo em um formato json
        exit;
    }
}
?>