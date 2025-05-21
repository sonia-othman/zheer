import Echo from 'laravel-echo'
import Pusher from 'pusher-js'

window.Pusher = Pusher

window.Echo = new Echo({
    broadcaster: 'reverb',  // Ensure 'reverb' is set correctly as the broadcaster
    key: import.meta.env.VITE_REVERB_APP_KEY,  // Reverb API key
    wsHost: import.meta.env.VITE_REVERB_HOST,  // Reverb WebSocket server host
    wsPort: import.meta.env.VITE_REVERB_PORT ?? 8080,  // Default WebSocket port (change if necessary)
    wssPort: import.meta.env.VITE_REVERB_PORT ?? 443,  // Default WebSocket Secure port (for https)
    forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https',  // Force TLS if using https
    enabledTransports: ['ws', 'wss'],  // Enable both ws and wss transports
});
