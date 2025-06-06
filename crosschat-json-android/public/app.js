let username, room, userId, socket;
let typingTimeout;
const TYPING_DELAY = 3000;

document.getElementById('saveUsernameBtn').addEventListener('click', () => {
  username = document.getElementById('usernameInput').value.trim();
  room = document.getElementById('roomInput').value.trim();
  userId = document.getElementById('userIdInput').value.trim();

  if (!username || !room || !userId) {
    alert("Please enter all fields.");
    return;
  }

  socket = new WebSocket(`ws://localhost:8080?username=${encodeURIComponent(username)}&room=${encodeURIComponent(room)}&userId=${encodeURIComponent(userId)}`);

  socket.addEventListener('open', () => {
    document.getElementById('messageInput').disabled = false;
    document.getElementById('sendBtn').disabled = false;
  });

  socket.addEventListener('message', (event) => {
    const data = JSON.parse(event.data);

    switch (data.action) {
      case 'sendMessage':
        displayMessage(data);
        socket.send(JSON.stringify({ action: 'messageReceived', messageId: data.messageId }));
        break;
      case 'updateUsers':
        document.getElementById('users').textContent = data.users.join(', ');
        break;
      case 'typing':
        document.getElementById('typingIndicator').textContent = `${data.username} is typing...`;
        break;
      case 'stopTyping':
        document.getElementById('typingIndicator').textContent = '';
        break;
      case 'updateMessageStatus':
        console.log(`Message ${data.messageId} status: ${data.status}`);
        break;
    }
  });
});

document.getElementById('sendBtn').addEventListener('click', () => {
  const input = document.getElementById('messageInput');
  const message = input.value.trim();
  if (!message) return;

  const timestamp = new Date().toISOString();

  socket.send(JSON.stringify({
    action: 'sendMessage',
    username, room, userId,
    message, timestamp
  }));

  input.value = '';
});

document.getElementById('messageInput').addEventListener('input', () => {
  if (socket?.readyState === WebSocket.OPEN) {
    socket.send(JSON.stringify({ action: 'typing' }));
    clearTimeout(typingTimeout);
    typingTimeout = setTimeout(() => {
      socket.send(JSON.stringify({ action: 'stopTyping' }));
    }, TYPING_DELAY);
  }
});

function displayMessage(data) {
  const container = document.getElementById('messages');
  const msg = document.createElement('div');
  const time = new Date(data.timestamp).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });

  msg.classList.add('message', data.userId === userId ? 'you' : 'other');
  msg.innerHTML = `<div class="meta"><strong>${data.username}</strong> • ${time}</div>${data.message}`;

  container.appendChild(msg);
  container.scrollTop = container.scrollHeight;
}
