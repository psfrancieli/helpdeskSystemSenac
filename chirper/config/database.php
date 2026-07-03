<?php

$env = parse_ini_file(__DIR__ . '/../../.env');

return [
    'host'     => 'ep-green-night-acel3qx9-pooler.sa-east-1.aws.neon.tech',
    'port'     => 5432,
    'dbname'   => 'neondb',
    'user'     => $env['PGUSER'] ?? '',
    'password' => $env['PGPASSWORD'] ?? '',
];

?>