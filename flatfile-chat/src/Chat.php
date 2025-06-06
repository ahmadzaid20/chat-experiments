<?php
namespace ChatApp;

use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;

class Chat implements MessageComponentInterface
{
    protected $clients;
    protected $rooms;

    public function __construct()
    {
        $this->clients = new \SplObjectStorage;
        $this->rooms = [];
    }

    public function onOpen(ConnectionInterface $conn)
    {
        parse_str($conn->httpRequest->getUri()->getQuery(), $queryParams);

        $conn->username = $queryParams['username'] ?? 'Anonymous';
        $conn->room = $queryParams['room'] ?? 'General';

        $this->clients->attach($conn);
        $this->rooms[$conn->room][$conn->resourceId] = $conn->username;

        echo "New connection: [{$conn->resourceId}] {$conn->username} joined {$conn->room}\n";

        $this->broadcastUserList($conn->room);
    }

    public function onMessage(ConnectionInterface $from, $msg)
    {
        $data = json_decode($msg, true);
        $room = $from->room;

        switch ($data['action']) {
            case 'checkMessages':
                $this->sendChatHistory($from, $room);
                break;

            case 'typing':
            case 'stopTyping':
                $this->broadcastToRoom($room, $msg, $exclude = $from);
                break;

            case 'sendMessage':
            default:
                $message = [
                    'username' => $from->username,
                    'room' => $room,
                    'message' => $data['message'],
                    'timestamp' => date('Y-m-d H:i:s'),
                    'action' => 'sendMessage'
                ];
                $jsonMsg = json_encode($message);
                $this->logMessage($room, $jsonMsg);
                $this->broadcastToRoom($room, $jsonMsg, $exclude = $from);
                break;
        }
    }

    public function onClose(ConnectionInterface $conn)
    {
        $this->clients->detach($conn);
        unset($this->rooms[$conn->room][$conn->resourceId]);

        echo "Connection {$conn->resourceId} closed\n";

        if (empty($this->rooms[$conn->room])) {
            unset($this->rooms[$conn->room]);
        }

        $this->broadcastUserList($conn->room);
    }

    public function onError(ConnectionInterface $conn, \Exception $e)
    {
        echo "Error: {$e->getMessage()}\n";
        $conn->close();
    }

    protected function broadcastToRoom(string $room, string $message, ConnectionInterface $exclude = null)
    {
        foreach ($this->clients as $client) {
            if ($client->room === $room && $client !== $exclude) {
                $client->send($message);
            }
        }
    }

    protected function broadcastUserList(string $room)
    {
        $users = array_values($this->rooms[$room] ?? []);
        $payload = json_encode([
            'action' => 'updateUsers',
            'users' => $users
        ]);

        $this->broadcastToRoom($room, $payload);
    }

    protected function sendChatHistory(ConnectionInterface $conn, string $room)
    {
        $file = __DIR__ . "/../logs/chat_log_{$room}.txt";
        if (!file_exists($file)) return;

        $lines = file($file, FILE_IGNORE_NEW_LINES);
        foreach ($lines as $line) {
            $conn->send($line);
        }
    }

    protected function logMessage(string $room, string $message)
    {
        $file = __DIR__ . "/../logs/chat_log_{$room}.txt";
        file_put_contents($file, $message . PHP_EOL, FILE_APPEND | LOCK_EX);
    }
}
