# Bus Pass Management

A lightweight PHP application for managing bus routes, buses, locations, stops, and route status using SQLite.

## Features

- Dashboard summary of routes, buses, locations, and stops
- CRUD for:
  - Routes
  - Buses
  - Source and destination locations
  - Bus stops
- Route status update and display
- SQLite database automatically initialized in `data/bus_pass.db`
- Simple UI with navigation and form-based management

## Requirements

- PHP with `pdo_sqlite` enabled
- Web server such as Apache (XAMPP, WAMP, etc.)
- Browser to access the app via `http://localhost`

## Installation

1. Copy the project folder into your web server document root.
   - Example for XAMPP: `C:\xampp\htdocs\Bus-pass-managemnet`
2. Make sure `php.ini` enables the `pdo_sqlite` extension.
3. Open the app in your browser:
   - `http://localhost/Bus-pass-managemnet/index.php`
4. The application creates the SQLite database automatically on first access.

## Usage

1. Open the dashboard at `index.php`.
2. Add source and destination locations in `locations.php`.
3. Create routes in `routes.php`, selecting source and destination locations.
4. Add buses in `buses.php` and assign them to routes.
5. Manage bus stops for each route in `stops.php`.
6. Update route status in `status.php`.

## Project Files

- `index.php` — dashboard and statistics overview
- `routes.php` — create, edit, delete, and search routes
- `buses.php` — manage bus records and assignment to routes
- `locations.php` — manage source and destination locations
- `stops.php` — manage bus stop records for routes
- `status.php` — update and display route status
- `db.php` — SQLite connection and schema initialization
- `styles.css` — styling for the user interface
- `data/` — automatically created directory for the SQLite database

## Notes

- The app uses SQLite and enforces foreign key constraints.
- Locations cannot be deleted while they are used by a route.
- Routes cannot be deleted while buses are assigned.
- Bus stop records are deleted automatically when the linked route is removed.

## Troubleshooting

- If the app shows a database error, confirm `pdo_sqlite` is enabled and the web server user has permission to create files in `data/`.
- Ensure the `data/` folder is writable by the PHP process.
