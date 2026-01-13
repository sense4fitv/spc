// Minimal Service Worker - just for PWA installation requirement
// No caching strategies needed since we don't want offline functionality

self.addEventListener('install', function(event) {
    // Skip waiting - activate immediately
    self.skipWaiting();
});

self.addEventListener('activate', function(event) {
    // Take control immediately
    event.waitUntil(self.clients.claim());
});

// No fetch event handler = all requests go to network
// This ensures the app always fetches fresh data

