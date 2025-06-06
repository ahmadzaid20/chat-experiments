package com.devpal.crosschat;

import okhttp3.OkHttpClient;
import okhttp3.Request;
import okhttp3.WebSocket;
import okhttp3.WebSocketListener;
import okhttp3.Response;
import okio.ByteString;

import android.os.Handler;
import android.os.Looper;
import android.util.Log;

import androidx.annotation.NonNull;

public class WebSocketClient {

    private WebSocket webSocket;
    private OkHttpClient client;

    public void startWebSocket() {
        client = new OkHttpClient();

        Request request = new Request.Builder()
                .url("ws://http://192.168.0.6/websocket")
                .build();

        webSocket = client.newWebSocket(request, new WebSocketListener() {

            @Override
            public void onOpen(@NonNull WebSocket webSocket, @NonNull Response response) {
                Log.d("WebSocket", "Connection opened");
                webSocket.send("Hello from Android!");
            }

            @Override
            public void onMessage(@NonNull WebSocket webSocket, @NonNull String text) {
                Log.d("WebSocket", "Received message: " + text);
            }

            @Override
            public void onMessage(@NonNull WebSocket webSocket, @NonNull ByteString bytes) {
                Log.d("WebSocket", "Received byte message: " + bytes.hex());
            }

            @Override
            public void onClosing(@NonNull WebSocket webSocket, int code, @NonNull String reason) {
                Log.d("WebSocket", "Closing connection: " + reason);
                webSocket.close(1000, null);
            }

            @Override
            public void onFailure(@NonNull WebSocket webSocket, @NonNull Throwable t, Response response) {
                Log.e("WebSocket", "Connection error: " + t.getMessage());

                new Handler(Looper.getMainLooper()).postDelayed(() -> {
                    Log.d("WebSocket", "Reconnecting WebSocket...");
                    startWebSocket();
                }, 5000);
            }
        });
    }

    public void sendMessage(String message) {
        if (webSocket != null) {
            webSocket.send(message);
        }
    }

    public void closeWebSocket() {
        if (webSocket != null) {
            webSocket.close(1000, "Client closing connection");
        }
        if (client != null) {
            client.dispatcher().executorService().shutdown();
        }
    }
}
