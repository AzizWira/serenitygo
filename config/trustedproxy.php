<?php

return [
    // Laravel TrustProxies reads this at request-handling time. This avoids
    // accessing the container's config service while bootstrap/app.php runs.
    // Local ngrok's agent typically connects from 127.0.0.1 or ::1.
    // Limit this list to reverse proxies you actually control.
    'proxies' => env('TRUSTED_PROXIES', '127.0.0.1,::1'),
];
