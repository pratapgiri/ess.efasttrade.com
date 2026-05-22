# Cursor Rules Suggestions — ESS / HRM

Copy sections below into `.cursor/rules/*.mdc` as needed. Rules reflect **existing** patterns, not ideal architecture.

---

## 1. Project Context (always apply)

```markdown
# ESS HRM — Project Context

This is a Laravel 12 + Inertia + React 19 HRM/ESS application with optional SaaS billing.

- Backend: PHP 8.2, Eloquent, Spatie Permission, session auth (no routes/api.php).
- Frontend: TypeScript, shadcn/ui, Tailwind v4, Ziggy `route()` helper.
- Tenancy: `created_by` + `getCompanyAndUsersId()` — NOT multi-database.
- Do NOT introduce repository layers, DTO packages, or REST API routes unless explicitly requested.
- Read PROJECT_OVERVIEW.md, RISK_AREAS.md before large changes.
```

---

## 2. Code Style Rules

```markdown
# PHP Style

- Match existing controller style: explicit permission checks with `Auth::user()->can()` AND route `permission:` middleware.
- Use `__()` for user-facing strings.
- Prefer `whereIn('created_by', getCompanyAndUsersId())` for company-scoped queries.
- Use existing helpers from `app/Helpers/helper.php` (`settings()`, `get_file()`, `upload_file()`, `formatDateTime()`, `isSaas()`, `isDemo()`).
- Run Laravel Pint only if project already uses it for touched files; match surrounding formatting.
- Avoid new global helpers; extend `helper.php` only when used in 3+ places.

# TypeScript/React Style

- Path alias: `@/` → `resources/js/`.
- Use `PageTemplate` for authenticated pages.
- Use `hasPermission` from `@/utils/authorization` (NOT `@/utils/permissions` in new pages — hook version is settings-only).
- Use `route('name')` from Ziggy, never hardcode URLs.
- Use `usePage().props` with pragmatic typing; avoid large refactors to strict types unless requested.
- Toast via `@/components/custom-toast`.
- i18n: `useTranslation()` or `t()` from `@/utils/i18n` in configs.
```

---

## 3. File Structure Rules

```markdown
# Where to Put New Code

| Artifact | Location |
|----------|----------|
| HR feature controller | `app/Http/Controllers/{Entity}Controller.php` |
| HR Inertia page | `resources/js/pages/hr/{kebab-module}/index.tsx` (+ create/edit if needed) |
| Admin CRUD (simple) | Consider `resources/js/config/crud/{entity}.ts` + `PageCrudWrapper` |
| Form request (optional) | `app/Http/Requests/{Entity}Request.php` — only if following nearby modules |
| Model (company-scoped) | `app/Models/{Entity}.php` extending `BaseModel` when using permission scopes |
| Service (complex logic) | `app/Services/{Name}Service.php` — only if logic is reused or very large |
| Migration | `database/migrations/YYYY_MM_DD_HHMMSS_description.php` |
| Seeder permission | `database/seeders/PermissionSeeder.php` + `RoleSeeder.php` |
| Routes | `routes/web.php` inside `auth` → `plan.access` group with `hr.` prefix |

# Do NOT create

- `app/Repositories/`
- `routes/api.php` entries without explicit approval
- Duplicate payment controllers (extend existing gateway pattern)
```

---

## 4. Naming Rules

```markdown
# Naming Conventions (observed)

## PHP
- Controllers: `{Entity}Controller`, PascalCase
- Models: singular PascalCase (`LeaveApplication`)
- Tables: snake_case plural (`leave_applications`)
- Route names: `hr.{kebab-resource}.{action}` (e.g. `hr.leave-applications.index`)
- Permissions: kebab-case verbs: `manage-employees`, `create-employees`, `manage-any-leave-applications`, `manage-own-leave-applications`, `access-{module}-module`
- Middleware aliases: `plan.access`, `checksaas`, `setting`, `verified`

## TypeScript
- Pages: `resources/js/pages/hr/leave-applications/index.tsx` (match Inertia render string)
- Components: PascalCase files in `components/`
- CRUD configs: `{entity}Config` in `config/crud/{entity}.ts`

## Database
- Foreign keys: `{entity}_id` (`employee_id`, `department_id`)
- Ownership: `created_by` (user id)
- Status fields: string enums like `active`, `pending`, `approved` (check sibling migrations)
```

---

## 5. API / Route Rules

```markdown
# Routing Rules

- All new authenticated HR routes go in `routes/web.php` under:
  `Route::middleware(['auth', 'verified', 'setting'])->group` → `plan.access` subgroup.
- Add `->middleware('permission:manage-{resource}')` on each route; mirror with `can()` in controller.
- Use named routes for every route; frontend must use Ziggy `route()`.
- Public routes (careers, payments) stay outside `auth` group; payment callbacks need CSRF exception in `bootstrap/app.php` if POST from external gateways.
- Return Inertia pages: `Inertia::render('hr/{module}/index', $props)`.
- Errors: `redirect()->back()->with('error', __('Permission Denied.'))` or validation errors via Inertia/shared flash.
- JSON responses only for existing API-style endpoints (media, payments, chatgpt).

# Request/Response

- Paginate with `$request->per_page ?? 10`
- Accept filters via `$request->all([...])` passed back to frontend as `filters` prop
- Validate sort fields against allowlists before `orderBy`
```

---

## 6. Validation Rules

```markdown
# Validation

- Many controllers use inline `Validator::make()` — acceptable for consistency with neighbors.
- FormRequest classes exist for: User, Role, Permission, Branch, Coupon, Category — use for **admin** modules when adding similar entities.
- Always scope `unique` rules with tenant context when applicable.
- File uploads: use `upload_file()` / `check_file()` / `get_file()` helpers, not raw Storage in new code unless following MediaController pattern.
- SaaS: check `isSaas()` and plan limits (`max_employees`) before creating employees/users.
```

---

## 7. Database Rules

```markdown
# Database

- Add `created_by` to new company-scoped tables; set from `Auth::id()` or `createdBy()` on create.
- Extend `BaseModel` for entities listed in permission scope trait (HR modules).
- Register new permissions in seeder; run migration for pivot tables if many-to-many.
- Do NOT add SoftDeletes unless product owner requests — project uses hard deletes / `delete_status` on users only.
- Follow existing migration naming: `create_{table}_table` or `add_{column}_{table}_table`.
- Seeders: add to `DatabaseSeeder` in both demo and non-demo branches if required for production bootstrapping.

# Queries

- Company scope: `whereIn('created_by', getCompanyAndUsersId())`
- Employee self-scope: check `manage-own-*` vs `manage-any-*` like `LeaveApplicationController::index`
- Superadmin: no `created_by` filter (or use `AutoApplyPermissionCheck` which bypasses)
```

---

## 8. Security Rules

```markdown
# Security

- Never skip `permission:` middleware on HR routes.
- Assume frontend permission checks are cosmetic only.
- Superadmin bypass is only via `SuperAdmin*Middleware` — do not duplicate bypass logic in controllers.
- Respect `DemoModeMiddleware` — do not enable destructive routes in demo without checking `isDemo()`.
- CSRF: ensure axios uses meta token (`resources/js/utils/axios-config.ts`).
- Do not commit `.env`, credentials, or `storage/` secrets.
- Payment webhooks: keep CSRF-exempt list minimal; document new entries in `bootstrap/app.php`.
- Use `$request->validate()` or FormRequest for all public forms (career apply, contact).
- SQL: use Eloquent/query builder; no raw concatenated user input.
```

---

## 9. Performance Rules

```markdown
# Performance

- Eager-load relations used in Inertia lists (`with(['employee', 'leaveType'])` pattern).
- Paginate all index endpoints; avoid `->get()` on unbounded HR tables.
- Use `select()` limited columns for dropdown/filter data.
- Avoid N+1 in `CrudTable` renderers — transform in controller when possible.
- Queue: prefer existing Artisan commands for heavy batch work; if adding jobs, register in `config/queue.php` and document cron.
- Vite: lazy-load large page components if adding heavy charts (see `app.tsx` Suspense pattern).
```

---

## 10. Refactoring Restrictions

```markdown
# DO NOT BREAK — Refactoring Guardrails

1. Do NOT split `routes/web.php` without coordinated team decision — tooling depends on single file.
2. Do NOT replace Spatie middleware with defaults — superadmin bypass will break.
3. Do NOT change `helper.php` function signatures used globally.
4. Do NOT rename permission strings without updating PermissionSeeder, roles, frontend `hasPermission`, and sidebar (`app-sidebar.tsx`).
5. Do NOT migrate Employee to BaseModel without auditing all Employee queries (Employee extends Model today).
6. Do NOT remove `storage/installed` check or installer middleware without replacement.
7. Do NOT unify payment gateways into one controller — each gateway has distinct callback URLs whitelisted in CSRF config.
8. Do NOT enable `DynamicStorageService::configureDynamicDisks()` in boot without testing all environments (currently commented out).
9. Preserve Inertia shared props shape in `HandleInertiaRequests` — frontend depends on `auth.permissions`, `globalSettings`, `csrf_token`.
10. Keep `envato.workdo.io` script block in `app.tsx` if upstream template expects it.
```

---

## 11. Reusable Component Rules

```markdown
# Frontend Components

## Prefer existing
- Lists: `CrudTable`, `SearchAndFilterBar`, `PageTemplate`
- Modals: `CrudFormModal`, `CrudDeleteModal`, `ModalStackProvider` + `useStackedModal`
- Forms: shadcn `Input`, `Select`, `DatePicker`, `RichTextEditor`
- Admin simple CRUD: `PageCrudWrapper` + `config/crud/*.ts`

## When building new HR pages
- Copy nearest neighbor module (e.g. leave-applications for approval workflows).
- Pass `filters` back to frontend for state preservation.
- Gate actions with `hasPermission(permissions, '...')` matching backend permission names exactly.

## Sidebar
- New nav items require updates to `app-sidebar.tsx` AND backend permissions.
- Superadmin nav: `getSuperAdminNavItems()`; company: `getCompanyNavItems()`.
```

---

## 12. SaaS & Payments Rules

```markdown
# SaaS

- Wrap SaaS-only features with `isSaas()` in backend and `globalSettings.is_saas` / props on frontend.
- Plan limits: check `$user->plan->max_employees` before employee creation.
- `plan.access` middleware handles expired plans — do not bypass for company users.

# Payments

- New gateway = new controller + routes in `checksaas` group + CSRF exceptions + frontend form in `components/payment/`.
- Use `processPaymentSuccess()`, `createPlanOrder()` helpers for plan purchases.
```

---

## 13. Testing Rules

```markdown
# Tests

- Framework: Pest (`tests/Feature`, `tests/Unit`).
- New business logic for leave/accrual/plans should get Feature tests (see `MonthlyPaidLeaveAccrualTest`, `PlanAccessTest`).
- Use sqlite in-memory env from phpunit.xml.
- Do not add trivial tests that only assert routes exist.
```

---

## Suggested `.cursor/rules` file split

| File | Contents |
|------|----------|
| `00-project-context.mdc` | Section 1 (alwaysApply: true) |
| `10-php-laravel.mdc` | Sections 2, 4, 6, 7, 8 (globs: `app/**/*.php`, `routes/**/*.php`, `database/**/*`) |
| `20-react-inertia.mdc` | Sections 2, 11 (globs: `resources/js/**/*`) |
| `30-guardrails.mdc` | Sections 10, 8 (alwaysApply: true) |

Adjust globs to match your Cursor rules format (`description`, `globs`, `alwaysApply`).
