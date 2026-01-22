# Linked

Linked is a production-ready short link service built in a single day. It supports platform domains, custom domains, password-protected links, QR codes, click analytics, and automatic cleanup from day one.

This project was vibe coded with OpenAI's Codex.

## What it does

- Create short links on platform domains (auto-generated codes only).
- Add custom domains and verify via DNS TXT before use.
- Support custom aliases on verified custom domains.
- Optional password protection and expiration.
- QR code generation (SVG) with download links.
- Minimal analytics: click count and last accessed time.
- Hard deletion on expiry or manual delete (including QR cleanup).
- Guest links allowed with TTL-based auto deletion.

## Stack

- Laravel 12 + Inertia.js + Vue 3
- Tailwind CSS v4
- MariaDB
- Redis + Horizon for queues
- Endroid QR Code (SVG output)

## Workflow (how it was built)

- Start from written requirements and a technical spec.
- Break work into task groups and implement one group per branch.
- Make incremental commits per task group and run focused Pest tests.
- Merge each branch to master after confirmation.
- Track progress in `docs/tasks.md` and keep specs in `docs/`.

## Current state

- All tasks in `docs/tasks.md` are complete.
- Auth: Google OAuth only.
- Public redirects, password flows, and analytics are implemented.
- QR generation and downloads are live (SVG on local `qr_code` disk).
- Scheduler prunes expired and guest links hourly.
- Queue driver is Redis; Horizon is configured.
- UI is polished with a custom palette, typography, and loading states.

## Local setup

1. Copy `.env.example` to `.env` and set your values.
2. Install dependencies and run migrations.
3. Run the app server, queue worker (Horizon), and scheduler.

