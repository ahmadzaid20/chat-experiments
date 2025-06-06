<?php
use Ratchet\MessageComponentInterface;
use Ratchet\ConnectionInterface;

require __DIR__ . '/../vendor/autoload.php';

class Chat implements MessageComponentInterface {
    protected $clients;
    protected $rooms;
    protected $db;

    public function __construct() {
        $this->clients = new \SplObjectStorage;
        $this->rooms = [];

        // Connect to the database
        $this->db = new mysqli('localhost', 'root', '', 'chat_app');
        if ($this->db->connect_error) {
            die("Database connection failed: " . $this->db->connect_error);
        }
    }

    public function onOpen(ConnectionInterface $conn) {
        $querystring = $conn->httpRequest->getUri()->getQuery();
        parse_str($querystring, $queryParams);

        $username = $queryParams['username'] ?? 'Anonymous';
        $room = $queryParams['room'] ?? 'General';

        $conn->username = $username;
        $conn->room = $room;

        $this->clients->attach($conn);

        if (!isset($this->rooms[$room])) {
            $this->rooms[$room] = [];
        }
        $this->rooms[$room][$conn->resourceId] = $username;

        echo "New connection! ({$conn->resourceId}), Username: {$username}, Room: {$room}\n";

        $this->sendUsersInRoom($room);
    }

    public function onMessage(ConnectionInterface $from, $msg) {
        $data = json_decode($msg, true);

        if ($data['action'] === 'checkMessages') {
            $this->sendSavedMessagesForRoom($from, $data['room']);
        } elseif ($data['action'] === 'typing' || $data['action'] === 'stopTyping') {
            foreach ($this->clients as $client) {
                if ($from !== $client && $client->room === $data['room']) {
                    $client->send($msg);
                }
            }
        } else {
            $messageWithConnectionInfo = [
                'username' => $from->username,
                'room' => $from->room,
                'message' => $data['message'],
                'timestamp' => date('Y-m-d H:i:s'),
                'action' => 'sendMessage'
            ];

            $this->saveMessageToDatabase($messageWithConnectionInfo);

            $messageToSend = json_encode($messageWithConnectionInfo);

            foreach ($this->clients as $client) {
                if ($from !== $client && $client->room === $data['room']) {
                    $client->send($messageToSend);
                }
            }
        }
    }

    public function onClose(ConnectionInterface $conn) {
        $this->clients->detach($conn);
        $room = $conn->room;
        unset($this->rooms[$room][$conn->resourceId]);

        if (empty($this->rooms[$room])) {
            unset($this->rooms[$room]);
        }

        echo "Connection {$conn->resourceId} (Username: {$conn->username}, Room: {$conn->room}) has disconnected\n";

        $this->sendUsersInRoom($room);
    }

    public function onError(ConnectionInterface $conn, \Exception $e) {
        echo "An error has occurred: {$e->getMessage()}\n";
        $conn->close();
    }

    protected function saveMessageToDatabase($msgData) {
        $stmt = $this->db->prepare("INSERT INTO messages (username, room, message, timestamp) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("ssss", $msgData['username'], $msgData['room'], $msgData['message'], $msgData['timestamp']);
        $stmt->execute();
        $stmt->close();
    }

    protected function sendSavedMessagesForRoom(ConnectionInterface $conn, $room) {
        $stmt = $this->db->prepare("SELECT username, message, timestamp FROM messages WHERE room = ? ORDER BY timestamp ASC");
        $stmt->bind_param("s", $room);
        $stmt->execute();
        $result = $stmt->get_result();

        while ($row = $result->fetch_assoc()) {
            $messageData = [
                'username' => $row['username'],
                'room' => $room,
                'message' => $row['message'],
                'timestamp' => $row['timestamp'],
                'action' => 'sendMessage'
            ];
            $conn->send(json_encode($messageData));
        }

        $stmt->close();
    }

    protected function sendUsersInRoom($room) {
        if (isset($this->rooms[$room])) {
            $users = array_values($this->rooms[$room]);
            $data = [
                'action' => 'updateUsers',
                'users' => $users
            ];

            foreach ($this->clients as $client) {
                if ($client->room === $room) {
                    $client->send(json_encode($data));
                }
            }
        }
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

$server->run();