---
name: autoreview
description: "Pre-commit/ship code review: Codex default; optional Claude or Pi."
---

# Auto Review - Pre-Commit Code Review

Run structured code review as a closeout check before committing, merging, or shipping changes.

## Core Rules

1. **Focus on P0 Blockers**: Report issues worth blocking the current change because they materially break normal flow, outcome, or safety boundaries.
2. **Verify Every Finding**: Read real code paths, callers, and adjacent files before accepting reviewer feedback. Never blindly apply review feedback.
3. **Scope Control**: Classify findings as in-scope blockers, follow-ups, or stop-and-escalate items. Prevent scope creep during review.
4. **Root-Cause Focus**: Prefer clean refactors that remove bug classes over symptom-patching.
