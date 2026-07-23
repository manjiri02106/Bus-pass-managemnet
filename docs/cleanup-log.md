# Cleanup Log - Bus Pass Management System Restructuring

This document records the moves, deletions, and refactoring actions performed during the feature isolation refactor.

**Refactor Target**: Clean up layout files, isolate business logic, rename components to kebab-case, and structure them under standard subdirectories (`/config`, `/src`, `/tests`, `/docs`, `/scripts`, `/archive`).
**Branch**: `feature/minimal-surface`
**Timestamp**: 2026-07-20

## File Operations Log

| Original Path | Action | Destination / Status | Reason | Timestamp |
| :--- | :--- | :--- | :--- | :--- |
| `admin_dashboard.php` | Archived | `/archive/admin_dashboard.php` | Unrelated to the 5 core officer/student features. HOD and System Admin flows handled directly by Route Validation. | 2026-07-20T13:43:00 |
| `sidebar.php` | Archived | `/archive/sidebar.php` | Navigation structure merged directly into individual dashboard pages to remove redundant layout templates. | 2026-07-20T13:43:00 |
| `db.php` | Refactored | `/config/db-connection.php` | Moved database setup into standard config structure. | 2026-07-20T13:43:00 |
| `database.sql` | Retained | `/database.sql` | Required DB schema definition. | 2026-07-20T13:43:00 |
| `style.css` | Moved | `/src/style.css` | Moved under production source folder. | 2026-07-20T13:43:00 |
| `header.php` | Integrated | Removed | Header layout code embedded in views to isolate dependencies. | 2026-07-20T13:43:00 |
| `footer.php` | Integrated | Removed | Footer layout code embedded in views to isolate dependencies. | 2026-07-20T13:43:00 |
| `auth_helpers.php` | Renamed | `/src/auth-helper.php` | Renamed to match kebab-case naming standard. | 2026-07-20T13:43:00 |
| `login.php` | Moved | `/src/login.php` | Moved to production source directory. | 2026-07-20T13:43:00 |
| `logout.php` | Moved | `/src/logout.php` | Moved to production source directory. | 2026-07-20T13:43:00 |
| `officer_dashboard.php` | Renamed | `/src/officer-dashboard.php` | Restructured under src using kebab-case. | 2026-07-20T13:43:00 |
| `verify_application.php` | Renamed | `/src/verify-application.php` | Refactored UI logic to delegate core checks to service classes. | 2026-07-20T13:43:00 |
| `student_dashboard.php` | Renamed | `/src/student-dashboard.php` | Restructured under src using kebab-case. | 2026-07-20T13:43:00 |
| `apply.php` | Renamed | `/src/apply.php` | Restructured under src using kebab-case. | 2026-07-20T13:43:00 |
| `renew.php` | Renamed | `/src/renew.php` | Restructured under src using kebab-case. | 2026-07-20T13:43:00 |
| `download_pass.php` | Renamed | `/src/download-pass.php` | Restructured under src using kebab-case. | 2026-07-20T13:43:00 |
| `seed.php` | Moved | `/scripts/seed.php` | Relocated script under standard scripts directory. | 2026-07-20T13:43:00 |
| `test_features.php` | Replaced | `/scripts/run-tests.php` | Restructured as test suite runner in scripts folder. | 2026-07-20T13:43:00 |
