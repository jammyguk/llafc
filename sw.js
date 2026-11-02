// Keeps the app opening instantly and working offline; fresh data always wins when online.
const C="lionstext-v1";
self.addEventListener("install",e=>{self.skipWaiting();
  e.waitUntil(caches.open(C).then(c=>c.addAll(["./","index.html","crest.png","icon-192.png"]).catch(()=>{})));});
self.addEventListener("activate",e=>{e.waitUntil(caches.keys().then(k=>Promise.all(k.filter(x=>x!==C).map(x=>caches.delete(x)))).then(()=>self.clients.claim()));});
self.addEventListener("fetch",e=>{
  const r=e.request;if(r.method!=="GET"||new URL(r.url).origin!==location.origin)return;
  e.respondWith(fetch(r).then(res=>{const cp=res.clone();caches.open(C).then(c=>c.put(r,cp));return res;}).catch(()=>caches.match(r)));});
