# Invoicing, Credit Notes, Delivery Notes & Sales Reports

## Overview

Full invoicing functionality with buyer details, credit notes, delivery notes, and sales reporting. Design is ready for **ETIMS (KRA) middleware** integration without refactoring.

---

## 1. Invoices

### Buyer details (stored on invoice)

- **buyer_name**, **buyer_kra_pin**, **buyer_email**, **buyer_phone** (all optional; can be set with or without `buyer_id`)
- Unique **invoice number** (assigned on issue)
- Line items: quantity, unit price, tax (VAT), line total
- Totals: subtotal, discount, tax, total, balance

### Invoice status

- **DRAFT** — created, not sent (`status = draft`)
- **SIGNED** — ETIMS-signed (`etims_status = signed`, `etims_reference` set)
- **PAID** — fully settled (`status = paid`)

### ETIMS readiness

- **etims_status**: `pending` | `signed` | `failed`
- **etims_reference**: unique reference from ETIMS (nullable until signed)

### API (all under `auth:sanctum`)

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/invoices` | List (filter: seller_id, status, type, etims_status) |
| POST | `/api/v1/invoices` | Create (with buyer_name, buyer_kra_pin, buyer_email, buyer_phone, items, etc.) |
| GET | `/api/v1/invoices/{id}` | Show (with items, creditNotes, deliveryNotes) |
| POST | `/api/v1/invoices/{id}/issue` | Issue (assign number) |
| POST | `/api/v1/invoices/{id}/etims-signed` | Mark ETIMS signed (body: `etims_reference`) |
| POST | `/api/v1/invoices/{id}/etims-failed` | Mark ETIMS failed |
| POST | `/api/v1/invoices/{id}/allocate` | Allocate receipt to invoice |
| POST | `/api/v1/invoices/{id}/allocate-payment` | Create receipt and allocate |

---

## 2. Credit Notes

- Linked to an **invoice**; specify **amount** to credit; **reason** optional.
- Invoice **balance** is reduced; status becomes `paid` or `partially_paid` when applicable.
- **Audit trail**: all credit notes stored in `credit_notes` with number, amount, reason, `etims_status`, `etims_reference`.

### API

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/credit-notes` | List (filter: invoice_id) |
| POST | `/api/v1/credit-notes` | Create (invoice_id, amount_minor, currency, reason) |
| GET | `/api/v1/credit-notes/{id}` | Show |

---

## 3. Delivery Notes

- Linked to an **invoice**; **delivery_address** (JSON); **items** (description, quantity; optional invoice_item_id).
- **Status**: `pending` | `dispatched` | `delivered`.
- **ETIMS**: `etims_status`, `etims_reference` for future middleware.

### API

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/v1/delivery-notes` | List (filter: invoice_id) |
| POST | `/api/v1/delivery-notes` | Create (invoice_id, delivery_address, items, notes) |
| GET | `/api/v1/delivery-notes/{id}` | Show |
| PATCH | `/api/v1/delivery-notes/{id}/status` | Update status (body: status) |

---

## 4. Sales Reports

- **Daily / weekly / monthly** aggregations (order count, total sales, total VAT).
- **By source**: POS vs online (from `orders.source`).
- **Total VAT** and **payments received** in summary.
- **Voided sales**: list with sale reference, date/time, user/cashier, reason.

### API

| Method | Endpoint | Query | Description |
|--------|----------|-------|-------------|
| GET | `/api/v1/reports/sales` | start_date, end_date, period=(daily\|weekly\|monthly) | Full report + optional period aggregation |
| GET | `/api/v1/reports/sales/daily` | start_date, end_date | Daily aggregation |
| GET | `/api/v1/reports/sales/weekly` | start_date, end_date | Weekly aggregation |
| GET | `/api/v1/reports/sales/monthly` | start_date, end_date | Monthly aggregation |
| GET | `/api/v1/reports/sales/voided` | start_date, end_date | Voided sales list |

---

## 5. System Design & ETIMS Middleware Readiness

### Models

- **Invoice**: `etims_status`, `etims_reference`; buyer_name, buyer_kra_pin, buyer_email, buyer_phone.
- **CreditNote**: `etims_status`, `etims_reference`; links to invoice; audit trail.
- **DeliveryNote**: `etims_status`, `etims_reference`; delivery_address; items; status.

### Services (business logic)

- **InvoiceService**: create, issue, markSigned, markEtimsFailed.
- **CreditNoteService**: create (updates invoice balance), markSigned.
- **DeliveryNoteService**: create, updateStatus, markSigned.
- **SalesReportService**: report, dailyReport, weeklyReport, monthlyReport, voidedSales.

### Events (for future middleware)

- **InvoiceCreated** — after invoice create.
- **InvoiceSigned** — after markSigned (etims_reference).
- **CreditNoteCreated** — after credit note create.

When you add ETIMS middleware, subscribe to these events and call the external API; then call `InvoiceService::markSigned()` / `CreditNoteService::markSigned()` with the ETIMS reference.

### DTOs

- **CreateInvoiceDTO**, **CreateCreditNoteDTO**, **CreateDeliveryNoteDTO** — used by services; controllers stay thin.

---

## 6. Database

- **invoices**: new columns buyer_name, buyer_kra_pin, buyer_email, buyer_phone, etims_status, etims_reference.
- **credit_notes**: invoice_id, number, amount_minor, currency, reason, etims_status, etims_reference, meta.
- **delivery_notes**: invoice_id, number, delivery_address (JSON), status, etims_status, etims_reference, notes, meta.
- **delivery_note_items**: delivery_note_id, invoice_item_id, description, quantity.

Document numbers for credit notes and delivery notes use **DocumentNumberService** with doc_type `credit_note` and `delivery_note` (per seller, per year).

---

## 7. Code Quality

- **Thin controllers**: validation + call service + return JSON.
- **Services**: all business logic (create, issue, allocate logic, balance updates).
- **DTOs**: typed input for services.
- **Events**: emitted where relevant; listeners can be added for ETIMS without changing existing code.
- **Admin backend** is independent from POS UI; these APIs are for admin and future middleware.
