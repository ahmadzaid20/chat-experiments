<?php
use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;

require __DIR__ . '/../vendor/autoload.php';

class Chat implements MessageComponentInterface {
    protected $clients;
    protected $rooms;

    public function __construct() {
        $this->clients = new \SplObjectStorage;
        $this->rooms = [];
    }

    protected function loadMessagesFromFile($roomId) {
        $filePath = __DIR__ . "/../rooms/room_$roomId.json";
        return file_exists($filePath)
            ? json_decode(file_get_contents($filePath), true) ?? []
            : [];
    }

    protected function saveMessagesToFile($roomId, $messages) {
        $dir = __DIR__ . '/../rooms';
        if (!is_dir($dir)) mkdir($dir, 0777, true);
        file_put_contents("$dir/room_$roomId.json", json_encode($messages, JSON_PRETTY_PRINT));
    }

    public function onOpen(ConnectionInterface $conn) {
        parse_str($conn->httpRequest->getUri()->getQuery(), $params);
        $conn->username = $params['username'] ?? 'Anonymous';
        $conn->room = $params['room'] ?? 'General';
        $conn->userId = $params['userId'] ?? uniqid();

        $this->clients->attach($conn);
        $room = $conn->room;

        if (!isset($this->rooms[$room])) $this->rooms[$room] = [];
        $this->rooms[$room][$conn->resourceId] = [
            'username' => $conn->username,
            'userId' => $conn->userId
        ];

        echo "Connected: {$conn->username} (UserID: {$conn->userId}) in room: {$room}\n";

        $this->sendUsersInRoom($room);
        $this->sendSavedMessagesForRoom($conn, $room);
    }

    public function onMessage(ConnectionInterface $from, $msg) {
        $data = json_decode($msg, true);
        switch ($data['action']) {
            case 'sendMessage':
                $this->handleSendMessage($from, $data);
                break;
            case 'typing':
            case 'stopTyping':
                $this->broadcastTypingStatus($from, $data['action']);
                break;
            case 'messageReceived':
            case 'messageSeen':
                $this->updateMessageStatus($data['messageId'], $data['action'], $from->userId, $from->room);
                break;
        }
    }

    protected function handleSendMessage(ConnectionInterface $from, $data) {
        $message = [
            'messageId' => uniqid(),
            'username' => $from->username,
            'userId' => $from->userId,
            'room' => $from->room,
            'message' => $data['message'] ?? '',
            'timestamp' => date('Y-m-d H:i:s'),
            'action' => 'sendMessage',
            'status' => [
                'sent' => [$from->userId],
                'received' => [],
                'seen' => []
            ]
        ];

        $messages = $this->loadMessagesFromFile($from->room);
        $messages[] = $message;
        $this->saveMessagesToFile($from->room, $messages);

        $json = json_encode($message);
        foreach ($this->clients as $client) {
            if ($client->room === $from->room) {
                $client->send($json);
            }
        }
    }

    protected function broadcastTypingStatus(ConnectionInterface $from, $action) {
        $data = ['action' => $action, 'username' => $from->username];
        foreach ($this->clients as $client) {
            if ($client !== $from && $client->room === $from->room) {
                $client->send(json_encode($data));
            }
        }
    }

    protected function updateMessageStatus($messageId, $action, $userId, $room) {
        $statusMap = [
            'messageReceived' => 'received',
            'messageSeen' => 'seen'
        ];

        if (!isset($statusMap[$action])) {
            echo "Unknown action: $action\n";
            return;
        }

        $statusKey = $statusMap[$action];

        $messages = $this->loadMessagesFromFile($room);
        foreach ($messages as &$msg) {
            if ($msg['messageId'] === $messageId) {
                if (!isset($msg['status'][$statusKey])) {
                    $msg['status'][$statusKey] = [];
                }

                if (!in_array($userId, $msg['status'][$statusKey])) {
                    $msg['status'][$statusKey][] = $userId;
                }
                break;
            }
        }

        $this->saveMessagesToFile($room, $messages);

        $notify = json_encode([
            'action' => 'updateMessageStatus',
            'messageId' => $messageId,
            'status' => $statusKey
        ]);

        foreach ($this->clients as $client) {
            if ($client->room === $room) {
                $client->send($notify);
            }
        }
    }


    protected function sendUsersInRoom($room) {
        if (!isset($this->rooms[$room])) return;

        $usernames = array_map(fn($u) => $u['username'], $this->rooms[$room]);
        $data = json_encode(['action' => 'updateUsers', 'users' => $usernames]);

        foreach ($this->clients as $client) {
            if ($client->room === $room) {
                $client->send($data);
            }
        }
    }

    protected function sendSavedMessagesForRoom(ConnectionInterface $conn, $room) {
        $messages = $this->loadMessagesFromFile($room);
        foreach ($messages as $msg) {
            $conn->send(json_encode($msg));
        }
    }

    public function onClose(ConnectionInterface $conn) {
        $this->clients->detach($conn);
        unset($this->rooms[$conn->room][$conn->resourceId]);
        if (empty($this->rooms[$conn->room])) {
            unset($this->rooms[$conn->room]);
        }
        $this->sendUsersInRoom($conn->room);
        echo "Disconnected: {$conn->username} (UserID: {$conn->userId}) from room: {$conn->room}\n";
    }

    public function onError(ConnectionInterface $conn, \Exception $e) {
        echo "Error: {$e->getMessage()}\n";
        $conn->close();
    }
}

$server = \Ratchet\Server\IoServer::factory(
    new \Ratchet\Http\HttpServer(
        new \Ratchet\WebSocket\WsServer(
            new Chat()
        )
    ),
    8080
);

echo "WebSocket server started on port 8080...\n";
$server->run();
