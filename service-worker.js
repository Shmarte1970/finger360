const CACHE_NAME = "finger360-cache-v1";
const urlsToCache = [
  "/",
  "/index.php",
  "/dashboard.php",
  "/css/dashboard.css",
  "/js/dashboard.js",
  "/js/main.js",
  "/assets/img/placeholder.jpg",
  "/manifest.json",
];

self.addEventListener("install", function (event) {
  event.waitUntil(
    caches.open(CACHE_NAME).then(function (cache) {
      return cache.addAll(urlsToCache);
    })
  );
});

self.addEventListener("fetch", function (event) {
  event.respondWith(
    caches.match(event.request).then(function (response) {
      return response || fetch(event.request);
    })
  );
});
