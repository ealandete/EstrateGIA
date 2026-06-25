// EstrateGIA Service Worker v2.1
const CACHE = 'estrategia-v2.1';
const ASSETS = ['/','/assets/css/bootstrap.min.css','/assets/css/fontawesome.min.css','/assets/css/app.css?v=22','/assets/js/bootstrap.bundle.min.js','/assets/js/chart.min.js','/manifest.json'];

self.addEventListener('install', e => {
  e.waitUntil(caches.open(CACHE).then(c => c.addAll(ASSETS)));
});

self.addEventListener('fetch', e => {
  e.respondWith(
    caches.match(e.request).then(r => r || fetch(e.request).then(res => {
      if (res.ok) { const clone = res.clone(); caches.open(CACHE).then(c => c.put(e.request, clone)); }
      return res;
    }).catch(() => caches.match('/')))
  );
});

self.addEventListener('activate', e => {
  e.waitUntil(caches.keys().then(keys => Promise.all(keys.filter(k => k !== CACHE).map(k => caches.delete(k)))));
});
