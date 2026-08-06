# Deliverable 12 — Frontend Code Review Checklist

Every UI pull request should verify the following.

---

## Critical

- [ ] Uses approved Blade components / does not duplicate primitives
- [ ] Matches a [page template](../design/page-templates.md) (or justified exception)
- [ ] Design tokens / approved Tailwind colors — no random hex sprawl
- [ ] Layout spacing follows standards
- [ ] Permission-aware actions (hidden when unauthorized)
- [ ] Loading / error / empty states handled
- [ ] No secrets or PII leaked into `@js` / Alpine stores
- [ ] CSRF preserved on mutating requests

---

## Architecture

- [ ] Follows [folder-architecture.md](./folder-architecture.md) placement
- [ ] Naming matches [naming-conventions.md](./naming-conventions.md)
- [ ] Alpine usage justified per [alpine-standards.md](./alpine-standards.md)
- [ ] No new heavy JS framework introduced
- [ ] Controllers authorize; Blade is not the only gate

---

## Design system

- [ ] Typography/spacing consistent with [../design/](../design/)
- [ ] Buttons/badges via variants not one-off classes
- [ ] Passes relevant items in [../design/design-review-checklist.md](../design/design-review-checklist.md)

---

## Responsive

- [ ] Mobile-first utilities
- [ ] Sidebar/drawer OK below `lg`
- [ ] Forms/tables usable at 360px
- [ ] No unintended horizontal page scroll

---

## Accessibility

- [ ] Semantic HTML for controls
- [ ] Focus visible; modal/dropdown keyboard OK
- [ ] Labels/errors wired
- [ ] `motion-reduce` considered if animated

---

## Performance

- [ ] No global import of huge libs without need
- [ ] Images/icons optimized
- [ ] Listings paginated
- [ ] Dashboard widgets isolated on failure

---

## Product / IA

- [ ] Glossary labels
- [ ] Correct workspace/nav placement
- [ ] Cross-links use context patterns when applicable

---

## Reviewer notes

```
Breakpoints tested:
Axe/keyboard:
Exceptions:
```

**Approve only if Critical is complete.**
