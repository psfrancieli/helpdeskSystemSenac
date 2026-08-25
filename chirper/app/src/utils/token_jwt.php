<?php

require_once __DIR__ . "/jwt/JWTExceptionWithPayloadInterface.php";

require_once __DIR__ . "/jwt/BeforeValidException.php";

require_once __DIR__ . "/jwt/CachedKeySet.php";

require_once __DIR__ . "/jwt/ExpiredException.php";

require_once __DIR__ . "/jwt/JWK.php";

require_once __DIR__ . "/jwt/JWT.php";

require_once __DIR__ . "/jwt/Key.php";

require_once __DIR__ . "/jwt/SignatureInvalidException.php";


use Firebase\JWT\JWT;

use Firebase\JWT\Key;

$env = parse_ini_file(__DIR__ . '/../../../../.env');
 

define('SECRET_KEY', "{$env["SECRETKEY"]}");
 

function criarToken($paylod){
    $token = [

        "iss" => "OctopusSystem.com",

        "iat" => time(),

        // "exp" => time() + (60 * 60 * 24),

        "sub" => $paylod

    ];

    return JWT::encode($token, SECRET_KEY, "HS256");

}
function decrypt($token){
    try{
        $key = new Key(SECRET_KEY, "HS256");
        $decode = JWT::decode($token, $key);
        $result = json_decode(json_encode($decode->sub), true);
        return $result;
    }catch(Exception $err){
        return false;
    }
}

function validateTokenJWT(){

    $headers = getallheaders();

    if (!isset($headers["Authorization"])){

        http_response_code(400);

        echo json_encode("Token não encontrado!");

        exit;

    }

    $token = str_replace("Bearer ", "", $headers["Authorization"]);

    $jwt = decrypt($token);

    if (!$jwt){

        http_response_code(400);

        echo json_encode("Token invalido!");

        exit;

    }

    $_SESSION['user'] = $jwt;
    return $jwt;
}