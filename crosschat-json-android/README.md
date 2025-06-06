# 🔄 CrossChat JSON – WebSocket Chat with JSON Storage and Android Sync

A real-time multi-room chat application built with **PHP**, **Ratchet**, and **WebSockets**. Messages are stored in `.json` files per room, with full support for Android ↔ Web communication and message status tracking (sent, received, seen).

---

## 🚀 Features

- 🔌 Real-time messaging using WebSockets
- 🏠 Multiple room support
- ✍️ Typing indicator
- 🔁 Sync between Android ↔ Web clients
- 💾 Messages stored in JSON files (`rooms/`)
- 👀 Tracks message status: sent, received, seen
- 🖥️ Clean HTML/CSS/JS frontend

---

## 📁 Folder Structure

```
crosschat-json-android/
├── public/
│   ├── index.html        # Web UI
│   ├── styles.css        # CSS styles
│   └── app.js            # WebSocket logic (frontend)
│
├── server/
│   └── server.php        # WebSocket server logic (Ratchet)
│
├── rooms/                # JSON files per chat room
│   └── room_{roomId}.json
│
├── vendor/               # Composer dependencies
├── composer.json
├── composer.lock
├── LICENSE
└── README.md
```

---

## 🛠 Installation

1. Install Composer dependencies:
   ```bash
   composer install
   ```

2. Start the WebSocket server:
   ```bash
   php server/server.php
   ```

3. Open the frontend in your browser:
   ```
   http://localhost/crosschat-json-android/public/
   ```

---

## 🧑‍💻 Author

**Ahmad Zaid**  
📧 [ahmad.m.dsalman20@gmail.com](mailto:ahmad.m.dsalman20@gmail.com)  
💼 [GitHub](https://github.com/ahmadzaid20) • [LinkedIn](https://linkedin.com/in/ahmad-moh-zaid)

---

## 📃 License

This project is licensed under the [MIT License](LICENSE).