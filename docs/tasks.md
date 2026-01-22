* [x] 0. Project setup and baseline

  * [x] Initialize Laravel 12 project and environment configuration (local + production-ready env structure)
  * [x] Configure MariaDB connection and run baseline migration
  * [x] Configure Inertia + Vue 3 integration and verify page rendering
  * [x] Configure TailwindCSS v4 
  * [x] Set up global layout styles
  * [x] Set up app layout shell (navigation, container, page header, flash/toast placeholder)
  * [x] Define route groups: dashboard (auth), public (redirect/create), domain verification (auth)
  * [x] Add basic error pages/views for 404 and expired/deleted states

* [ ] 1. Authentication (Google OAuth)

  * [x] Install/configure Google OAuth provider
  * [x] Implement login flow (redirect to Google, callback handling, session login)
  * [x] Implement logout
  * [x] Create/update `users` table fields required for OAuth identity
  * [ ] Create authenticated middleware-protected dashboard entry route
  * [x] Add UI: “Continue with Google” button and minimal auth landing page
  * [ ] Acceptance: user can sign in/out and access dashboard

* [x] 2. Data model (domains, links, optional guest tokens)

  * [x] Create `domains` table (hostname, type, status, verification metadata, user ownership for custom)
  * [x] Create `links` table (domain_id, user_id nullable, code, alias nullable, destination_url, password_hash nullable, expires_at nullable, click_count, last_accessed_at, qr reference)
  * [x] Add indexes/uniqueness constraints:

    * [x] domains.hostname unique
    * [x] links (domain_id, code) indexed/unique as needed
    * [x] links (domain_id, alias) unique where alias not null
    * [x] expires_at indexed
  * [x] Add optional `link_access_tokens` table (only if implementing guest “manage link” token)
  * [x] Seed or ensure platform domain(s) exist (or configure platform domains list in config)
  * [x] Acceptance: migrations run cleanly; constraints match alias policy

* [x] 3. Domain management (custom domains + verification)

  * [x] Build dashboard UI: domain list page (status, hostname, actions)
  * [x] Build “Add domain” flow (input hostname, create pending verification record)
  * [x] Generate verification token/value and store it
  * [x] Build verification instructions UI (what TXT record to set, value to use)
  * [x] Implement “Verify now” action that checks DNS TXT and updates status to verified
  * [x] Block link creation on custom domains unless domain is verified
  * [x] Implement disable/remove domain actions (remove only if no links or handle policy)
  * [x] Acceptance: user can add a domain, verify via DNS TXT, then use it for links

* [x] 4. Link creation (platform + custom domain rules)

  * [x] Build link creation UI (single-screen form)

    * [x] Destination URL input + validation feedback
    * [x] Domain selector (platform domains + verified custom domains)
    * [x] Alias field shown only when custom domain selected
    * [x] Password toggle + password field
    * [x] Expiry toggle + expiry input (date/time or duration)
  * [x] Implement backend validation rules:

    * [x] Allow only http/https destination URLs; reject unsafe schemes
    * [x] Platform domain: alias must be empty; code auto-generated
    * [x] Custom domain: alias allowed (required if you choose); enforce allowed charset/length and uniqueness per domain
  * [x] Implement short code generation with collision retry
  * [x] Store password as secure hash (never plaintext)
  * [x] Store expiry in `expires_at` when provided
  * [x] Implement guest creation endpoint/page (no login required)

    * [x] Guest links get an implicit expiry based on configurable TTL (created_at + N days) if no explicit expiry
  * [x] Post-create success page:

    * [x] Display short URL with copy button
    * [x] Display link status (password on/off, expiry date)
    * [x] QR preview placeholder (shows “generating” until ready)
  * [x] Acceptance: platform links auto-code; custom domain links allow alias; guest links created successfully

* [x] 5. Link listing and management (dashboard)

  * [x] Build links list page (cards/table)

    * [x] Show short URL, destination URL, domain, click count, last accessed, expiry status
    * [x] Provide delete action per link
  * [x] Implement server-side pagination/sorting (minimal: newest first)
  * [x] Implement hard delete for authenticated user-owned links
  * [x] Enforce ownership checks on all link management actions
  * [x] Acceptance: user sees only their links; can delete; list updates correctly

* [x] 6. Public redirect resolution (host-based)

  * [x] Implement hostname resolution:

    * [x] Determine if request host is platform domain or verified custom domain
    * [x] If custom domain not verified/unknown, treat as not found
  * [x] Implement slug resolution:

    * [x] Platform domains: resolve by `code`
    * [x] Custom domains: resolve by `alias` (and optionally by `code` fallback if supported)
  * [x] Implement expiry checks:

    * [x] If expired -> show expired/deleted page (no redirect)
  * [x] Implement redirect response (fast path)
  * [x] Acceptance: visiting short URL redirects correctly across platform and custom domains

* [ ] 7. Password-protected links (public)

  * [ ] If link has password:

    * [ ] Show password prompt page on GET
    * [ ] Validate password on POST
    * [ ] On success, perform redirect
    * [ ] On failure, show error state (no redirect)
  * [ ] Add basic throttling for password attempts (per IP/slug) if feasible day-one
  * [ ] Acceptance: protected links never redirect without correct password

* [ ] 8. Click analytics (queue-first)

  * [ ] Define analytics update behavior:

    * [ ] Increment click_count
    * [ ] Update last_accessed_at
  * [ ] Implement `RecordLinkClick` job and dispatch it on successful redirect
  * [ ] Implement safe fallback to synchronous atomic DB update if queue not available
  * [ ] Ensure analytics not recorded for:

    * [ ] expired/deleted
    * [ ] failed password attempts
  * [ ] Acceptance: clicks increment and last_accessed_at updates without slowing redirect

* [ ] 9. QR code generation and download

  * [ ] Choose QR output format (PNG or SVG) and storage target (local/S3-configurable)
  * [ ] Implement `GenerateQrForLink` job dispatched after link creation
  * [ ] Store QR reference/path on link record
  * [ ] Implement QR retrieval endpoint:

    * [ ] Authenticated owners can download their QR
    * [ ] Guest-created links: allow download from success page (use signed URL/token if needed)
  * [ ] Update UI to show QR preview when ready and provide download action
  * [ ] Acceptance: every link produces a downloadable QR; UI reflects “generating” then “ready”

* [ ] 10. Scheduler: expiry + guest auto-deletion (hard delete)

  * [ ] Implement scheduled command/job to hard delete expired links
  * [ ] Implement scheduled command/job to hard delete guest links past configured TTL
  * [ ] Ensure QR assets removed when link is deleted/expired
  * [ ] Add safety sweep to remove orphaned QR assets (optional)
  * [ ] Configure scheduler cadence (e.g., hourly) and document required cron entry
  * [ ] Acceptance: expired and guest TTL links disappear automatically and stop working

* [ ] 11. Queue infrastructure and reliability

  * [ ] Configure queue driver for day-one (database recommended unless Redis available)
  * [ ] Create queue tables and failed jobs table
  * [ ] Document how to run queue worker(s) in production
  * [ ] Ensure QR and click jobs are idempotent enough for retries
  * [ ] Acceptance: jobs process successfully; failures are observable and retryable

* [ ] 12. UI/UX polish (minimal modern design)

  * [ ] Define friendly color palette + typography scale in Tailwind config
  * [ ] Build reusable UI components:

    * [ ] Buttons, inputs, toggles, cards, badges, empty states
    * [ ] Toast/flash messages for copy/create/delete/verify actions
  * [ ] Ensure forms have clear validation states and helper text
  * [ ] Add consistent loading states (domain verify, create link, QR generating)
  * [ ] Add dark mode support if time allows
  * [ ] Acceptance: UI is consistent, minimal, and pleasant with clear states

* [ ] 13. Validation, abuse controls, and security checks

  * [ ] Destination URL validation (block dangerous schemes; optional internal IP blocking)
  * [ ] Rate limit guest link creation (per IP) if feasible day-one
  * [ ] Ensure domain ownership enforced; prevent claiming domains already registered
  * [ ] Ensure redirect route avoids heavy middleware and leaks no sensitive info
  * [ ] Acceptance: obvious abuse vectors mitigated without adding complexity

* [ ] 14. Final verification against acceptance criteria

  * [ ] Google sign-in works; dashboard accessible
  * [ ] Custom domain add + DNS TXT verify works
  * [ ] Platform link creation works (auto code only)
  * [ ] Custom domain link creation works (custom alias)
  * [ ] Password protection flow works end-to-end
  * [ ] QR generated + downloadable
  * [ ] Click count increments + last accessed updates
  * [ ] Manual delete hard deletes and link stops working immediately
  * [ ] Scheduled expiry/guest cleanup hard deletes and links stop working
