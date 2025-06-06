let username = null;
let room = null;
let socket = null;
let typingTimeout;
const TYPING_DELAY = 3000;

const usernameInput = document.getElementById('usernameInput');
const roomInput = document.getElementById('roomInput');
const messageInput = document.getElementById('messageInput');
const sendBtn = document.getElementById('sendBtn');
const saveUsernameBtn = document.getElementById('saveUsernameBtn');
const messagesDiv = document.getElementById('messages');
const typingIndicator = document.getElementById('typingIndicator');
const usersList = document.getElementById('users');

saveUsernameBtn.addEventListener('click', () => {
    const name = usernameInput.value.trim();
    const roomName = roomInput.value.trim();

    if (!name || !roomName) {
        alert("Please enter a valid username and room name.");
        return;
    }

    username = name;
    room = roomName;

    socket = new WebSocket(`ws://${location.hostname}:8080?username=${encodeURIComponent(username)}&room=${encodeURIComponent(room)}`);

    socket.addEventListener('open', () => {
        console.log(`Connected as ${username} in room ${room}`);
        socket.send(JSON.stringify({ action: 'checkMessages', room }));
    });

    socket.addEventListener('message', event => {
        const data = JSON.parse(event.data);

        if (data.action === 'typing') {
            typingIndicator.textContent = `${data.username} is typing...`;
            typingIndicator.style.visibility = 'visible';
        } else if (data.action === 'stopTyping') {
            typingIndicator.textContent = '';
            typingIndicator.style.visibility = 'hidden';
        } else if (data.action === 'updateUsers') {
            usersList.innerHTML = '';
            data.users.forEach(user => {
                const li = document.createElement('li');
                li.textContent = user;
                usersList.appendChild(li);
            });
        } else {
            displayMessage(data);
        }
    });

    messageInput.disabled = false;
    sendBtn.disabled = false;
});

messageInput.addEventListener('input', () => {
    if (socket && socket.readyState === WebSocket.OPEN) {
        socket.send(JSON.stringify({
            username,
            room,
            action: 'typing'
        }));

        clearTimeout(typingTimeout);
        typingTimeout = setTimeout(() => {
            socket.send(JSON.stringify({
                username,
                room,
                action: 'stopTyping'
            }));
        }, TYPING_DELAY);
    }
});

sendBtn.addEventListener('click', () => {
    const messageText = messageInput.value.trim();
    if (!messageText) return;

    const timestamp = new Date().toISOString();

    const messageData = {
        username,
        room,
        message: messageText,
        timestamp,
        action: 'sendMessage'
    };

    socket.send(JSON.stringify(messageData));
    displayMessage({ ...messageData, username: 'You' }, true);

    messageInput.value = '';
    typingIndicator.textContent = '';
});

function displayMessage(data, isLocal = false) {
    const msg = document.createElement('div');
    const time = new Date(data.timestamp).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    const date = new Date(data.timestamp).toLocaleDateString();
    const lastDate = messagesDiv.getAttribute('data-last-date');

    if (lastDate !== date) {
        const divider = document.createElement('div');
        divider.classList.add('date-divider');
        divider.innerHTML = `<span>${date}</span>`;
        messagesDiv.appendChild(divider);
        messagesDiv.setAttribute('data-last-date', date);
    }

    msg.classList.add('message', data.username === 'You' || isLocal ? 'you' : 'other');
    msg.innerHTML = `<div class="meta"><strong>${data.username}</strong> : ${time}</div>${data.message}`;
    messagesDiv.appendChild(msg);
    messagesDiv.scrollTop = messagesDiv.scrollHeight;
}
