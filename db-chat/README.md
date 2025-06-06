# 🗃️ db-chat – PHP WebSocket Chat with MySQL Storage

A real-time multi-room chat application built with **PHP** and **Ratchet WebSocket**, storing chat messages in a **MySQL** database.

---

## 🚀 Features

- 🔌 Real-time chat using WebSocket (Ratchet)
- 🏠 Multi-room support
- ✍️ Typing indicators
- 🗄️ MySQL message storage
- 🖥️ Simple and responsive UI (HTML/CSS/JS)

---

## 📦 Requirements

- PHP 7.4 or higher
- Composer
- MySQL server
- XAMPP (or similar local server)

---

## 🗂️ Database Schema

Create the `messages` table by importing this SQL:

```
db/schema.sql
```

```sql
CREATE TABLE messages (
  id INT AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(255) NOT NULL,
  room VARCHAR(255) NOT NULL,
  message TEXT NOT NULL,
  timestamp DATETIME NOT NULL
);
```

---

## 🛠️ Installation

1. Install dependencies:
   ```bash
   composer install
   ```

2. Start the WebSocket server:
   ```bash
   php bin/server.php
   ```

3. Open the chat interface in your browser:
   ```
   http://localhost/db-chat/public/
   ```

---

## 🧑‍💻 Author

**Ahmad Zaid**  
📧 [ahmad.m.dsalman20@gmail.com](mailto:ahmad.m.dsalman20@gmail.com)  
💼 [GitHub](https://github.com/ahmadzaid20) • [LinkedIn](https://linkedin.com/in/ahmad-moh-zaid)

---

## 📃 License

This project is open-source and available under the [MIT License](LICENSE).