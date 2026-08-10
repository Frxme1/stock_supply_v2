---
name: backend-code-review
description: Use only when the user explicitly requests a review or audit of backend code under `api/` or backend scripts/services. Supports pending-change, file-focused, and pasted-diff reviews. Do not use for implementation-only requests, diagnosis without review intent, or frontend code.
---

# Backend Code Review

Review the requested scope for concrete, reproducible defects. The nearest `AGENTS.md` owns package facts and commands; this skill owns the review workflow and routes to its bundled rule packs.

## Evidence First

1. Establish the requested review scope and inspect the relevant diff or files.
2. Read the changed lines, their behavior owner, nearby tests, and local docstrings or comments that define contracts.
3. Trace callers, persistence boundaries, authorization, generated schemas, or external I/O only when they decide correctness.
4. Report only findings tied to an observable failure, violated contract, security boundary, data integrity risk, or demonstrated maintenance problem.

## Review Principles

- **Security & Authorization**: Validate input sanitization, permission checks, SQL injection prevention, and safe parameter handling.
- **Database & Schema Integrity**: Check transaction boundaries, migration safety, indexing, and query efficiency (avoid N+1 queries).
- **Architecture & Boundaries**: Ensure clear separation between controllers, business logic/services, and data access layers.
- **Error Handling & Resilience**: Avoid silent exception catching, verify failure log traceability, and check resource cleanup.
