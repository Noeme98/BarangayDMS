# BarangayDMS

Barangay Document Management System — digital filing, Kapitan approval workflow, and historical records digitization.

## Stack

- PHP 8+ (XAMPP)
- Supabase (REST API)
- MySQL-compatible schema in `database(sql)/`

## Setup

1. Copy `test1/.env.example` to `test1/.env` and add your Supabase URL and publishable key.
2. Run `database(sql)/supabase_schema.sql` in the Supabase SQL Editor.
3. Open `http://localhost/3RD-YEARS/test1/` in your browser.
4. First-time admin: run `test1/setup.php` or use credentials from `reset_users.sql`.

## Roles

| Role | Access |
|------|--------|
| Admin | Manage accounts |
| Kapitan | Upload, approve, view documents |
| Member | Submit documents for approval, browse archive |

## Demo data

Run `test1/seed_demo.php` once to load sample documents.
