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

* [x] 7. Password-protected links (public)

  * [x] If link has password:

    * [x] Show password prompt page on GET
    * [x] Validate password on POST
    * [x] On success, perform redirect
    * [x] On failure, show error state (no redirect)
  * [x] Add basic throttling for password attempts (per IP/slug) if feasible day-one
  * [x] Acceptance: protected links never redirect without correct password

* [x] 8. Click analytics (queue-first)

  * [x] Define analytics update behavior:

    * [x] Increment click_count
    * [x] Update last_accessed_at
  * [x] Implement `RecordLinkClick` job and dispatch it on successful redirect
  * [x] Implement safe fallback to synchronous atomic DB update if queue not available
  * [x] Ensure analytics not recorded for:

    * [x] expired/deleted
    * [x] failed password attempts
  * [x] Acceptance: clicks increment and last_accessed_at updates without slowing redirect

* [x] 9. QR code generation and download

  * [x] Choose QR output format (PNG or SVG) and storage target (local/S3-configurable)
  * [x] Implement `GenerateQrForLink` job dispatched after link creation
  * [x] Store QR reference/path on link record
  * [x] Implement QR retrieval endpoint:

    * [x] Authenticated owners can download their QR
    * [x] Guest-created links: allow download from success page (use signed URL/token if needed)
  * [x] Update UI to show QR preview when ready and provide download action
  * [x] Acceptance: every link produces a downloadable QR; UI reflects “generating” then “ready”

* [x] 10. Scheduler: expiry + guest auto-deletion (hard delete)

  * [x] Implement scheduled command/job to hard delete expired links
  * [x] Implement scheduled command/job to hard delete guest links past configured TTL
  * [x] Ensure QR assets removed when link is deleted/expired
  * [x] Add safety sweep to remove orphaned QR assets (optional)
  * [x] Configure scheduler cadence (e.g., hourly) and document required cron entry
  * [x] Acceptance: expired and guest TTL links disappear automatically and stop working

* [x] 11. Queue infrastructure and reliability

  * [x] Configure queue driver for day-one (database recommended unless Redis available)
  * [x] Create queue tables and failed jobs table
  * [x] Document how to run queue worker(s) in production (not required)
  * [x] Ensure QR and click jobs are idempotent enough for retries
  * [x] Acceptance: jobs process successfully; failures are observable and retryable

* [x] 12. UI/UX polish (minimal modern design)

  * [x] Define friendly color palette + typography scale in Tailwind config
  * [x] Build reusable UI components:

    * [x] Buttons, inputs, toggles, cards, badges, empty states
    * [x] Toast/flash messages for copy/create/delete/verify actions
  * [x] Ensure forms have clear validation states and helper text
  * [x] Add consistent loading states (domain verify, create link, QR generating)
  * [x] Add dark mode support if time allows
  * [x] Acceptance: UI is consistent, minimal, and pleasant with clear states

* [x] 13. Validation, abuse controls, and security checks

  * [x] Destination URL validation (block dangerous schemes; optional internal IP blocking)
  * [x] Rate limit guest link creation (per IP) if feasible day-one
  * [x] Ensure domain ownership enforced; prevent claiming domains already registered
  * [x] Ensure redirect route avoids heavy middleware and leaks no sensitive info
  * [x] Acceptance: obvious abuse vectors mitigated without adding complexity

* [x] 14. Final verification against acceptance criteria

  * [x] Google sign-in works; dashboard accessible
  * [x] Custom domain add + DNS TXT verify works
  * [x] Platform link creation works (auto code only)
  * [x] Custom domain link creation works (custom alias)
  * [x] Password protection flow works end-to-end
  * [x] QR generated + downloadable
  * [x] Click count increments + last accessed updates
  * [x] Manual delete hard deletes and link stops working immediately
  * [x] Scheduled expiry/guest cleanup hard deletes and links stop working

* [x] 15. Dynamic redirect rules (data model + validation)

  * [x] Define “dynamic link” concept: a link can be either:

    * [x] Static (single destination_url)
    * [x] Dynamic (multiple destinations with ordered conditions + fallback)
  * [x] Extend `links` table to support dynamic mode flag/type and fallback destination (if not already present)
  * [x] Create `link_rules` table

    * [x] Fields: link_id, priority/order, destination_url, is_fallback (optional), enabled, timestamps
  * [x] Create `link_rule_conditions` table

    * [x] Fields: link_rule_id, condition_type, operator, value(s), timestamps
  * [x] Add indexes for fast evaluation:

    * [x] link_rules by link_id + priority
    * [x] link_rule_conditions by link_rule_id + condition_type
  * [x] Validation policies:

    * [x] Only http/https destinations
    * [x] At least one rule + exactly one fallback for dynamic links
    * [x] Rule priorities must be unique per link (or auto-normalized)
    * [x] Limit maximum rules per link (configurable) to keep evaluation fast
    * [x] Limit maximum conditions per rule (configurable)
  * [x] Acceptance: dynamic link schema supports multiple destinations, ordered evaluation, and fallback

* [x] 16. Condition system (supported signals + operators)

  * [x] Define supported condition types (day-one):

    * [x] Country (ISO code)
    * [x] Device type (mobile/desktop/tablet)
    * [x] OS (iOS/Android/Windows/macOS/Linux)
    * [x] Browser (Chrome/Safari/Firefox/Edge/Other)
    * [x] Referrer domain (exact / contains)
    * [x] Referrer path (contains / prefix)
    * [x] UTM source/medium/campaign (from query params)
    * [x] Language/locale (Accept-Language prefix)
    * [x] Time window (optional): day-of-week and/or hour range in a specified timezone
  * [x] Define operators per condition type:

    * [x] equals / not equals
    * [x] in list / not in list
    * [x] contains / not contains
    * [x] starts_with / ends_with (referrer/path)
    * [x] regex (optional; if included, restrict to referrer/path and guard complexity)
    * [x] exists / not exists (e.g., referrer present)
  * [x] Define normalization rules:

    * [x] Country stored/evaluated using ISO-3166-1 alpha-2
    * [x] Device/OS/Browser as enums
    * [x] Referrer parsed into scheme/host/path/query; store only the parts needed for matching
  * [x] Define evaluation semantics:

    * [x] AND within a rule (all conditions must match)
    * [x] Rules evaluated by ascending priority; first match wins
    * [x] Fallback rule always present and used if no match
  * [x] Acceptance: condition types and operators are consistent, validated, and predictable

* [x] 17. Redirect runtime evaluation (fast path)

  * [x] Implement a “request context” builder for redirect requests:

    * [x] Country (from existing detection pipeline)
    * [x] Device/OS/Browser (from User-Agent parsing)
    * [x] Referrer (from headers, parsed)
    * [x] UTM params (from query string)
    * [x] Language/locale (Accept-Language)
    * [x] Timestamp and day-of-week (optional)
  * [x] Implement dynamic rule resolver:

    * [x] Loads rules + conditions for a link efficiently (minimal queries; cached if feasible)
    * [x] Evaluates in priority order, returns destination_url
    * [x] Falls back when no rule matches
  * [x] Ensure password protection still applies before redirect resolution (if link protected)
  * [x] Ensure expiry/deleted logic still blocks redirect before evaluation
  * [x] Add guardrails to keep redirect fast:

    * [x] Hard caps on number of rules/conditions evaluated
    * [x] Avoid regex by default or restrict to safe patterns
  * [x] Acceptance: dynamic redirects select correct destination under multiple conditions without noticeable latency

* [x] 18. Dynamic link creation UI (separate form + rule builder)

  * [x] Add link type selector in create flow: Static vs Dynamic
  * [x] For dynamic links, show dedicated UI:

    * [x] Add rule button (creates a destination + conditions)
    * [x] Rule priority ordering (drag-drop or up/down)
    * [x] Destination URL input per rule
    * [x] Condition builder per rule:

      * [x] Add condition row
      * [x] Select condition type
      * [x] Select operator (based on type)
      * [x] Input value(s) (single, multi-select, text)
    * [x] Fallback destination input (mandatory)
  * [x] Client-side validation for common errors:

    * [x] Missing fallback
    * [x] Empty destination
    * [x] Invalid country code
    * [x] Duplicate priorities (if manual)
  * [x] Add UX features:

    * [x] Rule templates (optional quick-add): “Mobile vs Desktop”, “Country split”, “Referrer split”
    * [x] Preview evaluation panel (optional): pick a simulated context and show resulting destination
  * [x] Acceptance: user can create a dynamic link with multiple rules and fallback from the UI

* [x] 19. Dynamic link management UI (view/edit/clone)

  * [x] Update links list to indicate link type (static/dynamic)
  * [x] Add link detail page (or modal) for dynamic links:

    * [x] Show ordered rules, conditions, and destinations
    * [x] Show fallback destination
  * [x] Implement edit flow for dynamic rules:

    * [x] Add/remove rules
    * [x] Reorder rules
    * [x] Add/remove conditions
    * [x] Enable/disable specific rules
  * [x] Add “clone link” action (optional but useful):

    * [x] Duplicates rule set for quick iteration
  * [x] Acceptance: dynamic links are editable safely and remain consistent after updates

* [x] 20. Analytics integration for dynamic outcomes

  * [x] Extend click analytics payload to include:

    * [x] Resolved rule_id (or “fallback”)
    * [x] Resolved destination_url (optional; store only if needed)
  * [x] Update analytics worker/job to record:

    * [x] Click counts per link (existing)
    * [x] Click counts per rule (new aggregation)
  * [x] Add dashboard view for dynamic analytics:

    * [x] Per rule clicks
    * [x] Fallback clicks
  * [x] Acceptance: user can see which rule is being selected and how often

* [x] 21. Caching and performance optimization (dynamic rules)

  * [x] Add caching strategy for rule sets per link (e.g., cache key by link_id + updated_at)
  * [x] Invalidate cache when rules/conditions change
  * [x] Ensure cache is host-aware where needed (domain+slug resolution remains correct)
  * [x] Add feature flag/config to disable caching if troubleshooting
  * [x] Acceptance: dynamic evaluation avoids repeated DB queries under load

* [x] 22. Testing and verification scenarios (dynamic)

  * [x] Define test matrix scenarios:

    * [x] Country + device combined (US + mobile → X, else → Y)
    * [x] Referrer present vs not present
    * [x] Browser split (Safari vs others)
    * [x] UTM campaign split
    * [x] Multiple rules where first match wins
    * [x] Disabled rule skipped
    * [x] No match uses fallback
  * [ ] Add UI-driven manual test checklist page (optional) for QA
  * [x] Acceptance: dynamic redirects behave correctly across defined scenarios

* [x] 23. Safety controls for dynamic rules

  * [x] Enforce limits (configurable):

    * [x] max rules per link
    * [x] max conditions per rule
    * [x] max total conditions per link
  * [x] Validate and normalize referrer matching inputs (domain parsing, trim, lowercase)
  * [x] If regex is supported:

    * [x] Restrict to referrer/path only
    * [x] Add max length and reject catastrophic patterns (or disable regex entirely for v1)
  * [x] Acceptance: dynamic feature cannot degrade redirect performance or be abused easily

* [x] 24. API foundations (Sanctum) and access scope

  * [x] Define API surface for v1 (endpoints required)

    * [x] Links: create, list, delete, get details
    * [x] QR: download (or get URL)
    * [x] Domains: create, list, delete/disable, verify, get details
    * [x] Bulk import: create job, job status, job items list
  * [x] Install/configure Laravel Sanctum for token auth
  * [x] Define token abilities/scopes (minimum set)

    * [x] links:read, links:write
    * [x] domains:read, domains:write
    * [x] bulk:read, bulk:write
  * [x] Implement API auth middleware and consistent error format
  * [x] Acceptance: authenticated API requests work using personal access tokens and scopes

* [x] 25. API token management UI (dashboard)

  * [x] Create UI page: API Tokens

    * [x] List tokens (name, created_at, last_used_at, scopes)
    * [x] Create token (name + scopes selection)
    * [x] Revoke token
  * [x] One-time token display on creation (copy UX + warning)
  * [x] Ensure only token owner can manage tokens
  * [x] Acceptance: user can create/revoke tokens and use them for API calls

* [x] 26. API endpoints: links/domains/QR parity with UI

  * [x] Implement Links API

    * [x] Create static link
    * [x] Create dynamic link (if dynamic rules are enabled)
    * [x] List links (pagination + filters minimal)
    * [x] Delete link
    * [x] Get link details (include QR status/url)
  * [x] Implement Domains API

    * [x] Create domain (pending)
    * [x] List domains
    * [x] Verify domain
    * [x] Disable/delete domain (policy-consistent)
  * [x] Implement QR API

    * [x] Get QR metadata/status
    * [x] Download QR (or return signed URL)
  * [x] Acceptance: API supports all required operations with scope enforcement

* [x] 27. Bulk shorten UX (dashboard)

  * [x] Add “Bulk Shorten” entry point in dashboard
  * [x] Build bulk shorten form:

    * [x] Large textarea; one URL per line
    * [x] Domain selector (platform + verified custom domains)
    * [x] Optional defaults: password, expiry (apply to all or disabled for bulk v1)
    * [x] Validation summary: count lines, show invalid lines, deduplicate option
  * [x] Add “Start Bulk Shorten” action that creates a bulk import job and redirects to job page
  * [x] Acceptance: user can paste many URLs, submit, and be redirected to a job page

* [x] 28. Bulk import job model (DB) and lifecycle

  * [x] Create `bulk_import_jobs` table

    * [x] Fields: id, user_id, domain_id, status, total_count, processed_count, success_count, failed_count, started_at, finished_at, timestamps
  * [x] Create `bulk_import_items` table

    * [x] Fields: job_id, row_number, source_url, status, link_id nullable, error_message nullable, qr_status, timestamps
  * [x] Define job statuses:

    * [x] pending, running, completed, completed_with_errors, failed, cancelled (optional)
  * [x] Define item statuses:

    * [x] queued, processing, succeeded, failed
  * [x] Acceptance: a submitted bulk request produces a job and items rows representing each input line

* [x] 29. Bulk processing pipeline (queues + idempotency)

  * [x] Create job dispatch flow:

    * [x] Upon bulk submission, enqueue a “ProcessBulkImportJob” (or chunked jobs)
  * [x] Implement processing strategy:

    * [x] Parse + normalize URLs
    * [x] Validate scheme and safety rules
    * [x] Create link (code/alias rules same as normal create)
    * [x] Enqueue QR generation per created link (existing worker)
    * [x] Update item row with short URL/link_id and status
    * [x] Update aggregate counters on bulk_import_jobs
  * [x] Chunking/backpressure:

    * [x] Process items in batches to avoid long-running single jobs
    * [x] Ensure retries do not duplicate links (idempotent handling per item)
  * [x] Failure handling:

    * [x] Record error_message per item
    * [x] Continue processing other items
    * [x] Finalize job status correctly
  * [x] Acceptance: bulk jobs reliably process large inputs without timeouts; failures are per-row

* [x] 30. Bulk job page UI (real-time updates)

  * [x] Create bulk job detail page:

    * [x] Job header: status, counts, progress indicator
    * [x] Table: one row per item with columns:

      * [x] Row number
      * [x] Long URL
      * [x] Short URL (when ready)
      * [x] QR status / QR preview (when ready)
      * [x] Error (if failed)
  * [x] Implement “live updates” transport:

    * [ ] Option A: polling endpoint (simple day-one)
    * [x] Option B: websockets/broadcasting (only if already present)
  * [x] Implement incremental UI update behavior:

    * [x] Refresh changed rows only
    * [x] Show “QR generating” until QR ready
  * [x] Add actions:

    * [x] Download QR for a row (when ready)
    * [ ] Copy short URL
    * [ ] Export results (CSV) (optional but high value)
  * [x] Acceptance: job page updates as links/QRs are generated; user sees progress in near real-time

* [x] 31. Bulk jobs list and navigation

  * [x] Create “Bulk Imports” list page:

    * [x] Show recent jobs, status, counts, created_at
    * [x] Link to job detail
  * [x] Add dashboard navigation entry
  * [x] Acceptance: user can find past bulk jobs and open their status pages

* [ ] 32. Bulk import API endpoints

  * [ ] Create Bulk Job API

    * [ ] Create job with list of URLs (array or newline string)
    * [ ] Get job status (counts + status)
    * [ ] List job items (paginated)
  * [ ] Enforce scopes: bulk:read / bulk:write
  * [ ] Acceptance: bulk import can be driven entirely via API

* [ ] 33. API documentation (OpenAPI) and docs page

  * [ ] Decide docs approach:

    * [ ] OpenAPI spec file maintained in repo
    * [ ] UI renderer (Swagger UI / Redoc) served at a docs route
  * [ ] Implement docs page (authenticated or public—choose one; default: authenticated)
  * [ ] Document authentication:

    * [ ] How to create token
    * [ ] How to pass token (Authorization header)
    * [ ] Scopes/abilities
  * [ ] Document key endpoints with request/response schemas:

    * [ ] Links, Domains, QR, Bulk Jobs
  * [ ] Keep docs aligned with API behavior (versioning note)
  * [ ] Acceptance: user can view API docs and successfully call endpoints using described auth

* [ ] 34. Permissions, consistency, and UX polish for bulk + API

  * [ ] Ensure ownership enforcement across:

    * [ ] Links created by bulk job
    * [ ] Bulk job visibility
    * [ ] QR downloads
  * [ ] Add rate limits:

    * [ ] Bulk submission per user/token
    * [ ] API calls per token (basic)
  * [ ] Add clear UI empty/error states:

    * [ ] Bulk validation errors (invalid lines)
    * [ ] Job failed/cancelled states
    * [ ] Token creation/revocation confirmations
  * [ ] Acceptance: bulk + API features are safe, consistent, and user-friendly
