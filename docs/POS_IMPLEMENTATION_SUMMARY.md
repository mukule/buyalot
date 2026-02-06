# POS Extraction — Implementation Summary

## Completed (Phases 1–2)

### Task 1: POS Boundaries
- **`docs/POS_BOUNDARIES.md`** updated to separate:
  - **POS selling** (move to domain / standalone Vue 3 app): login, terminal, sales, hold, void, unsettled payments.
  - **Admin dashboard** (stay in monolith): reports, POS settings, registers CRUD, invoicing.

### Task 2: POS Backend Domain
- **`app/Domains/POS/`** structure:
  - **Controllers:** `PosAuthController`, `PosSaleController`, `PosSessionController` (thin; logic in services).
  - **Services:** `SaleService` — creates sales, VAT from settings, allocated payments, session totals (no business logic in controllers).
  - **Enums:** `SaleStatus` (OPEN, HELD, COMPLETED, VOIDED) — ready for hold/resume/void in Phase 3.
  - **Events / Listeners / DTOs:** placeholder folders for Phase 3–4.

- **Order model** (shared): added `HasPayments` trait, `pos_session_id` in fillable, `posSession()` relationship.

### Task 3: POS API Routes
- **`routes/api/pos.php`** (included from `routes/api.php`):
  - Base path: **`/api/pos`**
  - **Stateless, JSON only.** All authenticated routes use **`auth:sanctum`** (token from login).

### Task 4: POS Terminal Login
- **`PosAuthController`**:
  - **`POST /api/pos/login`**  
    Body: `username` (email or phone), `pin` (4 digits), optional `terminal_id` (pos_register_id), optional `opening_balance`.  
    Returns: `user`, `token` (Bearer), `terminal` (session_id, register_id, register_name) when `terminal_id` is sent.
  - **`POST /api/pos/logout`**  
    Header: `Authorization: Bearer <token>`. Revokes current POS token.

- **Session (API, JSON):**
  - **`GET /api/pos/session/current`** — current user’s open session + POS settings (read-only).
  - **`POST /api/pos/sessions/open`** — open session (pos_register_id, opening_balance).
  - **`POST /api/pos/sessions/{session}/close`** — close session (closing_balance, notes).

- **Sales:** **`POST /api/pos/sales`** — create sale (delegates to `SaleService`); same payload as before (pos_session_id, customer_id, items, payment_method, amount_paid, allocated_payment_ids).

- **Products, customers, unallocated payments, voided sales** — same behaviour as existing POS controllers; used via `/api/pos/*` with token.

## POS API Endpoints (all under `/api/pos`)

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| POST | `/api/pos/login` | — | Username + PIN + optional terminal_id → user + token + terminal |
| POST | `/api/pos/logout` | Bearer | Revoke token |
| GET | `/api/pos/session/current` | Bearer | Open session + settings (read-only) |
| POST | `/api/pos/sessions/open` | Bearer | Open terminal session |
| POST | `/api/pos/sessions/{session}/close` | Bearer | Close session |
| GET | `/api/pos/products` | Bearer | Products (paginated) |
| GET | `/api/pos/categories` | Bearer | Categories + brands |
| GET | `/api/pos/customers` | Bearer | Customer search |
| POST | `/api/pos/customers` | Bearer | Quick create customer |
| POST | `/api/pos/sales` | Bearer | Create sale (SaleService) |
| GET | `/api/pos/unallocated-payments` | Bearer | By customer_id (query) |
| POST | `/api/pos/unallocated-payments` | Bearer | Store unsettled payment |
| GET | `/api/pos/voided-sales` | Bearer | By pos_session_id (query) |
| POST | `/api/pos/voided-sales` | Bearer | Store voided sale |
| POST | `/api/pos/voided-sales/{id}/recall` | Bearer | Recall voided sale |

## What Stays in Monolith (unchanged)

- **Admin dashboard:** POS Settings, Registers CRUD, Sales reports, Invoicing, VAT/template admin.
- **Web POS routes:** `admin/pos/*` (Inertia) remain for admin; standalone POS UI will use only `/api/pos/*`.
- **eCommerce:** Checkout, cart, my-orders, payment callbacks — untouched.

## Next Steps (Phases 3–8)

- **Phase 3:** Sale statuses (OPEN / HELD / COMPLETED / VOIDED), hold sale, resume held sale, void sale, persist held sales in DB.
- **Phase 4:** Unsettled payments (already in place); add events (PosPaymentReceived, SaleCompleted, SaleHeld, SaleVoided) and broadcast (Echo/Reverb).
- **Phase 5:** VAT from admin settings (read-only in POS API); inclusive/exclusive and per-line exposure.
- **Phase 6:** Standalone Vue 3 POS app (`/pos-ui`) with login, active sale, held sales, payments, real-time; Pinia/composables for state.
- **Phase 7:** Keep admin reports/settings/invoicing as-is.
- **Phase 8:** Remove POS selling Vue from monolith; harden POS APIs; confirm eCommerce and admin unchanged.
