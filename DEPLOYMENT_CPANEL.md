# cPanel Deployment Guide

## Overview
This application is optimized for deployment on shared cPanel hosting without requiring Redis or other external cache servers.

## Key Configuration Changes

### 1. Cache System
**Changed from:** Redis (not available on most shared hosting)
**Changed to:** Database cache (fully compatible with cPanel)

**Benefits:**
- ✅ Works on all shared hosting environments
- ✅ No additional server dependencies
- ✅ Persistent cache across server restarts
- ✅ Good performance for production workloads
- ✅ Automatic cleanup via Laravel's cache:prune command

**Configuration:**
```env
CACHE_STORE=database
CACHE_PREFIX=buyalot_cache_
```

**Alternative:** File cache (suitable for smaller datasets)
```env
CACHE_STORE=file
```

### 2. Queue System
**Configuration:** Database queue (already configured)

```env
QUEUE_CONNECTION=database
```

**Important:** On cPanel, you need to set up a cron job to process queues:
```bash
* * * * * cd /home/username/public_html && php artisan queue:work --stop-when-empty
```

Or use this for continuous processing:
```bash
* * * * * cd /home/username/public_html && php artisan queue:work --max-time=3600
```

### 3. Session Management
**Configuration:** Database sessions (already configured)

```env
SESSION_DRIVER=database
SESSION_LIFETIME=120
```

## Required Migrations

All necessary migrations are already in place:
- ✅ `cache` table (0001_01_01_000001_create_cache_table.php)
- ✅ `cache_locks` table (0001_01_01_000001_create_cache_table.php)
- ✅ `jobs` table (0001_01_01_000002_create_jobs_table.php)
- ✅ `sessions` table (0001_01_01_000000_create_users_table.php)
- ✅ `failed_jobs` table (0001_01_01_000002_create_jobs_table.php)

## Performance Optimizations

### 1. Database Indexes
The system includes optimized indexes for:
- Cache lookups
- Queue processing
- Session management
- Product searches

### 2. Search Cache Service
The `SearchCacheService` already uses Laravel's cache facade, which automatically adapts to your configured cache driver:

```php
// This works with both file and database cache
Cache::put(self::CACHE_KEY, $data, self::CACHE_TTL);
```

### 3. Scheduled Tasks (Cron Jobs)

Add these cron jobs in cPanel:

```bash
# Laravel scheduler (handles all scheduled tasks)
* * * * * cd /home/username/public_html && php artisan schedule:run >> /dev/null 2>&1

# Queue worker (process background jobs)
* * * * * cd /home/username/public_html && php artisan queue:work --stop-when-empty >> /dev/null 2>&1

# Cache cleanup (run daily)
0 0 * * * cd /home/username/public_html && php artisan cache:prune-stale-tags >> /dev/null 2>&1
```

## Deployment Steps

### 1. Upload Files
Upload your application files to your cPanel hosting via FTP or Git.

### 2. Configure Environment
Copy `.env.example` to `.env` and update:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=your_database_name
DB_USERNAME=your_database_user
DB_PASSWORD=your_database_password

CACHE_STORE=database
SESSION_DRIVER=database
QUEUE_CONNECTION=database
```

### 3. Run Migrations
```bash
php artisan migrate --force
```

### 4. Optimize for Production
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
```

### 5. Build Search Cache
```bash
php artisan tinker
>>> App\Services\SearchCacheService::rebuild();
```

### 6. Set Permissions
```bash
chmod -R 755 storage bootstrap/cache
```

## Cache Performance Tips

### Database Cache
- **Best for:** Medium to large datasets, multiple concurrent users
- **TTL:** Configured at 21600 seconds (6 hours) for search cache
- **Cleanup:** Automatically handled by Laravel

### File Cache
- **Best for:** Small datasets, low traffic sites
- **Faster:** For read-heavy operations with small cache size
- **Note:** Ensure `storage/framework/cache` is writable

## Monitoring & Maintenance

### Clear Cache
```bash
php artisan cache:clear
```

### Rebuild Search Cache
```bash
php artisan tinker
>>> App\Services\SearchCacheService::rebuild();
```

### Check Queue Status
```bash
php artisan queue:monitor
```

### View Failed Jobs
```bash
php artisan queue:failed
```

## Troubleshooting

### Issue: Cache not working
**Solution:** Verify database tables exist and environment is correctly configured
```bash
php artisan migrate:status
```

### Issue: Queue jobs not processing
**Solution:** Ensure cron job is set up correctly in cPanel
```bash
php artisan queue:listen --tries=3
```

### Issue: Permission denied errors
**Solution:** Fix storage permissions
```bash
chmod -R 755 storage
chmod -R 755 bootstrap/cache
```

## Performance Comparison

### Redis vs Database Cache (on cPanel)

| Feature | Redis | Database Cache |
|---------|-------|----------------|
| cPanel Support | ❌ Not available | ✅ Native support |
| Setup Complexity | High | Low |
| Performance | Excellent | Good |
| Persistence | Optional | Yes |
| Shared Hosting | ❌ No | ✅ Yes |
| Cost | VPS required | Included |

## Conclusion

Your application is now fully compatible with shared cPanel hosting. The database cache provides excellent performance without requiring Redis or other external services.

For higher traffic or VPS deployments, you can easily switch to Redis by changing:
```env
CACHE_STORE=redis
QUEUE_CONNECTION=redis
```

But for cPanel deployments, stick with:
```env
CACHE_STORE=database
QUEUE_CONNECTION=database
SESSION_DRIVER=database
```
