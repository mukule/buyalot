# Buyalot POS Terminal

Standalone Vue 3 SPA for the Buyalot Point-of-Sale system. Runs on its own subdomain (e.g. `pos.buyalot.com`) and consumes the main Buyalot backend API.

## Prerequisites

- Node.js 18+
- The main Buyalot Laravel backend running and accessible

## Setup

```bash
# Install dependencies
npm install

# Copy env and configure backend URL
cp .env.example .env
# Edit .env → set VITE_API_BASE_URL to your backend (e.g. https://buyalot.com)

# Start dev server
npm run dev
```

The dev server runs on `http://localhost:5174` by default.

## Production Build

```bash
npm run build
```

Output goes to `dist/`. Deploy this as a static site behind any web server (Nginx, Caddy, Vercel, Netlify, etc.).

### Nginx Example

```nginx
server {
    listen 80;
    server_name pos.buyalot.com;
    root /var/www/pos/dist;
    index index.html;

    location / {
        try_files $uri $uri/ /index.html;
    }
}
```

## Backend CORS

The Laravel backend must allow cross-origin requests from the POS domain. Add `POS_APP_URL` to the backend `.env`:

```
POS_APP_URL=https://pos.buyalot.com
```

The `config/cors.php` in the backend is already configured to read this value.

## Architecture

- **Vue 3** + Composition API
- **Vue Router 4** for client-side routing
- **Axios** for API calls (Bearer token auth via Laravel Sanctum)
- **Tailwind CSS v4** with shadcn-vue components
- Fully stateless — token stored in localStorage
