import Echo from 'laravel-echo'
import Pusher from 'pusher-js'

window.Pusher = Pusher

window.Echo = new Echo({
    broadcaster: 'pusher',  // Ensure 'pusher' is set correctly as the broadcaster
    key: import.meta.env.VITE_PUSHER_APP_KEY,
    cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER, // ← Make sure this is defined
    forceTLS: true
});
