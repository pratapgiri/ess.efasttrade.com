# .cursor/rules

## Core Rule
- Read existing code before writing new code.
- Follow current project architecture and conventions.
- Keep changes minimal and production safe.

## Backend
- Laravel 12
- PHP 8.2+
- Inertia.js
- React + TypeScript
- Spatie Permission
- TailwindCSS

## Routing
- Use routes/web.php
- Follow route naming:

hr.module.action

## Permissions
Always update:
- PermissionSeeder
- Route middleware
- Sidebar visibility
- Frontend permission checks

## Multi Tenancy
Always scope queries using:
- created_by
- getCompanyId()
- getCompanyAndUsersId()

Never expose cross-company data.

## Frontend
Reuse:
- CrudTable
- PageTemplate
- CrudFormModal
- PageCrudWrapper

## Security
- Validate uploads
- Keep backend auth checks
- Never trust frontend permissions
- Never disable CSRF casually

## Performance
- Avoid unfiltered Model::get()
- Use pagination and eager loading

## Critical Files
- routes/web.php
- bootstrap/app.php
- helper.php
- HandleInertiaRequests.php
