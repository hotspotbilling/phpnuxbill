self.addEventListener('push', event => {
    const data = event.data.json();
    self.registration.showNotification(data.title, {
      body: data.body,
      icon: '/ui/ui/images/logo.png',
      badge: '/ui/ui/images/logo.png',
      vibrate: [200, 100, 200],
      requireInteraction: true,
      actions: [{
        action: 'view',
        title: 'View'
      }]
    });
  });

self.addEventListener('notificationclick', event => {
    event.notification.close();
    event.waitUntil(
        clients.openWindow('/')
    );
});
  