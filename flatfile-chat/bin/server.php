<?php
require __DIR__ . '/../vendor/autoload.php';

use Ratchet\Server\IoServer;
use Ratchet\Http\HttpServer;
use Ratchet\WebSocket\WsServer;
use ChatApp\Chat;

$server = IoServer::factory(
    new HttpServer(new WsServer(new Chat())),
    8080
);

echo "WebSocket server running on port 8080...\n";
$server->run();
