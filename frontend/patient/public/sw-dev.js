self.addEventListener("push", (event) => {
    if (!event.data) return;

    try {
        const data = event.data.json();
        const options = {
            body: data.body || data.message || "",
            icon: data.icon || "/icons/icon-192x192.png",
            badge: data.badge || "/icons/icon-192x192.png",
            data: data.data || data.payload || {},
        };

        event.waitUntil(
            self.registration.showNotification(data.title || "New Notification", options)
        );
    } catch {
        const text = event.data.text();
        event.waitUntil(
            self.registration.showNotification("Notification", {
                body: text,
                icon: "/icons/icon-192x192.png",
            })
        );
    }
});

self.addEventListener("notificationclick", (event) => {
    event.notification.close();
    event.waitUntil(
        self.clients.matchAll({ type: "window", includeUncontrolled: true }).then((clientList) => {
            if (clientList.length > 0) {
                let client = clientList[0];
                for (let i = 0; i < clientList.length; i++) {
                    if (clientList[i].focused) {
                        client = clientList[i];
                        break;
                    }
                }
                return client.focus();
            }
            return self.clients.openWindow("/notifications");
        })
    );
});
