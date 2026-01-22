# Short Link Service — Non-Technical Project Requirement (v1)

## 1. Objective

Build a **production-usable short link service in one day**, using rapid “vibe coding” with OpenAI Codex.
The system must support **custom domains, QR codes, password-protected links, and automatic deletion** from day one, while remaining intentionally minimal.

---

## 2. Target Users

* Indie founders
* Marketers running ads or campaigns
* Developers sharing links with access control
* Users who want branded short links on their own domains

Enterprise features are explicitly out of scope.

---

## 3. Core Principles

* Fast to use
* Clear ownership of links
* Predictable behavior
* No feature hidden behind configuration complexity
* All features usable on day one

---

## 4. Link Creation Rules

### 4.1 Domain & Alias Policy

* **Platform-owned domains**

  * Short codes are **auto-generated only**
  * No custom aliases allowed
* **User-added custom domains**

  * Users may define **custom aliases**
  * Alias uniqueness is enforced per domain

---

### 4.2 Link Attributes (Configurable at Creation)

Each short link may have:

* Destination URL
* Optional password
* Optional expiration (date or duration)
* Optional QR code generation
* Assigned domain (platform or custom)

---

## 5. Authentication & Ownership

### 5.1 Authentication

* **Google OAuth only** in v1
* Other providers are explicitly deferred

---

### 5.2 Ownership Rules

* **Authenticated users**

  * All links are permanently owned
  * Persist until deleted or expired
* **Unauthenticated users**

  * Links are allowed
  * Automatically deleted after a configurable number of days
  * No recovery once deleted

---

## 6. Redirection Behavior

### 6.1 Standard Redirect

* Visiting a valid short URL redirects to the destination

### 6.2 Password-Protected Links

* User is prompted for a password before redirect
* Incorrect password does not redirect
* No password recovery for viewers

### 6.3 Expired or Deleted Links

* Show a simple, neutral error page
* No redirect occurs

---

## 7. Analytics (Minimal but Mandatory)

Per link:

* Total click count
* Last accessed timestamp

No geographic, device, or referrer analytics in v1.

---

## 8. QR Code Support

* QR code is generated automatically for each link
* QR code:

  * Represents the short URL
  * Is downloadable
* No customization (color, logo) in v1

---

## 9. Link Management (Dashboard)

Authenticated users can:

* View all owned links
* See:

  * Short URL
  * Destination URL
  * Domain
  * Click count
  * Expiration status
* Delete links manually

Unauthenticated users:

* No dashboard
* Links are ephemeral

---

## 10. Deletion & Expiry Rules

### 10.1 Hard Deletion

* Manual deletion removes the link permanently
* Expired links are also **hard deleted**
* Deleted links:

  * Cannot be restored
  * Stop redirecting immediately

---

## 11. Custom Domain Support (Day One)

* Users can add their own domains
* Domain verification is required
* Once verified:

  * Links can be created under that domain
  * Custom aliases are allowed

No SSL customization, DNS automation UI, or domain analytics in v1.

---

## 12. Explicitly Out of Scope (v1)

* Teams or shared workspaces
* API access
* Rate limiting UI
* Advanced analytics
* Link editing after creation
* UTM builders
* Public stats pages
* Webhooks

---

## 13. Day-One Success Criteria

The system is successful if:

1. A user signs in with Google
2. Adds a custom domain
3. Creates:

   * A platform-domain link (auto code)
   * A custom-domain link (custom alias)
4. Enables password protection and expiry
5. Downloads a QR code
6. Visits the link and sees analytics increment
7. Deletes the link and confirms it no longer works

---

## 14. Future Expansion (Acknowledged, Not Implemented)

* More OAuth providers
* API access
* Teams
* Link editing
* Analytics export
* Custom QR styling

