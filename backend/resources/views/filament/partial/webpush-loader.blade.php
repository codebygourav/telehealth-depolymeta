@auth
<script>
    window.addEventListener('load', function() {
        if (!('serviceWorker' in navigator) || !('PushManager' in window)) {
            console.warn('Web Push Notifications are not supported in this browser.');
            return;
        }

        // Register Service Worker
        navigator.serviceWorker.register('/sw.js')
            .then(function(registration) {
                console.log('Service Worker registered with scope:', registration.scope);
                
                // Once SW is ready, negotiate subscription
                return navigator.serviceWorker.ready;
            })
            .then(function(registration) {
                // Get subscription
                return registration.pushManager.getSubscription()
                    .then(async function(subscription) {
                        if (subscription) {
                            // Already subscribed. Check if we need to send it to the server
                            await sendSubscriptionToServer(subscription);
                            return subscription;
                        }

                        // Request permission
                        const permission = await Notification.requestPermission();
                        if (permission !== 'granted') {
                            console.warn('Notification permission not granted.');
                            return null;
                        }

                        // Subscribe
                        const publicKey = "{{ config('webpush.vapid.public_key') }}";
                        if (!publicKey) {
                            console.error('VAPID public key is missing.');
                            return null;
                        }

                        const convertedVapidKey = urlBase64ToUint8Array(publicKey);

                        return registration.pushManager.subscribe({
                            userVisibleOnly: true,
                            applicationServerKey: convertedVapidKey
                        });
                    });
            })
            .then(function(subscription) {
                if (subscription) {
                    sendSubscriptionToServer(subscription);
                }
            })
            .catch(function(error) {
                console.error('Service Worker registration or subscription failed:', error);
            });

        function urlBase64ToUint8Array(base64String) {
            const padding = '='.repeat((4 - base64String.length % 4) % 4);
            const base64 = (base64String + padding)
                .replace(/\-/g, '+')
                .replace(/_/g, '/');

            const rawData = window.atob(base64);
            const outputArray = new Uint8Array(rawData.length);

            for (let i = 0; i < rawData.length; ++i) {
                outputArray[i] = rawData.charCodeAt(i);
            }
            return outputArray;
        }

        function sendSubscriptionToServer(subscription) {
            const key = subscription.getKey ? subscription.getKey('p256dh') : null;
            const token = subscription.getKey ? subscription.getKey('auth') : null;
            const contentEncoding = (PushManager.supportedContentEncodings || ['aesgcm'])[0];

            return fetch("{{ route('admin.webpush.subscribe') }}", {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': "{{ csrf_token() }}",
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    endpoint: subscription.endpoint,
                    keys: {
                        p256dh: key ? btoa(String.fromCharCode.apply(null, new Uint8Array(key))) : null,
                        auth: token ? btoa(String.fromCharCode.apply(null, new Uint8Array(token))) : null
                    },
                    content_encoding: contentEncoding
                })
            })
            .then(response => response.json())
            .then(data => {
                console.log('Push subscription synced with server:', data);
            })
            .catch(err => {
                console.error('Error syncing push subscription with server:', err);
            });
        }
    });
</script>
@endauth
