package com.devpal.crosschat;

import android.os.Bundle;
import android.text.Editable;
import android.text.TextWatcher;
import android.view.View;
import android.view.ViewTreeObserver;
import android.widget.Button;
import android.widget.EditText;
import android.widget.LinearLayout;
import android.widget.ScrollView;
import android.widget.TextView;

import androidx.appcompat.app.AppCompatActivity;

import org.json.JSONArray;
import org.json.JSONException;
import org.json.JSONObject;

import java.text.SimpleDateFormat;
import java.util.Date;
import java.util.Locale;

import okhttp3.OkHttpClient;
import okhttp3.Request;
import okhttp3.WebSocket;
import okhttp3.WebSocketListener;

public class MainActivity extends AppCompatActivity {

    private Button btnSave, btnSend;
    private EditText edtUsername, edtRoom, edtMessage, edtUserId;
    private TextView txtUsers, typingIndicator;
    private LinearLayout layoutMessages;
    private ScrollView scrollViewMessages;
    private OkHttpClient client;
    private WebSocket webSocket;
    private String username, room, userId;
    private boolean isTyping = false;
    private static final int TYPING_DELAY = 3000;
    private Runnable typingRunnable;
    private String urlLink = "192.168.0.6";

    @Override
    protected void onCreate(Bundle savedInstanceState) {
        super.onCreate(savedInstanceState);
        setContentView(R.layout.activity_main);

        // Binding components from XML
        btnSave = findViewById(R.id.btnSave);
        btnSend = findViewById(R.id.btnSend);
        edtUsername = findViewById(R.id.edtUsername);
        edtRoom = findViewById(R.id.edtRoom);
        edtMessage = findViewById(R.id.edtMessage);
        edtUserId = findViewById(R.id.edtUserId);  // Bind the User ID's EditText
        txtUsers = findViewById(R.id.txtUsers);
        typingIndicator = findViewById(R.id.typingIndicator);
        layoutMessages = findViewById(R.id.layoutMessages);
        scrollViewMessages = findViewById(R.id.scrollViewMessages);

        client = new OkHttpClient();

        // Set up a monitor to display the keyboard and trigger messages
        setupKeyboardListener();

        // Save button to save the username, user ID, and room
        btnSave.setOnClickListener(new View.OnClickListener() {
            @Override
            public void onClick(View v) {
                username = edtUsername.getText().toString();  // Get Username
                room = edtRoom.getText().toString();
                userId = edtUserId.getText().toString();  // Get User ID

                if (!username.isEmpty() && !room.isEmpty() && !userId.isEmpty()) {
                    startWebSocket();  // Start a WebSocket connection after saving the username and user ID
                } else {
                    addMessage("Please enter username, room, and User ID", true);
                }
            }
        });

        // Message Entry - View Writing Status
        edtMessage.addTextChangedListener(new TextWatcher() {
            @Override
            public void beforeTextChanged(CharSequence s, int start, int count, int after) {}

            @Override
            public void onTextChanged(CharSequence s, int start, int before, int count) {
                if (webSocket != null && !isTyping) {
                    sendTypingStatus(true);
                }
            }

            @Override
            public void afterTextChanged(Editable s) {
                if (webSocket != null) {
                    edtMessage.removeCallbacks(typingRunnable);
                    typingRunnable = () -> sendTypingStatus(false);
                    edtMessage.postDelayed(typingRunnable, TYPING_DELAY);
                }
            }
        });

        // Sending messages when the send button is pressed
        btnSend.setOnClickListener(new View.OnClickListener() {
            @Override
            public void onClick(View v) {
                String message = edtMessage.getText().toString();

                if (!message.isEmpty()) {
                    sendMessageToServer(message);
                    addMessage("You: " + message, true);  // Display the sent message on the screen
                    edtMessage.setText("");
                }
            }
        });
    }

    // Set up a monitor to trigger messages when the keyboard appears
    private void setupKeyboardListener() {
        scrollViewMessages.getViewTreeObserver().addOnGlobalLayoutListener(new ViewTreeObserver.OnGlobalLayoutListener() {
            @Override
            public void onGlobalLayout() {
                // Calculate the screen space when the keyboard appears
                int heightDiff = scrollViewMessages.getRootView().getHeight() - scrollViewMessages.getHeight();
                if (heightDiff > 200) { // Assume the keyboard is visible when the screen is shrunk this much
                    scrollViewMessages.post(() -> scrollViewMessages.fullScroll(View.FOCUS_DOWN));
                }
            }
        });
    }

    // Initiate a WebSocket connection
    private void startWebSocket() {
        // Build a WebSocket URL with query string data
        String wsUrl = "ws://" + urlLink + ":8080"
                + "?username=" + username
                + "&room=" + room
                + "&userId=" + userId;

        Request request = new Request.Builder().url(wsUrl).build();
        webSocket = client.newWebSocket(request, new WebSocketListener() {

            @Override
            public void onOpen(WebSocket webSocket, okhttp3.Response response) {
                runOnUiThread(() -> {
                    addMessage("Connected to WebSocket as " + username + " in room " + room, true);
                    edtMessage.setEnabled(true);
                    btnSend.setEnabled(true);
                });
            }

            @Override
            public void onMessage(WebSocket webSocket, String text) {
                runOnUiThread(() -> handleMessageFromServer(text));
            }

            @Override
            public void onFailure(WebSocket webSocket, Throwable t, okhttp3.Response response) {
                runOnUiThread(() -> addMessage("Error: " + t.getMessage(), true));
            }
        });

        client.dispatcher().executorService().shutdown();
    }

    // Send messages to the server with User ID
    private void sendMessageToServer(String message) {
        try {
            JSONObject messageData = new JSONObject();
            messageData.put("action", "sendMessage");
            messageData.put("username", username);
            messageData.put("room", room);
            messageData.put("userId", userId);  // Send User ID with the message
            messageData.put("message", message);
            webSocket.send(messageData.toString());
        } catch (JSONException e) {
            e.printStackTrace();
        }
    }

    // Send write status with User ID
    private void sendTypingStatus(boolean isTyping) {
        try {
            JSONObject typingStatus = new JSONObject();
            typingStatus.put("action", isTyping ? "typing" : "stopTyping");
            typingStatus.put("username", username);
            typingStatus.put("room", room);
            typingStatus.put("userId", userId);  // Send User ID with write status
            webSocket.send(typingStatus.toString());
            this.isTyping = isTyping;
        } catch (JSONException e) {
            e.printStackTrace();
        }
    }

    // Dealing with messages from the server
    private void handleMessageFromServer(String text) {
        try {
            JSONObject receivedData = new JSONObject(text);
            String action = receivedData.getString("action");

            if (action.equals("sendMessage")) {
                String receivedMessage = receivedData.getString("message");
                String sender = receivedData.getString("username");

                // Display the sent message over time
                String formattedMessage = sender + ": " + receivedMessage + "\n" +
                        new SimpleDateFormat("hh:mm a", Locale.getDefault()).format(new Date());

                addMessage(formattedMessage, false);

            } else if (action.equals("updateUsers")) {
                JSONArray usersArray = receivedData.getJSONArray("users");
                StringBuilder usersList = new StringBuilder("Users in room:\n");
                for (int i = 0; i < usersArray.length(); i++) {
                    usersList.append(usersArray.getString(i)).append("\n");
                }
                txtUsers.setText(usersList.toString());

            } else if (action.equals("typing")) {
                String typingUser = receivedData.getString("username");
                typingIndicator.setText(typingUser + " is typing...");
                typingIndicator.setVisibility(View.VISIBLE);

            } else if (action.equals("stopTyping")) {
                typingIndicator.setVisibility(View.GONE);
            }
        } catch (JSONException e) {
            e.printStackTrace();
        }
    }

    // Add a message to the user interface
    private void addMessage(String message, boolean isYou) {
        TextView textView = new TextView(MainActivity.this);
        textView.setText(message);
        textView.setBackgroundResource(isYou ? R.drawable.message_you : R.drawable.message_other);

        // Adding margins to format messages
        LinearLayout.LayoutParams params = new LinearLayout.LayoutParams(
                LinearLayout.LayoutParams.WRAP_CONTENT, LinearLayout.LayoutParams.WRAP_CONTENT);
        params.setMargins(0, 0, 0, 20);  // Adding space between messages
        textView.setLayoutParams(params);

        layoutMessages.addView(textView);

        // Scroll to the last message
        scrollViewMessages.post(() -> scrollViewMessages.fullScroll(View.FOCUS_DOWN));
    }
}
