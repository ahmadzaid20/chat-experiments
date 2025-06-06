<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Real-Time Chat</title>
    <link rel="stylesheet" href="style.css" />
</head>
<body>
    <h1>Real-Time Chat</h1>

    <input type="text" id="usernameInput" placeholder="Enter your name" />
    <input type="text" id="roomInput" placeholder="Enter room name" />
    <button id="saveUsernameBtn">Join Chat</button>

    <ul id="users"></ul>

    <div id="messages" data-last-date=""></div>
    <div id="typingIndicator"></div>

    <input type="text" id="messageInput" placeholder="Enter your message" disabled />
    <button id="sendBtn" disabled>Send</button>

    <script src="app.js"></script>
</body>
</html>
