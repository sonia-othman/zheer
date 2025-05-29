const Ziggy = { "url": "http:\/\/localhost\/165.232.114.163", "port": null, "defaults": {}, "routes": { "sanctum.csrf-cookie": { "uri": "sanctum\/csrf-cookie", "methods": ["GET", "HEAD"] }, "home": { "uri": "\/", "methods": ["GET", "HEAD"] }, "notifications": { "uri": "notifications", "methods": ["GET", "HEAD"] }, "dashboard": { "uri": "dashboard", "methods": ["GET", "HEAD"] }, "storage.local": { "uri": "storage\/{path}", "methods": ["GET", "HEAD"], "wheres": { "path": ".*" }, "parameters": ["path"] } } };
if (typeof window !== 'undefined' && typeof window.Ziggy !== 'undefined') {
    Object.assign(Ziggy.routes, window.Ziggy.routes);
}
export { Ziggy };
