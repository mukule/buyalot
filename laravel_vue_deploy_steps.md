# Laravel + Vue Production Pull & Deploy Steps

This is a step-by-step guide for safely updating your Laravel + Vue project on production after pulling changes from Git.

---

## 1. Navigate to project folder

```bash
cd /home/buyalotl/buyalot
```

## 2. Pull latest code

```bash
git fetch origin
git checkout <branch>
git pull origin <branch>
```

## 3. Install/update PHP dependencies

```bash
composer install --optimize-autoloader --no-dev
```

## 4. Install/update Node dependencies and build assets

```bash
npm install
npm run build
```

## 5. Copy build folder to public_html

```bash
rm -rf /home/buyalotl/public_html/build
cp -r /home/buyalotl/buyalot/public/build /home/buyalotl/public_html/
chown -R smaina:smaina /home/buyalotl/public_html/build
```

## 6. Sync storage images to public_html

```bash
cp -r /home/buyalotl/buyalot/storage/app/public/images/* /home/buyalotl/public_html/storage/images/
chown -R smaina:smaina storage/images
```

## 7. Clear Laravel caches

```bash
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

## 8. Fix permissions for storage and cache folders

```bash
chown -R smaina:smaina storage bootstrap/cache
chmod -R 775 storage bootstrap/cache
```

## 9. Verify

- Check that Vite assets exist in `public_html/build/assets`
- Test site pages in browser
- Verify images load from `/storage/images`

---

Optional: For repeated deployments, you can wrap all these commands into a shell script to automate the process.
