---
name: auto-qa
description: "Continuously audit, live-test, and stress-test the current codebase across multiple subsystem lanes; default to independently verified, landed root-cause fixes, maintain an evidence-backed report, and announce results immediately."
---

# Auto QA - Continuous Code Audit & Testing

Run a continuous code quality campaign. Treat findings as hypotheses, passing tests as evidence, and fixes as complete only when verified. Always prefer a clean, appropriately scoped root-cause refactor over a quick fix or smaller diff.

## Core Rules

1. **Root-Cause Focus**: Identify broken ownership boundaries, abstraction flaws, state transition issues, or contract violations.
2. **Clean Refactors over Quick Fixes**: Avoid masking symptoms, swallowing exceptions, or adding one-off patches. Fix the actual owner of the logic.
3. **Evidence-Based Audit**: Report only findings backed by empirical evidence, reproducible steps, or contract violations.
4. **Subsystem Lane Testing**: Group testing by subsystem (UI/UX, Backend APIs, Database Schemas, CLI utilities, Integration contracts).
