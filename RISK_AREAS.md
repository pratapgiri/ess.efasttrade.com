# Risk Areas — ESS / HRM

Areas where changes are likely to break production, security, or tenant isolation. Use this before refactoring or adding features.

**Legend:** Severity — **Critical** | **High** | **Medium** | **Low**

---

## 1. Fragile Modules

### Monolithic routing (`routes/web.php` ~1,362 lines)

| Severity | **High** |
|----------|----------|
| Risk | Duplicate route names, middleware ordering mistakes, accidental public exposure of HR routes |
| Mitigation | Grep for route name before adding; copy existing route group structure exactly |

### Fat controllers

| Severity | **High** |
|----------|----------|
| Examples | `EmployeeController` (1300+ lines), likely similar payroll/recruitment controllers |
| Risk | Unintended side effects; untested branches; duplicate validation |
| Mitigation | Minimal diffs; extract to Service only when reuse is clear; add Feature test for changed workflow |

### `app/Helpers/helper.php`

| Severity | **Critical** |
|----------|----------|
| Risk | Global functions used across payments, storage, tenancy, mail — signature changes break entire app |
| Mitigation | Add new functions; avoid renaming; grep whole project before changing behavior |

### `AutoApplyPermissionCheck` trait

| Severity | **Critical** |
|----------|----------|
| Risk | Wrong scope leaks cross-tenant data or hides records incorrectly |
| Notes | Large `switch` for employee role; duplicate case labels (e.g. `Resignation` twice); references `ContractAmendment` model may not exist |
| Mitigation | Mirror scoping in controller if trait not used; test with company, employee, and superadmin users |

### Payment gateway sprawl (~33 controllers)

| Severity | **High** |
|----------|----------|
| Risk | CSRF exceptions, callback URLs, and demo middleware patterns are per-gateway |
| Mitigation | Copy from nearest gateway; update `bootstrap/app.php` CSRF except list; test callback in staging |

### `app-sidebar.tsx` (~1100+ lines)

| Severity | **Medium** |
|----------|----------|
| Risk | Nav item without matching permission confuses users; wrong permission string hides modules |
| Mitigation | Sync permission names with `PermissionSeeder` and route middleware |

---

## 2. Tight Coupling

| Coupling | Impact |
|----------|--------|
| `User` ↔ `Employee` | Employees are `User` with `type=employee` plus `employees` row — create/update/delete must touch both |
| Permissions ↔ Routes ↔ Sidebar ↔ Seeders | Renaming one `manage-*` string requires four-place update |
| Settings in DB ↔ `settings()` helper ↔ Inertia `globalSettings` | Inconsistent reads if bypassing helper |
| SaaS plans ↔ `UserObserver` ↔ `CheckPlanAccess` | Plan expiry logs users out mid-session |
| Email templates ↔ `HrEventNotificationService` | Template name strings are hardcoded in `TEMPLATE_MAP` |
| Inertia shared props ↔ all React pages | Changing `auth` shape breaks authorization utilities |

---

## 3. Legacy / Inconsistent Patterns

| Pattern | Issue |
|---------|--------|
| `Employee` extends `Model`, not `BaseModel` | Permission scope trait not applied at model level; controller does manual scoping |
| ~80 models use `BaseModel`, ~40 use plain `Model` | Inconsistent automatic scoping |
| Validation | Most controllers inline; only ~9 FormRequests |
| `permissions.ts` vs `authorization.ts` | Two frontend permission APIs — easy to import wrong one |
| `as any` on `usePage().props` | Runtime prop mismatches not caught by TS |
| i18n forces `dir=ltr` in `i18n.js` while LayoutContext supports RTL | RTL layout may be partial |
| `delete_status` on User only | Not a standard soft-delete; easy to assume SoftDeletes |
| `PublicFormController` | Exists but unwired — dead code path if “fixed” without routes |
| `DailyTimesheet` models | Possible missing migration — DB errors on use (**Medium confidence**) |
| `EmailTemplateService` references `Business` model | May be stale from another product fork (**needs verify before use**) |

---

## 4. Dangerous Patterns

| Pattern | Severity | Detail |
|---------|----------|--------|
| Dual authorization (middleware + `can()`) | **High** | Mismatch if only one is updated |
| Superadmin bypass in custom middleware | **Critical** | Correct for superadmin; catastrophic if `isSuperAdmin()` wrong |
| CSRF exceptions for payments | **Critical** | Required for webhooks; expanding list increases attack surface |
| `DemoModeMiddleware` URI pattern matching | **Medium** | `str_contains` on paths — false positives/negatives possible |
| `CheckPlanAccess` logs out non-company users | **High** | Employee users under expired company company lose access abruptly |
| Commented `DynamicStorageService` boot | **Medium** | Enabling without DB ready breaks boot (try/catch exists) |
| `UserObserver` raw `User::where()->update()` for referral | **Low** | Bypasses model events intentionally — don’t “fix” to `save()` without SQL Server context |
| Email templates route without auth middleware | **High** | `email-templates` routes at lines 218–222 in `web.php` are outside typical permission groups — verify exposure |
| Installer routes | **High** | `install/*` CSRF exempt — must not remain exposed post-install |

---

## 5. Performance Bottlenecks

| Area | Risk |
|------|------|
| Unbounded queries | Any `->get()` without `created_by` scope on large tables |
| Employee index | Multiple `whereHas` filters + pagination — OK if indexed; bad without DB indexes on `employee_id`, `department_id` |
| N+1 relations | Missing `with()` on busy Inertia pages |
| Full permission list on every request | `getAllPermissions()` in HandleInertiaRequests — grows with custom permissions |
| Media batch upload | CSRF-exempt batch endpoint — large uploads can stress disk |
| Payroll / payslip bulk | Named in demo restrictions — likely heavy CPU/PDF |
| No queue jobs | Long operations run synchronously in HTTP request |
| 202 page components | Large JS bundle — mitigated partially by Vite chunks |

---

## 6. Security Risks

| Risk | Severity | Notes |
|------|----------|-------|
| Tenant isolation via `created_by` only | **Critical** | Missing `whereIn` = data leak; superadmin sees all by design |
| Session fixation / auth | **Medium** | Standard Laravel — keep framework updated |
| Impersonation | **High** | Superadmin feature — protect superadmin accounts |
| File upload helpers | **High** | Validate mime/size in controller; storage path traversal via `get_file` |
| Payment callbacks | **High** | Must validate signatures per gateway (verify each controller) |
| IP restriction feature | **Medium** | Misconfiguration locks out admins |
| Google 2FA fields on User | **Medium** | Ensure not exposed in Inertia props unnecessarily |
| `.env` in workspace | **Critical** | Never commit; contains live credentials |
| `phpinfo.php` in project root | **Critical** | Remove from production deployments if present |
| LaraBug / debug | **High** | `APP_DEBUG=true` in production exposes stack traces |

---

## 7. Production Break Zones

Changes here often cause immediate outages:

1. **`bootstrap/app.php`** — middleware order, CSRF except, aliases  
2. **`HandleInertiaRequests::share()`** — all React pages  
3. **`PermissionSeeder` / roles** — authorization for every module  
4. **`storage/installed`** — app won’t boot to normal mode without it  
5. **Migrations on live DB** — 126 migrations; test rollback/alters on copy  
6. **`config/app.php` `is_saas`** — toggles feature set and User fillable  
7. **Payment success handlers** — `processPaymentSuccess()`, plan assignment  
8. **Leave accrual commands** — wrong cron = wrong balances (financial/legal risk)  
9. **Composer autoload `files` helper.php** — syntax error whites entire app  
10. **Vite build / `public/build`** — missing assets break all pages  

---

## 8. Testing Gaps

| Gap | Risk |
|-----|------|
| ~17 tests vs 163 controllers | Most HR modules untested |
| HR controllers not covered | Regressions found only manually |
| Payment tests | Limited to Stripe/Razorpay settings |
| No browser/E2E in repo | UI permission bugs slip through |

**Recommendation:** Add Pest tests for any change to leave balance, payroll totals, or permission scoping.

---

## 9. Environment / DevOps Unknowns

| Item | Confidence |
|------|------------|
| CI/CD pipeline | **None in repo** |
| `.env.example` | **Not found** — onboarding relies on installer or manual `.env` |
| Git history | **No commits** on current branch |
| Cron for Artisan commands | **Unknown** — must be configured on server |
| Queue workers | **Unknown** — default database queue unused without workers |
| Deployment | Root `index.php` + Laragon suggests Apache/vhost document root may be project root or `public/` — verify (**Low confidence**) |

---

## 10. Change Checklist (before merge)

- [ ] Permission added to seeder + role assignments + route middleware + sidebar + frontend `hasPermission`
- [ ] Queries scoped with `getCompanyAndUsersId()` where applicable
- [ ] Superadmin, company, employee roles manually tested
- [ ] SaaS: plan limits and `plan.access` tested when `IS_SAAS=true`
- [ ] Demo mode: destructive actions blocked when `IS_DEMO=true`
- [ ] CSRF: new external POST route added to except list only if required
- [ ] Migration tested on copy of production DB
- [ ] `npm run build` succeeds after frontend changes

---

See `QUICK_START_FOR_NEW_DEVELOPER.md` for safe feature workflow.
