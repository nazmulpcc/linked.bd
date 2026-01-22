# Technical Specification — Short Link Service (Laravel 12 + Inertia/Vue + MariaDB)

## 1. Architecture Overview

* **Backend:** Laravel 12 (API + Inertia controllers), Laravel Queues, Laravel Scheduler.
* **Frontend:** Inertia.js + Vue 3, TailwindCSS v4.
* **Database:** MariaDB 10.
* **Storage:** Local or S3-compatible storage for QR assets (configurable).
* **Domains:** Platform domains + user custom domains served by the same app, using host-based routing.
* **Auth:** Google OAuth only (Laravel Socialite).

---

## 2. Core Modules

### 2.1 Authentication

* Google OAuth login/logout.
* User profile minimal fields: name, email, avatar (optional), oauth provider identifiers.
* Session-based auth (standard Laravel session/cookie).

### 2.2 Domain Management

Responsible for adding/verifying user custom domains and enabling host-based resolution.

**Key responsibilities**

* Create/update/delete custom domains.
* Verification workflow (DNS-based recommended).
* Domain status lifecycle:

  * `pending_verification`
  * `verified`
  * `disabled` (admin/user action)
* Enforce ownership: a domain belongs to exactly one user.

**Host resolution**

* Incoming request hostname determines:

  * platform domain behavior (auto code only)
  * custom domain behavior (allow custom alias, only if verified)

### 2.3 Link Management

Handles link creation, listing, password protection, expiration, deletion.

**Rules**

* Platform domains: short code auto-generated only.
* Custom domains: allow custom alias; must be unique within that domain.
* Authenticated users: links persist until expired/deleted.
* Unauthenticated users: links created under “guest” mode; auto-deleted after configured days; no dashboard.

**Link states**

* `active`
* `expired` (time reached)
* `deleted` (hard deleted; represented only via audit log if you keep one)

### 2.4 Redirect Service

Fast-path handling for resolving and redirecting short links.

* Must be efficient: minimal middleware stack.
* Handles:

  * not found
  * expired/deleted
  * password prompt / validation
  * successful redirect
* Click tracking should not block response (queue-first, fallback to sync increment only if queue unavailable).

### 2.5 QR Code Service

Generates QR codes for short links.

* Store QR images (or SVG) and link them to the link record.
* Generation happens async via queue after link creation.
* Provide secure download endpoint (authenticated owner for owned links; for guest links allow download only via a signed token if needed).

### 2.6 Analytics Service (Minimal)

Per-link:

* `click_count`
* `last_accessed_at`

Data capture:

* Click events should be recorded in a lightweight way.
* Prefer aggregated increments + timestamp update. Avoid heavy per-click tables in v1 unless required for reliability auditing.

### 2.7 Cleanup & Expiry Service

* Scheduler job runs periodically (e.g., every hour).
* Hard delete:

  * expired links
  * guest links older than configured TTL
* Ensure QR assets are also deleted.

---

## 3. Data Model (MariaDB)

### 3.1 Tables

**users**

* OAuth identity fields, standard auth fields.

**domains**

* `id`
* `user_id` (nullable if platform-managed domains are represented here; otherwise omit)
* `hostname` (unique)
* `type` enum: `platform`, `custom`
* `status` enum: `pending_verification`, `verified`, `disabled`
* verification metadata (see below)
* timestamps

**links**

* `id`
* `user_id` nullable (null for guest-created)
* `domain_id` (platform or custom)
* `code` (auto short code; used on platform domains and may be used on custom too)
* `alias` nullable (only for custom domains; unique per domain)
* `destination_url`
* `password_hash` nullable
* `expires_at` nullable
* `click_count` bigint default 0
* `last_accessed_at` nullable
* `qr_path` / `qr_storage_key` nullable
* timestamps
* indexes:

  * (`domain_id`, `code`)
  * (`domain_id`, `alias`)
  * `expires_at`
  * `user_id`

**link_access_tokens (optional, only if needed)**
If you want guest links to have a manage/download page without login:

* `link_id`
* `token` unique (random)
* `expires_at` (optional)
  This enables “manage this link later” without accounts (still ephemeral).

**domain_verifications**
If you prefer separation:

* `domain_id`
* `method` enum: `dns_txt`
* `expected_value`
* `verified_at` nullable
* timestamps

### 3.2 Constraints

* Enforce uniqueness:

  * `domains.hostname` unique global.
  * For links:

    * platform: `code` unique within each platform domain.
    * custom: `alias` unique within that custom domain.
* Enforce alias policy at validation layer:

  * If `domain.type == platform` then `alias` must be null.

---

## 4. Routing & Request Flows

### 4.1 App/Dashboard Routes (Inertia)

Authenticated:

* Dashboard home (links list)
* Create link
* Domain list/manage
* Domain add + verify instructions
* Link details (optional, can be modal)
* Delete confirmation

Guest:

* Create link page (no dashboard)
* Post-create “success” page containing:

  * short URL
  * QR download
  * optional “manage token” (if implemented)

### 4.2 Public Redirect Routes

Host-based routing:

* `/{slug}` where slug resolves to:

  * custom domain: match `alias` first, else fallback to `code` if you allow both
  * platform domain: match `code`
    Password flow:
* GET `/{slug}` shows password prompt if protected (no redirect yet)
* POST `/{slug}` validates password then redirects

Error pages:

* 404 Not Found (unknown slug/domain)
* 410 Gone (expired/deleted) preferred over 404 for user clarity (optional)

---

## 5. Domain Verification (DNS TXT)

### 5.1 Workflow

1. User enters hostname (e.g., `go.example.com`)
2. System generates a verification token/value.
3. UI shows DNS instructions:

   * add a TXT record at a specific name (either root or `_shortlink.<domain>`)
4. User clicks “Verify”.
5. Backend checks DNS and marks domain verified.

### 5.2 Operational Requirements

* Verification check can be synchronous on button click.
* Additionally, a scheduled job can re-check pending domains periodically.

### 5.3 Serving Custom Domains

Assumption: custom domains are pointed (A/AAAA/CNAME) to your app/load balancer and TLS is handled at the edge (recommended). If TLS automation is not in scope, document prerequisites for the user.

---

## 6. Link Creation Rules & Validation

### 6.1 URL Validation

* Must be valid absolute URL with http/https.
* Reject javascript/data URIs.
* Optional: block internal IP ranges to reduce SSRF-style abuse.

### 6.2 Code/Alias Generation

* Code: short, URL-safe, collision-resistant.
* Collision handling: retry generation.
* Alias (custom domain only): validate allowed charset and length.

### 6.3 Expiry & Auto-Deletion

* `expires_at` optional for any link.
* Guest links also have implicit TTL:

  * derive a computed expiration (created_at + N days) or store explicit expires_at.
* Expired links are hard deleted by the cleanup job.

### 6.4 Password Protection

* Store only a secure hash (never plaintext).
* Password required only on redirect route, not on link creation.
* Optional: throttle password attempts per IP/slug.

---

## 7. Performance Strategy

### 7.1 Redirect Hot Path

* Minimal middleware.
* Resolve link via indexed lookup (domain+slug).
* Redirect response should not wait on QR generation or heavy writes.

### 7.2 Click Tracking

Preferred approach:

* On successful redirect, dispatch queue job to:

  * increment click_count
  * update last_accessed_at
    Fallback:
* if queue unavailable, do a lightweight atomic DB update.

### 7.3 QR Generation

* Always queue QR generation after link creation.
* Store generated QR artifact reference in DB.
* Allow user to view link immediately; QR may show “generating…” state.

---

## 8. Queues & Scheduler

### 8.1 Queue Jobs

* `GenerateQrForLink`
* `RecordLinkClick`
* `DeleteExpiredLinks` (can be scheduled command instead of job)
* Optional: `VerifyPendingDomains` batch re-check

Queue driver:

* Start with database queue for day one simplicity, or Redis if available.

### 8.2 Scheduled Commands

* Every hour:

  * delete expired links (hard delete)
  * delete guest links past TTL
  * delete orphan QR assets (safety sweep)
* Every 10–30 minutes (optional):

  * re-check pending domains

---

## 9. Frontend (Inertia + Vue 3)

### 9.1 Pages / Components

* Public Create Link
* Auth Landing / Sign-in with Google
* Dashboard:

  * Links list (table/card view)
  * Create link form
  * Domain management + verification instructions
* Redirect password page (public)

### 9.2 UX Requirements

* “Create link” must be single-screen, minimal fields:

  * destination URL
  * domain selector
  * alias input (only when custom domain selected)
  * password toggle + password field
  * expiry toggle + date/duration
* Post-create:

  * prominent short URL + copy button
  * QR preview + download
  * status indicators: QR ready, password enabled, expiry date

---

## 10. Design System (TailwindCSS v4)

### 10.1 Visual Style

* Modern minimalistic UI.
* Friendly colors; avoid high saturation.
* Consistent spacing, soft corners, subtle shadows.
* Dark mode support recommended (Tailwind v4 makes this straightforward).

### 10.2 Components

* Buttons: primary/secondary/ghost
* Inputs: consistent focus ring, validation states
* Cards: dashboard lists
* Toast notifications: copy success, created, deleted
* Empty states: “No links yet”

---

## 11. Security & Abuse Controls (Day-One Practical)

* Validate destination URLs strictly (block dangerous schemes).
* Optional rate limiting:

  * link creation per IP for guests
  * password attempts per IP/slug
* Protect authenticated routes with standard auth middleware.
* Ensure domain ownership validation prevents claiming someone else’s hostname.
* Ensure custom domains require verification before link creation.

---

## 12. Observability & Ops

* Basic request logging for redirect errors.
* Track queue failures (Laravel failed_jobs).
* Admin-only maintenance endpoints are out of scope; rely on logs.

---

## 13. Deployment Assumptions

* One web app serving:

  * dashboard
  * public redirect routes
* One queue worker process.
* One scheduler process (cron calling Laravel scheduler).
* Edge termination for TLS and custom domain hosting (recommended).

---

## 14. Acceptance Checklist (Mapped to Requirements)

* Google OAuth works; user can reach dashboard.
* User can add custom domain and verify via DNS TXT.
* User can create:

  * platform-domain link (auto code only)
  * custom-domain link (custom alias)
* Password-protected link requires password before redirect.
* QR generated and downloadable for each link.
* Click count increments and last_accessed_at updates.
* Expired links are removed automatically by scheduler (hard delete).
* Manual delete hard deletes immediately.

---

## 15. Implementation Phases (One-Day Execution)

1. **Foundation:** auth, layout, basic pages.
2. **Links:** create/list/delete, platform domain flow, redirect flow.
3. **Custom domains:** add + verify + enforce alias rules.
4. **Password + expiry:** prompt + validation, expiry enforcement.
5. **Queues:** click tracking + QR generation async.
6. **Scheduler:** cleanup jobs + stability checks.
7. **UI polish:** minimal design system + empty states + toasts.
