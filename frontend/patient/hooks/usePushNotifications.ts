import { useState, useEffect } from "react";
import { storePushSubscription, deletePushSubscription } from "@/api/notifications";

const VAPID_PUBLIC_KEY = "BCIR4YNdKorIo49wwlh6zrXIzGpt0rzy1wDJ-b0NgMvVwxmFEwDPKwpVpifJS96BvUXIXtgIp-o0jfwgZuqrobg";

function urlBase64ToUint8Array(base64String: string) {
    const padding = "=".repeat((4 - (base64String.length % 4)) % 4);
    const base64 = (base64String + padding).replace(/\-/g, "+").replace(/_/g, "/");
    const rawData = window.atob(base64);
    const outputArray = new Uint8Array(rawData.length);
    for (let i = 0; i < rawData.length; ++i) {
        outputArray[i] = rawData.charCodeAt(i);
    }
    return outputArray;
}

export function usePushNotifications() {
    const [permission, setPermission] = useState<NotificationPermission | null>(null);
    const [subscription, setSubscription] = useState<PushSubscription | null>(null);
    const [loading, setLoading] = useState(false);

    useEffect(() => {
        if (typeof window !== "undefined" && "Notification" in window) {
            setPermission(Notification.permission);

            if ("serviceWorker" in navigator) {
                const isDev = process.env.NODE_ENV === "development";
                const swPath = isDev ? "/sw-dev.js" : "/sw.js";

                if (isDev) {
                    navigator.serviceWorker.register(swPath).then((registration) => {
                        registration.pushManager.getSubscription().then((existingSubscription) => {
                            setSubscription(existingSubscription);
                        });
                    }).catch(err => {
                        console.error("Failed to register Service Worker in dev:", err);
                    });
                } else {
                    navigator.serviceWorker.ready.then((registration) => {
                        registration.pushManager.getSubscription().then((existingSubscription) => {
                            setSubscription(existingSubscription);
                        });
                    });
                }
            }
        }
    }, []);

    const subscribeToPush = async () => {
        if (typeof window === "undefined" || !("serviceWorker" in navigator) || !("PushManager" in window)) {
            console.warn("Push notifications are not supported in this browser.");
            return null;
        }

        setLoading(true);
        try {
            const perm = await Notification.requestPermission();
            setPermission(perm);

            if (perm !== "granted") {
                throw new Error("Notification permission denied");
            }

            const registration = await navigator.serviceWorker.ready;

            const sub = await registration.pushManager.subscribe({
                userVisibleOnly: true,
                applicationServerKey: urlBase64ToUint8Array(VAPID_PUBLIC_KEY),
            });

            setSubscription(sub);

            await storePushSubscription(sub);
            console.log("Registered Push Subscription on Backend successfully!");

            return sub;
        } catch (error) {
            console.error("Failed to subscribe to WebPush notifications:", error);
            throw error;
        } finally {
            setLoading(false);
        }
    };

    const unsubscribeFromPush = async () => {
        if (!subscription) return;
        setLoading(true);
        try {
            if (subscription.endpoint) {
                await deletePushSubscription(subscription.endpoint);
            }
            await subscription.unsubscribe();
            setSubscription(null);
            console.log("Unsubscribed from WebPush successfully!");
        } catch (error) {
            console.error("Failed to unsubscribe:", error);
        } finally {
            setLoading(false);
        }
    };

    return {
        permission,
        subscription,
        loading,
        subscribeToPush,
        unsubscribeFromPush,
        isSupported: typeof window !== "undefined" && "serviceWorker" in navigator && "PushManager" in window,
    };
}
