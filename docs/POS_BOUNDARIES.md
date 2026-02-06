# POS Domain Boundaries — Identification & Annotation

**Purpose:** Map all POS-related code. Separate **POS selling** (standalone UI) from **Admin dashboard** (reports, settings, invoicing).  
**Legend:** `[POS Selling]` = Move to POS domain / standalone POS UI · `[Admin]` = Keep in monolith (reports, settings, invoicing) · `[Shared]` = Used by both.

---

## Summary: What Moves vs What Stays

| Area | POS Selling (standalone) | Admin Dashboard (monolith) |
|------|--------------------------|----------------------------|
| **Login** | Username + 4-digit PIN, terminal_id → token | Existing admin auth (unchanged) |
| **UI** | Standalone Vue 3 POS app (`/pos-ui`) | Admin POS Settings, Reports, Invoicing pages |
| **API** | `routes/api/pos.php` — stateless JSON, POS token | Existing admin web + API for reports/settings |
| **Backend** | `app/Domains/POS` — sales, hold, void, payments | ReportController, PosSettingsController, InvoiceController stay in monolith |
| **VAT/Templates** | Read-only from admin settings | Admin CRUD for VAT, invoice templates |

---

## 1. Controllers

| File | POS Selling (move) | Admin (stay) | Notes |
|------|--------------------|--------------|--------|
| `app/Http/Controllers/POS/PosController.php` | ✓ | | Session open/close, PIN verify, lock, terminal show. **Move selling parts to domain;** keep admin redirects or remove from monolith when POS UI is standalone. |
| `app/Http/Controllers/POS/PosOrderController.php` | ✓ | | Creates POS order: items, VAT, payments. **Move to Domains/POS + SaleService;** thin controller. |
| `app/Http/Controllers/POS/PosProductController.php` | ✓ | | Product list + categories for POS. **Move to Domains/POS.** |
| `app/Http/Controllers/POS/PosCustomerController.php` | ✓ | | Customer search + quick create. **Move to Domains/POS.** |
| `app/Http/Controllers/POS/PosSettingsController.php` | | ✓ | Global settings, registers CRUD. **Stay in monolith** — admin-only. |
| `app/Http/Controllers/POS/PosUnallocatedPaymentController.php` | ✓ | | Unsettled payments: list, create. **Move to Domains/POS.** |
| `app/Http/Controllers/POS/PosVoidedSaleController.php` | ✓ | | Voided sales: list, store, recall. **Move to Domains/POS.** |
| `app/Http/Controllers/Admin/ReportController.php` | | ✓ | Sales reports (POS + online). **Stay in monolith.** |
| `app/Http/Controllers/Admin/InvoiceController.php` | | ✓ | Invoicing, delivery notes, credit notes. **Stay in monolith.** |
| `app/Http/Controllers/Orders/OrderController.php` | — | — | Shared: eCommerce my-orders; POS uses Order model. Do not move. |
| `app/Http/Controllers/Billing/InvoiceController.php` | — | — | API invoices. Admin/API; POS UI only reads. |
| `app/Http/Controllers/Payments/*` | — | — | eCommerce checkout; POS uses separate POS payment flow. |

---

## 2. Models

| File | Domain | Notes |
|------|--------|--------|
| `app/Models/POS/PosRegister.php` | **[POS]** | Register ↔ warehouse, sessions. |
| `app/Models/POS/PosSession.php` | **[POS]** | Session: open/closed, totals (cash/mpesa/other). |
| `app/Models/POS/PosSetting.php` | **[POS]** **[Taxes]** | VAT enabled/percentage, receipt/invoice prefix, payment_methods. Singleton-style `getSettings()`. |
| `app/Models/POS/PosUnallocatedPayment.php` | **[POS]** **[Payments]** | Unallocated payment: customer, session, amount, used_amount, status. |
| `app/Models/POS/PosVoidedSale.php` | **[POS]** **[Sales]** | Voided sale: session, cart_data, total_amount, reason, recalled. |
| `app/Models/Orders/Order.php` | **[Shared]** | Order: has `pos_session_id` (nullable). Used for both eCommerce and POS. **Missing:** `pos_session_id` in fillable; `payments()` morphMany relationship (Payments table uses morphs('payable')). |
| `app/Models/Orders/OrderItem.php` | **[Shared]** | Order line items. |
| `app/Models/Payment/Payment.php` | **[Payments]** **[Shared]** | Polymorphic payable (Order, etc.). |
| `app/Models/Customer/Customer.php` | **[Shared]** | Customer — used by POS and eCommerce. |

---

## 3. Routes

| Location | Domain | Notes |
|----------|--------|--------|
| `routes/web.php` (lines 53–56) | **[POS]** | POS direct login (guest): `pos/login`, `pos/login.submit`. |
| `routes/web.php` (lines 184–222) | **[POS]** | Admin POS: index, verify-pin, lock, sessions open/close, terminal, products, categories, customers, orders store, voided-sales, unallocated-payments, settings, registers. All under `check_permission:access-pos`. |
| `routes/api.php` | **[Shared]** | No POS-specific API yet. Orders, invoices under `auth:sanctum`. |
| `routes/order.php` | **[Shared]** | Customer my-orders — eCommerce only. |
| `routes/payment.php` | **[Payments]** **[Shared]** | Payment initiate, status, callback. |

---

## 4. Services

| File | Domain | Notes |
|------|--------|--------|
| `app/Services/PaymentService.php` | **[Payments]** **[Shared]** | Mpesa init, create payment. Used by eCommerce; InitiatePaymentRequest supports `pos_session` as payable. |
| **None** | **[POS]** **[Sales]** | **No dedicated POS or Sale service.** Sale/order creation and VAT logic live in `PosOrderController`. |
| **None** | **[Taxes]** | VAT is read from `PosSetting` and applied inline in `PosOrderController`; not a reusable VAT service. |

---

## 5. Vue Components (POS UI in monolith)

| File | POS Selling (standalone Vue 3 app) | Admin (stay in monolith) | Notes |
|------|-----------------------------------|--------------------------|--------|
| `resources/js/pages/Admin/POS/Index.vue` | ✓ → rebuild in `/pos-ui` | | POS home: registers, open session, PIN. |
| `resources/js/pages/Admin/POS/Login.vue` | ✓ → rebuild in `/pos-ui` | | Terminal login: username + PIN. |
| `resources/js/pages/Admin/POS/Terminal.vue` | ✓ → rebuild in `/pos-ui` | | Terminal: products, cart, customer, pay, hold, void, unsettled payments. |
| `resources/js/pages/Admin/POS/Settings.vue` | | ✓ | POS settings & registers. **Stay in admin.** |
| `resources/js/pages/Admin/Reports/Sales/Index.vue` | | ✓ | Sales report. **Stay in admin.** |

---

## 6. Logic Ownership Summary

- **POS:** PosController, PosProductController, PosCustomerController, PosSettingsController, PosRegister, PosSession, PosSetting, POS Vue pages (Index, Login, Terminal, Settings), POS web routes.
- **Sales:** PosOrderController (create order), PosVoidedSaleController, PosVoidedSale, Order/OrderItem (shared). **Gap:** No “sale” abstraction (OPEN/HELD/COMPLETED/VOIDED); current flow is order create + voided_sales for voids only; no “hold” sale in DB.
- **Payments:** PosOrderController (allocated + primary payment), PosUnallocatedPaymentController, PosUnallocatedPayment, Payment (shared), PaymentService (shared). Unsettled payments: store + attach to sale later — **already implemented**.
- **Taxes:** PosSetting (vat_enabled, vat_percentage), VAT calculation inline in PosOrderController. **Gap:** No VAT engine (inclusive/exclusive, per-line) reusable outside POS.

---

## 7. eCommerce vs POS — Do Not Touch

- **eCommerce checkout:** CartController, checkout summary/payment routes, Cart/Order placement (OrderPlacementService if any), payment initiate for `order` payable.
- **Customer-facing:** Cart, Checkout, My Orders, OrderController (customer), payment callbacks.
- **Shared but not POS-specific:** Order model, Payment model, Customer, ReportController (keep POS filters but do not remove web sales).

---

## 8. Next Steps

1. **Phase 1:** Create `app/Domains/POS`; add `routes/api/pos.php` (stateless JSON, POS token); move only **POS selling** logic into domain.
2. **Phase 2:** PosAuthController: login(username, PIN, optional terminal_id) → user + terminal session token; all POS APIs require token.
3. **Phase 3–4:** SaleService (OPEN/HELD/COMPLETED/VOIDED), hold/resume/void; unsettled payments; events & broadcasting.
4. **Phase 5:** VAT from admin settings (read-only in POS API).
5. **Phase 6:** Standalone Vue 3 POS UI (`/pos-ui`) — login, active sale, held sales, payments, real-time; no admin logic.
6. **Phase 7:** Admin dashboard unchanged (reports, settings, invoicing).
7. **Phase 8:** Remove POS selling Vue from monolith; secure POS APIs; validate eCommerce untouched.
