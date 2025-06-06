# 💬 Chat Experiments by Ahmad Zaid

This repository contains a set of three chat applications built with PHP WebSocket (Ratchet) showcasing different storage mechanisms and integration methods. Each folder contains a complete, working real-time chat system.

---

## 📁 Projects Overview

### 1. `flatfile-chat`
- 💾 **Storage**: Text file-based
- 💬 **Description**: Real-time chat with messages saved as `.txt` per room.
- ✅ Features: Room-based chat, typing indicator, user list
- 📦 Lightweight, no database needed.

### 2. `db-chat`
- 🗃️ **Storage**: MySQL Database
- 📊 **Description**: Real-time chat where messages are stored in a MySQL table.
- ✅ Features: Room support, persistent storage, typing indicator
- 🔌 Ideal for scalable backend systems.

### 3. `crosschat-json-android`
- 🌐 **Storage**: JSON files (one per room)
- 🤝 **Integration**: Can connect to a native Android client or Web interface
- ✅ Features: Message delivery status (sent, received, seen), multi-device chat
- 📱 Designed for web + Android interoperability

---

## ⚙️ Requirements

- PHP 7.4+
- Composer
- XAMPP or PHP CLI
- Optional: MySQL (for db-chat)
- Optional: Android Studio (for crosschat-json-android)

---

## 🔧 How to Use

Each folder contains a `README.md` with full setup instructions.  
Clone the repo and navigate to the folder you're interested in:

```bash
cd flatfile-chat       # or db-chat, or crosschat-json-android
composer install       # if required
php bin/server.php     # or php server.php depending on structure
```

Open the `public/index.html` in your browser or run the Android app if applicable.

---

## 🧑‍💻 Author

**Ahmad Zaid**  
📧 [ahmad.m.dsalman20@gmail.com](mailto:ahmad.m.dsalman20@gmail.com)  
💼 [GitHub](https://github.com/ahmadzaid20) • [LinkedIn](https://linkedin.com/in/ahmad-moh-zaid)