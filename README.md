# Smart-menu-and-inventory-management-system

Smart digital menu + lightweight inventory/ordering dashboard for restaurants.

## Features
- Public menu with cart + email billing (PHPMailer + Dompdf).
- Admin dashboard for recent orders, inventory add/update, and menu CRUD.
- Inventory alerts (In Stock / Low Stock / Out Of Stock) with category + search filters.
- Supplier order form with email notification and local audit log.
- Secure-ish auth: registration uses hashed passwords (login falls back to legacy plain if older records exist).

## Quick start
1) PHP 8+, MySQL, Composer packages installed (`vendor/` with PHPMailer + Dompdf).
2) Update DB credentials in PHP files if your host/port differs (default `localhost:6368`, user `root`, pass `1234`, db `resturent`).
3) Configure SMTP in `cart.php` / `contactus.php` / `order.php` / `email.php` (currently set for Brevo test credentials) for outbound mail.
4) Serve the project root via your PHP server (or place in your web root) and ensure the `uploads/` folder is writable for menu images.
5) Create required tables (`customer`, `admin`, `orders`, `menu`, `invitems`) matching the field names used in the PHP files.

## Notes
- `order.php` writes a simple `inventory_orders.log` for auditing supplier requests.
- Dashboard “Clear previous orders” truncates the `orders` table; use with care.
- Password hashes are stored for new users; login will accept legacy plain-text rows so old data still works.
