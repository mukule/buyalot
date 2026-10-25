# SEO deployment checklist (nginx)

## 1. Build with SSR

```bash
composer install --no-dev --optimize-autoloader
npm ci
npm run build:ssr            # builds public/build and bootstrap/ssr/ssr.mjs
php artisan optimize          # config/route/view caches
php artisan storage:link
```

## 2. Environment

```dotenv
APP_URL=https://example.com          # drives canonical URLs, sitemap, robots.txt, JSON-LD
APP_NAME="Bianlina"
INERTIA_SSR_ENABLED=true             # see config/inertia.php (defaults to enabled)

SEO_HOME_TITLE="Bianlina | Online Shopping in Kenya"
SEO_HOME_DESCRIPTION="Shop electronics, appliances, cars and building materials at the best prices in Kenya."
SEO_FACEBOOK_URL=
SEO_INSTAGRAM_URL=
SEO_TWITTER_URL=
SEO_TIKTOK_URL=
GOOGLE_SITE_VERIFICATION=
BING_SITE_VERIFICATION=
```

## 3. Services

```bash
sudo cp deploy/nginx/buyalot.conf /etc/nginx/sites-available/buyalot.conf
sudo ln -s /etc/nginx/sites-available/buyalot.conf /etc/nginx/sites-enabled/
sudo nginx -t && sudo systemctl reload nginx

sudo cp deploy/systemd/inertia-ssr.service /etc/systemd/system/
sudo systemctl daemon-reload
sudo systemctl enable --now inertia-ssr
```

Cron for the scheduler (sitemap regenerates daily at 03:00):

```
* * * * * cd /var/www/buyalot && php artisan schedule:run >> /dev/null 2>&1
```

## 4. Verify

```bash
curl -s https://example.com/products/<slug> | grep -c '<h1'            # >0 means SSR works
curl -s https://example.com/products/<slug> | grep 'application/ld+json'
curl -s https://example.com/robots.txt
curl -s https://example.com/sitemap.xml
```

Then:

- Google Search Console: add property, verify (meta tag via `GOOGLE_SITE_VERIFICATION`), submit `sitemap.xml`.
- Bing Webmaster Tools: same, with `BING_SITE_VERIFICATION`. Bing also feeds DuckDuckGo and Yahoo.
- Rich Results Test (https://search.google.com/test/rich-results) on a product URL and a category URL.
- PageSpeed Insights on home, a category and a product page; fix anything red in Core Web Vitals.
- Google Merchant Center: create account, add the product feed (see below) for Shopping tab results.
- Google Business Profile + Bing Places for local search ("near me", Maps).

## 5. After each deploy

```bash
npm run build:ssr && php artisan optimize && sudo systemctl restart inertia-ssr
php artisan sitemap:generate
```
