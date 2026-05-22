# Quick Start for New Developers — ESS / HRM

Get the app running locally and learn where to work without breaking tenant isolation or permissions.

---

## Prerequisites

| Tool | Version |
|------|---------|
| PHP | 8.2+ |
| Composer | 2.x |
| Node.js | 18+ (22 recommended per `package.json` engines implicit) |
| MySQL/MariaDB or SQLite | Production likely MySQL (Laragon default) |
| Laragon (optional) | Matches workspace path `c:\laragon\www\ess.efasttrade.com` |

---

## First-Time Setup

### 1. Environment file

There is **no `.env.example` in the repo** (only `.env` may exist locally). For a fresh clone:

- Copy from team documentation or duplicate `.env` and set:
  - `APP_KEY` — `php artisan key:generate`
  - `APP_URL` — e.g. `http://ess.efasttrade.com.test`
  - `DB_*` — database credentials
  - `IS_SAAS=true` / `IS_DEMO=false` (adjust per environment)
  - `FILESYSTEM_DISK=public`

**Confidence:** High that `.env` is required; Low on exact variable list without installer.

### 2. Install dependencies

```bash
cd c:\laragon\www\ess.efasttrade.com
composer install
npm install
```

### 3. Database

```bash
php artisan migrate
php artisan db:seed
```

- **Demo data:** set `IS_DEMO=true` in `.env` before seeding for full sample HR dataset (`DatabaseSeeder`).
- **Production-like:** `IS_DEMO=false` seeds permissions, roles, superadmin, default company, templates only.

### 4. Installer marker

If the app redirects to install wizard, ensure:

```bash
# After successful install OR manual dev setup:
# touch storage/installed  (or run installer)
```

`CheckInstallation` middleware blocks the app until `storage/installed` exists.

### 5. Storage link

```bash
php artisan storage:link
```

Uploads use `public` disk and helpers `upload_file()` / `get_file()`.

---

## Running the Application

### Recommended (full dev stack)

```bash
composer dev
```

Runs concurrently (from `composer.json`):

- `php artisan serve`
- `php artisan queue:listen --tries=1`
- `php artisan pail` (logs)
- `npm run dev` (Vite)

### Manual split terminals

```bash
# Terminal 1
php artisan serve

# Terminal 2
npm run dev

# Terminal 3 (optional)
php artisan queue:listen
```

### Production build

```bash
npm run build
# Optional SSR:
npm run build:ssr
composer dev:ssr
```

### Laragon

- Point vhost document root to **`public/`** (preferred) or verify root `index.php` setup matches your Apache config.
- Enable `mod_rewrite`; `.htaccess` exists in `public/`.

---

## Important Folders

| Path | What you do here |
|------|------------------|
| `app/Http/Controllers/` | Request handling, Inertia renders |
| `app/Models/` | Eloquent, relationships |
| `app/Services/` | Shared business logic (notifications, mail, storage) |
| `app/Helpers/helper.php` | Global helpers — read before duplicating logic |
| `routes/web.php` | **All** main routes |
| `routes/settings.php` | Settings submodule routes |
| `routes/auth.php` | Login/register |
| `resources/js/pages/` | Inertia React pages (~202 files) |
| `resources/js/components/` | UI + CRUD kit |
| `resources/js/config/crud/` | Config-driven admin CRUD |
| `database/migrations/` | Schema changes |
| `database/seeders/` | Permissions, roles, demo data |
| `tests/Feature/` | Pest tests |

---

## Common Commands

| Command | Purpose |
|---------|---------|
| `php artisan migrate` | Apply schema |
| `php artisan db:seed` | Seed roles/permissions/demo |
| `php artisan route:list --name=hr.` | Find HR routes |
| `php artisan cache:clear` | Clear cache after settings changes |
| `composer dev` | Full local dev stack |
| `npm run dev` | Vite HMR only |
| `npm run build` | Production assets |
| `npm run lint` | ESLint fix |
| `npm run format` | Prettier on `resources/` |
| `npm run types` | `tsc --noEmit` |
| `./vendor/bin/pest` | Run tests |
| `php artisan leave:monthly-accrual` | Monthly paid-leave accrual |
| `php artisan leave:year-end-reset` | Year-end leave reset |
| `php artisan leave:recompute-balances` | Recompute leave balances |
| `php artisan attendance:apply-late-penalty` | Apply late attendance penalties |
| `php artisan users:assign-default-plan` | Assign default SaaS plan to users |

List commands:

```bash
php artisan list
```

---

## Default Login (after seed)

From `database/seeders/DefaultSuperAdminSeeder.php` (when `IS_SAAS=true`):

| Role | Email | Password |
|------|-------|----------|
| Superadmin | `superadmin@example.com` | `password` |

Also read `database/seeders/DefaultCompanySeeder.php` for the company user credentials. **Change these immediately on any shared or production environment.**

---

## How Modules Communicate

```
Browser
  → Laravel route (web.php)
  → Middleware (auth, verified, setting, plan.access, permission:*)
  → Controller
      → Eloquent (scoped by created_by / can() checks)
      → Service (optional: HrEventNotificationService, etc.)
      → Inertia::render('hr/module/index', props)
  → React page
      → hasPermission(auth.permissions, '...')
      → CrudTable / forms
      → router.post(route('hr.module.store'))  // Inertia visits
```

### Adding a new HR module (safe sequence)

1. **Migration** — table + `created_by` + indexes  
2. **Model** — extend `BaseModel` if company-scoped list API  
3. **Permissions** — add to `PermissionSeeder`, assign in `RoleSeeder`  
4. **Controller** — copy pattern from similar module (e.g. `HolidayController`)  
5. **Routes** — `routes/web.php` with `hr.{name}.*` and `permission:` middleware  
6. **Page** — `resources/js/pages/hr/{name}/index.tsx` using `PageTemplate` + `CrudTable`  
7. **Sidebar** — `resources/js/components/app-sidebar.tsx`  
8. **Manual test** — superadmin, company, employee accounts  

### Approval workflow modules

Copy **leave applications** pattern:

- Status field (`pending`, `approved`, `rejected`)
- `HrEventNotificationService::notifyEvent()` on apply/status change
- Permissions: `manage-leave-applications`, `manage-any-*`, `manage-own-*`

---

## Frontend Tips

- Import `route` from Ziggy (global via `types/global.d.ts`).
- Permissions: `import { hasPermission } from '@/utils/authorization'`.
- Do not use `@/utils/permissions` in pages (hook-only).
- Match Inertia page path to controller string exactly: `'hr/widgets/index'` → `pages/hr/widgets/index.tsx`.

---

## SaaS vs Non-SaaS

| `IS_SAAS=true` | Plans, payments, referrals, trial, `max_employees` |
| `IS_SAAS=false` | Single-tenant HRM; plan middleware still runs but many flows inactive |

Check `isSaas()` in PHP and `globalSettings.is_saas` in React before showing billing UI.

---

## Demo Mode

`IS_DEMO=true` enables `DemoModeMiddleware`:

- GET allowed; PUT/PATCH/DELETE blocked  
- Many POST updates blocked (approve, settings, clock-in, etc.)

When testing edits locally, set `IS_DEMO=false`.

---

## Testing Your Changes

```bash
./vendor/bin/pest
# or
php artisan test
```

Add tests for:

- Permission-scoped queries
- Leave/payroll calculations
- Plan access / SaaS gates

---

## Git / Collaboration

**Current repo state:** `master` branch with **no commits yet** (empty history). Establish branching strategy with the team before relying on git blame/PRs.

**Confidence:** High (verified via `git log`).

---

## Security Reminders for Devs

- Never commit `.env` or payment API keys.  
- Remove `phpinfo.php` from deploy targets if present in project root.  
- Do not expose installer on production after setup.  
- Test with non-superadmin users before every PR.

---

## Further Reading

| Document | Contents |
|----------|----------|
| `PROJECT_OVERVIEW.md` | Architecture, modules, request flow |
| `CURSOR_RULES_SUGGESTION.md` | AI/coding conventions |
| `RISK_AREAS.md` | What not to break |

---

## Getting Help on a Module

1. Find route: `php artisan route:list | findstr leave` (Windows)  
2. Open controller action  
3. Open matching `resources/js/pages/...` file  
4. Grep permission string in `database/seeders/PermissionSeeder.php`  
5. Check sidebar entry in `app-sidebar.tsx`

This mirrors how the codebase is organized today — no separate API docs.
