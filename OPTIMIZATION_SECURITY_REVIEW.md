# BuyAlot / Bianlina — Optimization, Security & cPanel Hardening Review

> **Scope:** Laravel 12 + Vue 3 + Inertia e-commerce platform (with POS), targeted at a **low-resource shared cPanel host**.
> **Goal of this document:** A prioritized, file-referenced list of changes to make the project **lightweight, efficient, secure, and host-friendly**, plus a catalogue of **software-engineering practice** improvements.
> **Date:** 2026-06-23 · **Branch:** `refactoring_and_improvements`

---

## 0. How to read this document

Findings are grouped by theme. Each item is tagged with a **severity** and an **effort**:

- 🔴 **Critical** — fix before/at deployment (security or "won't run on cPanel").
- 🟠 **High** — significant resource, performance, or correctness impact.
- 🟡 **Medium** — quality / maintainability / moderate performance.
- 🟢 **Low** — hygiene / nice-to-have.

A consolidated, ordered **action plan** is in [Section 9](#9-prioritized-action-plan).

---

## 1. Executive summary

The application's **foundations are cPanel-compatible** — the active `.env` uses `file` cache, `file` sessions, `sync` queue, and `local` filesystem, none of which need Redis or a daemon. However, several things block a clean, lean, secure deploy:

| Area | Headline finding |
|---|---|
| **Secrets** | Live AWS, M-Pesa, DB and mail credentials sit in the on-disk `.env` (a *dev* file marked `APP_ENV=local`, `APP_DEBUG=true`). `.env` is **not** in git history (verified), but `.env.example` **is committed** and contains a real-looking mail password and admin emails. |
| **Search** | `SCOUT_DRIVER=meilisearch` is active. Meilisearch is a **separate server that does not exist on shared cPanel** — product search will fail unless switched to a DB/collection fallback. |
| **Bundle** | ~10 MB of built front-end assets. Two rich-text editors (CKEditor **and** Quill) bundled; `leaflet`, `vue-tel-input`, and `primevue` are installed but **unused**; ~5.7 MB of banner images are bundled instead of served statically. |
| **Backend** | Several heavy synchronous operations (image uploads, search-cache rebuild, fan-out notifications) and large in-memory loads (1000-row dropdowns) that risk PHP memory/time limits on shared hosting. |
| **Practices** | Authorization handled ad-hoc in controllers (no Policies), `FormRequest::authorize()` returning `true`, fat controllers, duplicated transformation logic, and debug code left in both PHP and Vue. |
| **Repo hygiene** | A 6.8 MB `public/build.zip` and a JVM crash log `hs_err_pid30420.log` are committed. |

**Estimated wins:** front-end build can shrink ~45% (10 MB → ~5.5 MB); repo shrinks several MB; per-request memory and latency drop materially on the admin/checkout paths.

---

## 2. 🔴 Security

### 2.1 Live credentials in the on-disk `.env` — rotate all of them
The working `.env` contains **real, active** secrets:

- `APP_KEY`, `HASHIDS_SALT`
- `DB_PASSWORD`
- `MAIL_PASSWORD`
- `AWS_ACCESS_KEY_ID` / `AWS_SECRET_ACCESS_KEY` (live)
- `MPESA_CONSUMER_KEY` / `MPESA_CONSUMER_SECRET` / `MPESA_PASSKEY` (live payment keys)
- `GOOGLE_CLIENT_SECRET`, `GOOGLE_MAPS_API_KEY`

**Good news (verified):** `.env` is **not** tracked and **not** in git history (`git log --all -- .env` is empty), so these were not leaked via the repo.

**Still required:**
1. **Rotate every key above** if this `.env` was ever shared, copied to a server with a misconfigured docroot, or pasted anywhere. M-Pesa and AWS keys in particular should be rotated as a precaution.
2. On cPanel, ensure the **document root points to `public/`**, never the project root, so `.env` can never be served.
3. Restrict the Google Maps API key by HTTP referrer and the AWS key to least-privilege (single bucket).

### 2.2 `.env.example` contains real-looking credentials — committed
**File:** `.env.example`
- Line 60–65: `MAIL_HOST=pop.kenyaweb.com`, a real username, and `MAIL_PASSWORD=@kenyan2024`.
- Line 73: real-looking `MAIL_ADMIN_ADDRESS` personal emails.
- Line 5: a concrete `HASHIDS_SALT` value.

**Fix:** Replace all values in `.env.example` with placeholders (`MAIL_PASSWORD=`, `your-salt-here`, etc.). An example file should never carry secrets. 🔴

### 2.3 `FormRequest::authorize()` returns `true` — anyone can create products
**File:** `app/Http/Requests/StoreProductRequest.php:9-12` (verified)
```php
public function authorize(): bool { return true; }
```
Any authenticated (or, depending on routing, unauthenticated) user passes authorization. **Fix:** return a real gate check, e.g. `return $this->user()?->can('create', \App\Models\Products\Product::class) ?? false;`. Audit **all** `FormRequest` classes for the same pattern. 🔴

### 2.4 Authorization done inline in controllers, not via Policies
Authorization is hand-written and inconsistent — easy to get wrong:
- `app/Http/Controllers/Admin/ProductController.php` — ownership checked manually in `store()` (`owner_id !== user->id`) but **not** in `edit()`/`update()`/`destroy()`. A seller can potentially edit another seller's product by ID.
- `app/Http/Controllers/Orders/OrderController.php:123-128` — seller filtering relies on `$sellerIds`; if `$isSeller` is true but the list is empty, the filter may not constrain results.
- `OrderController::dispatchItem()` — inline `abort(403)` logic.

**Fix:** Introduce `ProductPolicy`, `OrderPolicy`, and item-level policies; replace inline checks with `$this->authorize('update', $product)`. Register in `AuthServiceProvider`. 🔴

### 2.5 Missing rate limiting on expensive / abusable endpoints
**Verified:** auth routes already throttle (`routes/auth.php:67,71` → `throttle:6,1`). **Gaps:**
- Order creation (`POST` orders) — no throttle → spam/abuse vector.
- M-Pesa callback (`app/Http/Controllers/Payments/MpesaPaymentController.php`) — no throttle and no IP allow-list; combine with signature/whitelist validation.
- Cart add/update endpoints — no per-user limit.

**Fix:** Add `throttle:` middleware (e.g. `throttle:10,1` for orders) and validate the M-Pesa callback source. 🟠

### 2.6 Mass-assignment surface on sensitive fields
- `app/Models/User.php` — `$fillable` includes `user_type` and `additional_roles`. If a self-service profile-update request passes these through, a user could escalate their own role.
- `app/Models/Orders/Order.php` — `status`, `payment_status`, `fulfillment_status` are fillable.

**Fix:** Never bind these from public requests; whitelist columns explicitly in the relevant `FormRequest`/service, and keep status transitions behind policies. 🟠

### 2.7 File-upload validation depends solely on client-supplied extension
**File:** `app/Services/ImageService.php` uses `getClientOriginalExtension()` for the stored filename. `StoreProductRequest` does validate `image|mimes:...|max:10240` (good), but:
- Confirm **every** upload path (variant images, brand logos, policy attachments) routes through a `FormRequest` with `mimes`/`max` rules.
- If SVG is ever accepted, sanitize it (SVGs can carry scripts).

**Fix:** Centralize upload validation; validate real MIME, not client extension. 🟡

### 2.8 Production config sanity
The on-disk `.env` is `APP_ENV=local`, `APP_DEBUG=true`. On the server this **must** be `APP_ENV=production`, `APP_DEBUG=false` (already documented in `DEPLOYMENT_CPANEL.md`, but worth a deploy checklist gate). Leaving debug on leaks stack traces and config. 🔴

---

## 3. 🔴/🟠 cPanel host-friendliness (services & drivers)

### 3.1 Meilisearch will not exist on shared cPanel — provide a fallback
**Files:** `config/scout.php:19,140-158`; `.env` `SCOUT_DRIVER=meilisearch`; `app/Models/Products/Product.php` uses the `Searchable` trait.
Product search depends on a Meilisearch server at `127.0.0.1:8000`. On shared hosting there is no such server → **search breaks**.

**Options (in order of preference for cPanel):**
1. `SCOUT_DRIVER=database` (Scout's built-in DB driver) — zero infra, uses indexes you already added.
2. `SCOUT_DRIVER=collection` for small catalogs.
3. A hand-rolled `WHERE name/description LIKE ?` fallback with `LIMIT` (already partially present in `SearchController`).

Make the default driver `database` in config and only switch to Meilisearch on a VPS. 🔴

### 3.2 Redis / predis — remove from the critical path
`config/database.php:144-178` and `composer.json` (`predis/predis`) carry Redis support that cPanel can't run. The active `.env` doesn't use Redis (good), but:
- Keep cache/session/queue on `database` or `file` (see below).
- `predis/predis` can be dropped from `require` for a lean deploy if Redis is never used. 🟡

### 3.3 AWS S3 — confirm intentional, else drop the dependency
`FILESYSTEM_DISK=local` is active (good for cPanel). `league/flysystem-aws-s3-v3` and live AWS keys are present. If S3 is **not** used in production, remove the dependency and keys; if it **is**, note that local disk on cPanel has quota limits and image uploads should be size-capped. 🟠

### 3.4 Queue + scheduler: cron design
- Active `QUEUE_CONNECTION=sync` runs jobs **inline** — simple, but heavy jobs then block the web request (see §4). For background processing on cPanel, switch to `database` queue and add a cron:
  ```
  * * * * * cd /home/USER/app && php artisan schedule:run >> /dev/null 2>&1
  ```
  Let the scheduler dispatch `queue:work --stop-when-empty --max-time=50` rather than running a persistent worker (no Supervisor on shared hosting). `routes/console.php` already schedules `reservations:release-expired` every minute with `withoutOverlapping()` — good.
- **Caveat:** with `sync` queue, `Mail::queue(...)` still sends inline. Decide deliberately: `sync` (simple, slower requests) vs `database` + cron (faster requests, needs the cron). 🟠

### 3.5 Image processing memory
`intervention/image` needs GD/ImageMagick (usually present on cPanel but confirm the PHP extension is enabled). Large source images can blow the per-process memory limit — cap upload dimensions and process in a queued job, not inline. 🟡

### 3.6 Recommended cPanel `.env` baseline
```env
APP_ENV=production
APP_DEBUG=false
CACHE_STORE=database        # survives restarts; or 'file' for tiny sites
SESSION_DRIVER=database
QUEUE_CONNECTION=database    # + scheduler cron (see 3.4)
FILESYSTEM_DISK=local
SCOUT_DRIVER=database        # NOT meilisearch
```

---

## 4. 🟠 Backend performance & resource efficiency

> These matter most on a host with tight memory (often 256–512 MB/process) and `max_execution_time` limits.

### 4.1 Heavy synchronous work in the request lifecycle
- **Image uploads** — `app/Services/ImageService.php` + `app/Services/ProductService.php`: multiple images uploaded inline during product create/update (potentially 15+ writes per request). On a slow disk/S3 this times out. **Queue it.** 🟠
- **Search-cache rebuild** — `app/Services/SearchCacheService::rebuild()` loads **all** products + variants into memory (`->get()->toArray()`) and is triggered on product saves. A 50k-variant catalog spikes memory. A `RebuildSearchCache` job already exists (`app/Jobs/RebuildSearchCache.php`) — **dispatch it asynchronously** and stop rebuilding the whole index on every save. 🟠
- **Brand cache refresh** — `SearchCacheService` uses `array_search(array_column(...))` inside a loop over a brand's products: **O(n²)**. Build a keyed map once. 🟠
- **Fan-out notifications** — `app/Services/OrderPlacementService.php:~260-273`: `Notification::send()` to admins and per-seller, synchronously (one `Seller::find()` + N inserts per seller). On an order spanning many sellers this is many writes in the request. **Queue the notifications.** 🟠

### 4.2 Large unbounded in-memory loads
- `app/Http/Controllers/Admin/DiscountController.php:43-46` — loads up to **1000** products + 1000 variants + 1000 customers + 1000 brands into one page payload (~1 MB serialized/request). **Replace with searchable AJAX/typeahead endpoints.** 🟠
- `app/Http/Controllers/Admin/ProductController.php:~110-123` — loops paginated products calling `ProductStatus::find()` per row (N+1). Preload `ProductStatus::all()->keyBy('id')`. 🟠
- Restock history (`ProductController` ~878-906) caps at `limit(100)` but doesn't paginate — paginate instead. 🟡

### 4.3 N+1 and index-unfriendly queries
- `app/Http/Controllers/Orders/OrderController.php:~69` — `orWhereRaw("CONCAT(first_name,' ',last_name) LIKE ?")` defeats indexes. Search the columns separately (each can use an index) or add a generated/stored full-name column. 🟡
- Product-detail transformation in `HomeController` iterates the same variant collection multiple times (`map`, `flatMap`, `keyBy`) — collapse into one pass and eager-load `values.variant.category`. 🟡
- Audit `->through()`/`->map()` over paginated User lists (e.g. `Admin/DeliveryPersonController.php:42`) — select only needed columns. 🟡

### 4.4 External API calls on hot paths
- `app/Services/ShippingService.php` (Google Maps, ~line 160) is invoked while mapping regions on checkout/product pages. A slow API = slow page. **Cache shipping options per region (e.g. 1 hour).** 🟠
- M-Pesa callback verification, if it makes outbound calls, should be a queued job with retries, not inline. 🟡

### 4.5 Transactions spanning slow work
`ProductService::createOrUpdateProductStep()` wraps image handling inside `DB::transaction()`. Holding a DB connection/lock during file I/O increases contention. **Do uploads outside the transaction; commit only metadata inside.** 🟡

---

## 5. 🟠 Frontend bundle, assets & build

> Current built output: **~10 MB** across `public/build/assets` (242 files). On a bandwidth-limited host this directly affects cost and load time. Pages **are** code-split (Inertia `import.meta.glob`, verified) — good baseline.

### 5.1 Remove unused dependencies (verified zero usage)
- **`leaflet`** (+ `@types/leaflet`) — **no imports anywhere**; Google Maps is the real map lib (7 files). Remove (~261 KB asset). 🟠
- **`vue-tel-input`** — only a `.d.ts` exists, no usage. Remove. 🟢
- **`primevue`** — imported in `app.ts` but **no components used**. Remove (large). 🟠
- **`vue3-tel-input`** — confirm usage; only `country-calling-code` is actually used in phone fields. 🟢

### 5.2 Two rich-text editors bundled — consolidate to one
- **CKEditor** (`@ckeditor/ckeditor5-build-classic`, ~**1.4 MB**) used in `Admin/Orders/Edit.vue`, `Admin/Payments/Edit.vue`, `Admin/Products/Edit.vue`.
- **Quill** (`quill` + `@vueup/vue-quill`) used in `Admin/Policies/VersionForm.vue`, `Admin/Products/Create.vue`, and **registered globally in `resources/js/app.ts`** (so it ships in the main bundle for every page).

**Fix:** pick one editor; remove the other from `package.json`; stop registering it globally and import it **locally + lazily** in the 2–3 pages that need it. 🟠

### 5.3 Move bundled banner images to static `public/` and compress
`resources/js/assets/images/banners/` holds ~5.7 MB of PNG/JPEG (e.g. `sell-on-buyalot.png` 2.5 MB, `hikvision.png` 1.5 MB, `von.png` 1.2 MB) **imported into Vue** → bundled. Serve them from `public/` by URL instead, convert to **WebP**, and use responsive `srcset`. Expect ~65% reduction. 🟠

### 5.4 Add a Vite production build config
`vite.config.ts` has **no** build section. Add:
```ts
build: {
  sourcemap: false,                 // tsconfig currently has sourceMap:true
  chunkSizeWarningLimit: 600,
  rollupOptions: {
    output: {
      manualChunks: {
        'vendor-editor': ['@ckeditor/ckeditor5-build-classic'],
        'vendor-charts': ['chart.js'],
        'vendor-ui': ['reka-ui', 'lucide-vue-next'],
      },
    },
  },
},
```
Also set `sourceMap: false` in `tsconfig.json` for prod and remove the hard-coded ngrok host from `server.allowedHosts`. 🟠

### 5.5 Lazy-load heavy components
No `defineAsyncComponent`/dynamic `import()` anywhere. Lazy-load conditionally-rendered heavy components: `MapLocationModal.vue` (400 lines, Google Maps), `VuePermissionsManager.vue` (696), `VariantImages.vue` (446), `ProductVariantCreator.vue` (316), and the Chart.js Dashboard. 🟡

### 5.6 Lodash & micro-utils
Per-method imports are used (`import { debounce } from 'lodash'`) — acceptable, but switch to `lodash-es` (or `lodash/debounce`) for reliable tree-shaking, or replace with VueUse's `useDebounceFn` and native `toUpperCase()` to drop the 72 KB lodash chunk. 🟢

---

## 6. 🟡 Database

- **195 migrations.** Recent `..._add_performance_indexes_to_tables.php` and `..._add_search_indexes.php` add good composite indexes on products/variants/orders/categories/inventory — solid.
- **Gaps to check:** individual indexes on nullable FKs `orders.billing_address_id`, `orders.shipping_address_id`, `orders.discount_id`, `order_items.commission_id`. Add where these are filtered/joined. 🟡
- **JSON columns** (`orders.metadata`, `fulfillment_info`, `applied_discounts`, `order_items.product_snapshot`) — fine as storage, but never filter on JSON paths in list queries without generated columns. 🟡
- **Housekeeping:** schedule pruning of expired `sessions`, stale `cache`, old guest `carts`, and `failed_jobs`; consider archiving old `orders`. Keeps tables (and shared-host disk) small. 🟡
- On deploy, run `composer install --no-dev --optimize-autoloader` and `php artisan config:cache route:cache view:cache event:cache`. 🟢

---

## 7. 🟡 Software-engineering practices (catalogue)

These don't break the app but raise defect risk and slow future work. Address opportunistically.

1. **Authorization policies** — replace inline `abort(403)` / manual ownership checks with `Policy` classes (see §2.4). *(highest-value practice fix)*
2. **Form Requests everywhere** — controllers using inline `Validator::make()` or no validation (`CartController`, `OrderController` filters, `DiscountController`) should use dedicated `FormRequest` classes with real `authorize()`.
3. **Thin the controllers** — `ProductController::store()` (~130 lines), `OrderController::show()`/`update()` (120–150 lines each), `HomeController::dashboard()` (~114 lines of stats) should delegate to services and **API Resources** (`OrderResource`, `ProductResource`) for response shaping.
4. **De-duplicate transformation logic** — order payload mapping is duplicated across `Orders/OrderController` and `Admin/OrderController`; variant-category mapping is duplicated within `ProductController::create()`/`edit()`. Extract to Resources / private helpers.
5. **Error handling** — catch specific exceptions (`QueryException`) instead of string-matching `'SQLSTATE'` in `OrderController::store()`; wrap service failures in domain exceptions rather than bare `throw $e`.
6. **Remove dead code** — `OrderController::show1()` appears unused; many commented-out `// Log::info(...)` blocks; remove or formalize behind log level/config.
7. **Consistent API envelope** — standardize JSON responses (some return `{message,data}`, others raw).
8. **Frontend debug code** — ~12 `.vue`/`.ts` files contain `console.log`/`console.error` (heaviest: `Admin/Orders/Edit.vue`, `VuePermissionsManager.vue`, `Sell/Apply.vue`). Add ESLint `no-console` (warn) and strip before deploy.
9. **TypeScript discipline** — `eslint.config.js` disables `@typescript-eslint/no-explicit-any`, and `page.props as any` is widespread. Type the shared Inertia page props once and re-enable the rule incrementally.
10. **Split monolithic components** — `Sell/Apply.vue` (1,319 lines), `Delivery/Dashboard.vue` (948), `Admin/Orders/Show.vue` (884), `Checkout/Payment.vue` (865) should be broken into sub-components; use `provide/inject`/composables for `user`/auth state instead of prop drilling.
11. **Add `vue-tsc` to the build** for compile-time type checks (currently Vite transpiles without type checking).

---

## 8. 🟢 Repository hygiene

- **Remove committed build artifact:** `public/build.zip` (6.8 MB) is tracked. Delete it and add `public/build.zip` (or `*.zip`) to `.gitignore`. 🟠
- **Remove crash log:** `hs_err_pid30420.log` (JVM crash dump) is committed at the repo root. Delete and ignore. 🟢
- **Lockfile sprawl:** both `package-lock.json` **and** `pnpm-lock.yaml` are present; `package-lock.json` is in `.gitignore` yet a 934 KB copy exists. Pick **one** package manager and keep a single lockfile. 🟢
- **Duplicate large images in `public/`:** `swift.png`, `buyalotswift.png`, `img.png` are each ~981 KB and look duplicated; `bianlina1.png` (1.6 MB) overlaps with bundled copies. De-duplicate and compress. 🟡
- **`.env.example` secrets** — see §2.2. 🔴

---

## 9. Prioritized action plan

### Phase 1 — implementation status (2026-06-23)

The in-code Phase 1 items have been actioned on branch `refactoring_and_improvements`:

| # | Item | Status | Notes |
|---|---|---|---|
| 2 | Scrub `.env.example` | ✅ Done | All real secrets → placeholders; `APP_KEY`/`HASHIDS_SALT`/`DB_PASSWORD`/`MAIL_*` blanked. |
| 4 | Meilisearch → DB search | ✅ Done | `.env.example` now `SCOUT_DRIVER=database`; `SearchController::search()` falls back to a direct Eloquent `LIKE` query (name/slug/SKU) whenever the driver isn't `meilisearch`, so search works on cPanel with no engine. Meilisearch still used when explicitly configured. |
| 5 | Product create/update authorization | ✅ Done | Real enforcement added in `ProductController::store()` (the live path): step 1 requires `create-products`, later steps require `edit-products`, with admin/super-admin bypass. **Note:** `StoreProductRequest`/`UpdateProductRequest` were **dead code** — never type-hinted by any controller — so their `authorize()` could not protect anything. They were still hardened (return real gate checks) as defense-in-depth in case they're wired in later. |
| 7 | Remove repo junk | ✅ Done | `public/build.zip` (6.8 MB) and `hs_err_pid30420.log` untracked + deleted; added to `.gitignore`. |
| 1 | Rotate credentials | ⚠️ **Manual** | AWS, M-Pesa, Google, DB, mail keys must be rotated by you in the respective consoles — cannot be done from code. |
| 3 | Production `APP_ENV`/`APP_DEBUG` | ⚠️ **Manual (server)** | Set on the cPanel server's `.env` at deploy time; local dev `.env` left as `local` intentionally. |
| 6 | cPanel docroot = `public/` | ⚠️ **Manual (server)** | Configure in cPanel; cannot be set from the repo. |

**Follow-ups uncovered while implementing Phase 1:**
- `destroy()`/`destroyAll()`/`destroyImage()` on products have **no permission gate** — add `delete-products` (with seller ownership) in Phase 2. Left untouched now to avoid changing current seller delete behavior without confirmation.
- Other `FormRequest` classes (`CommissionPlanRequest`, `CustomerRequest`, `CustomerAddressRequest`, `SupportTicketRequest`) still `return true` in `authorize()`. They sit behind auth/role middleware so the exposure is lower, but they should get real checks — deferred to Phase 3 to avoid breaking guest/customer flows.
- The Scout `database` driver alone would error on this model (`toSearchableArray()` exposes computed keys like `skus`/`brand` that aren't real columns); that's why the fix uses a controller-level Eloquent fallback rather than just flipping the driver.

### Phase 1 — 🔴 Before deployment (security + "will it run")
1. Rotate AWS, M-Pesa, Google, DB, and mail credentials; lock down API keys. *(§2.1)*
2. Scrub `.env.example` to placeholders. *(§2.2)*
3. Server `.env`: `APP_ENV=production`, `APP_DEBUG=false`. *(§2.8)*
4. Switch `SCOUT_DRIVER` to `database` and verify search works without Meilisearch. *(§3.1)*
5. Fix `StoreProductRequest::authorize()` and audit all Form Requests. *(§2.3)*
6. Ensure cPanel docroot = `public/`. *(§2.1)*
7. Remove `public/build.zip` and `hs_err_pid30420.log` from the repo. *(§8)*

### Phase 2 — 🟠 First hardening pass (resources + abuse)
8. Introduce Policies for Product/Order/Item; remove inline auth. *(§2.4)*
9. Add rate limiting to orders, cart, and the M-Pesa callback. *(§2.5)*
10. Queue image processing, search-cache rebuild, and order notifications. *(§4.1)*
11. Replace 1000-row admin dropdowns with AJAX typeahead. *(§4.2)*
12. Cache shipping/Google-Maps results. *(§4.4)*
13. Remove `leaflet`, `primevue`, `vue-tel-input`; consolidate to one rich-text editor and lazy-load it. *(§5.1, §5.2)*
14. Move/compress banner images; add Vite `build` config (no sourcemaps, manualChunks). *(§5.3, §5.4)*
15. Decide queue strategy (`database` + scheduler cron) and document the cron. *(§3.4)*

### Phase 3 — 🟡 Quality & maintainability
16. Thin controllers → services + API Resources; de-duplicate transforms. *(§7.3, §7.4)*
17. Form Requests for all write endpoints. *(§7.2)*
18. Specific exception handling; remove dead code & commented logs. *(§7.5, §7.6)*
19. Add indexes on nullable FKs; schedule table pruning. *(§6)*
20. Lazy-load heavy Vue components; split monolithic pages. *(§5.5, §7.10)*

### Phase 4 — 🟢 Polish
21. Strip `console.*`; add ESLint `no-console`. *(§7.8)*
22. Re-enable `no-explicit-any` incrementally; add `vue-tsc` to build. *(§7.9, §7.11)*
23. Single package manager / lockfile; de-duplicate `public/` images. *(§8)*
24. Drop `predis`/S3 deps if confirmed unused. *(§3.2, §3.3)*

---

## Appendix — key file references

| Concern | File(s) |
|---|---|
| Secrets | `.env` (on disk), `.env.example` (committed) |
| Search driver | `config/scout.php`, `app/Models/Products/Product.php` |
| Redis/S3 config | `config/database.php:144-178`, `config/filesystems.php:57-68` |
| Scheduler | `routes/console.php` |
| Auth gap | `app/Http/Requests/StoreProductRequest.php:9-12` |
| Auth (inline) | `app/Http/Controllers/Admin/ProductController.php`, `app/Http/Controllers/Orders/OrderController.php` |
| Heavy sync work | `app/Services/ImageService.php`, `app/Services/SearchCacheService.php`, `app/Services/OrderPlacementService.php` |
| Big in-memory loads | `app/Http/Controllers/Admin/DiscountController.php:43-46` |
| External API on hot path | `app/Services/ShippingService.php` |
| Bundle/build | `package.json`, `vite.config.ts`, `resources/js/app.ts`, `resources/js/assets/images/banners/` |
| DB indexes | `database/migrations/*add_performance_indexes*`, `*add_search_indexes*` |
| Repo hygiene | `public/build.zip`, `hs_err_pid30420.log` |
