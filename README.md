# SignalGrid UI Prototype

A responsive, frontend-only concept for a campus Wi-Fi monitoring and heat-map platform.

## Run locally

Open `index.html` directly, or run a local static server:

```powershell
python -m http.server 8080
```

Then open `http://localhost:8080`.

## Laravel integration path

Move `index.html` into `resources/views/dashboard.blade.php`, move CSS/JS into `resources/css/app.css` and `resources/js/app.js`, then replace the static arrays with Laravel data. Laravel's Vite directive should load the compiled assets.

## Secure client preview

Recommended options:

1. Deploy as a private Cloudflare Pages preview and use Cloudflare Access for email or one-time-code protection.
2. Deploy to Vercel/Netlify and add platform authentication or password protection available on your plan.
3. Host inside a Laravel staging site protected by HTTP Basic Auth and HTTPS.

Do not include real campus network credentials, exact AP secrets, student identifiers, or production data in a client UI preview.

## Prototype notes

- All values are fictional sample data.
- The network test is simulated for UI review.
- Browser support for detailed network information varies; native/helper collection may be needed later for RSSI, SSID/BSSID and reliable packet-loss measurements.
- The prototype uses hosted fonts, Lucide icons and Chart.js, so internet access is required for those assets.
