---
description: "Use when reporting implementation results in PayrollSystem. Enforce exact changed-line reporting for every code-edit task."
name: "Payroll Reporting Format"
applyTo: "**"
---
# Reporting Format

When code is created or modified, always include:

- A short outcome summary.
- A list of changed files.
- Exact line numbers for added/updated code in each changed file.
- Validation commands run and their result (if any).
- Any blockers or assumptions.

For line references, prefer ranges when appropriate (for example: `#L12-L28`).