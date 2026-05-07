Authors:
-Farbod 
-Alister
-Nawaz 


# SOL-A — Instrument Rental & Accessories Shop

SOL-A is a PHP + MySQL (MariaDB) web app for:

- renting musical instruments,
- shopping accessory products,
- managing carts and wishlist,
- and running a full admin back office (content + operations).

Built with Bootstrap 5, procedural route controllers, and shared helpers under `includes/`.

---

## 1) Requirements

- PHP 8.0+ (`mysqli`, `session`, `json`; `mbstring` recommended)
- MySQL / MariaDB with `utf8mb4`
- Apache/Nginx (WAMP/LAMP/XAMPP is fine)

---

## 2) Configuration

- Database config: `includes/db_config.php`
- Connection bootstrap: `db_connect.php` (exposes global `$connect`)
- App kernel/helpers: `includes/app.php`

Notes:

- Use `sol_url("path")` for links from nested folders.
- Mutating forms must use CSRF helpers: `sol_csrf_field()` + `sol_csrf_verify()`.

---

## 3) Roles & session model

- Customer session: `$_SESSION["user"]` + `$_SESSION["uid"]`
- Admin session: `$_SESSION["adm"]` (with `uid` available for shared queries)
- Carts:
  - `$_SESSION["shop_cart"]` => `product_id => qty`
  - `$_SESSION["rent_cart"]` => `instrument_id => qty`
- Wishlist is DB-backed (`wishlist` table), not session-backed.

`sol_nav_role()` returns `guest`, `user`, or `admin`.

---

## 4) Main routes

### Public

- `index.php` (landing: home slider + rent/shop carousels)
- `login.php`, `register.php`, `logout.php`
- `faq.php`, `contact.php`

### Customer

- `account/home.php`, `account/profile.php`, `account/wishlist.php`
- Rent: `rent/rentcatalog.php`, `rent/product_details.php`, `rent/rent_cart.php`, `rent/rent_checkout_confirm.php`, `rent/my_rent_requests.php`
- Shop: `shop/catalog.php`, `shop/shopItems_details.php`, `shop/cart.php`, `shop/my_orders.php`

### Admin

- `admin/dashboard.php`
- `admin/rentals_admin.php`
- `admin/shop_orders_admin.php`
- `admin/categories_admin.php`
- `admin/instruments_admin.php`
- `admin/users_admin.php`, `admin/user_edit.php`, `admin/user_delete.php`
- `admin/faq_admin.php`
- `admin/contact_messages.php`
- `admin/home_slides_admin.php`
- Products domain: `products/index.php`, `products/create.php`, `products/update.php`, `products/delete.php`, `products/suppliers.php`

---

## 5) Home page features (current)

- Admin-managed hero slider (`home_slides` table):
  - background image, overlay %, heading/subheading,
  - CTA buttons,
  - audience (`all`, `guest`, `user`),
  - sort order and active flag.
- Horizontal carousels for:
  - `Instruments for rent`
  - `Accessories`
- Quick-view hover overlays.
- Wishlist heart buttons on cards (logged-in users).
- Ajax cart actions via `assets/js/nav-live.js`.

---

## 6) Core shared files

- `includes/app.php`  
  Session start, auth guards (`sol_require_user`, `sol_require_admin`), URL helpers, CSRF, DB helper checks, counts/wishlist helpers.

- `includes/layout_top.php` / `includes/layout_bottom.php`  
  Global layout shell + JS loading.

- `includes/home_slides.php`  
  Slider data resolver + upload path helpers.

- `includes/mini_cart.php` + `includes/partials/mini_cart_body.php`  
  Header mini-cart payload and rendering.

- `assets/js/sol-swal.js`  
  Central confirm modal handler for `data-sol-confirm`.

- `assets/js/nav-live.js`  
  Ajax actions for shop cart, rent cart, wishlist + live navbar count updates.

- `assets/js/home_shop_carousel.js`  
  Horizontal carousel scroll controls.

---

## 7) Database schema & migration order

Recommended order on a fresh database:

1. Base dump (e.g. `login.sql` / `login (8).sql`)
2. `schema_updates_home_slides.sql`
3. `schema_updates_delete_relations.sql`
4. Optional seed: `seed_home_slides_products_instruments.sql`

Important tables used by runtime:

- `users`, `categories`, `instruments`, `products`, `suppliers`
- `wishlist`
- `orders`
- `rental_requests`, `rental_status_logs`
- `faq`, `contact_messages`
- `home_slides`

---

## 8) Delete behavior & relationship safety

Current logic is relation-aware:

- **Categories**: if linked instruments exist, system tries detaching (`category_id = NULL`) before delete (requires nullable FK setup).
- **Suppliers**: linked products are detached (`fk_supplier_id = NULL`) before supplier delete.
- **Instruments**: delete is blocked when rental history exists.
- **Products/Users**: dedicated delete endpoints with CSRF checks.

`schema_updates_delete_relations.sql` ensures category deletion can work safely by updating FK strategy to `ON DELETE SET NULL`.

---


## 9) Security notes

- Keep all write/delete operations CSRF-protected.
- Use prepared statements for SQL (project standard).
- Do not commit real DB secrets from `includes/db_config.php`.
- Consider server-level protection for `/admin`.

---

## 10) Quick project tree

Just Folders stretcher
sol-a/
├── admin/
├── account/
├── rent/
├── shop/
├── products/
├── includes/
│   ├── app.php
│   ├── home_slides.php
│   ├── mini_cart.php
│   └── partials/
├── assets/
│   ├── css/main.css
│   └── js/{sol-swal.js,nav-live.js,home_shop_carousel.js}
├── pictures/
├── schema_updates_home_slides.sql
├── schema_updates_delete_relations.sql
├── seed_home_slides_products_instruments.sql
└── README.md

------

Folders and Files
sol-a/
│   .htaccess
│   404.php
│   contact.php
│   Database.php
│   db_connect.php
│   faq.php
│   file_upload.php
│   index.php
│   login.php
│   login.sql
│   logout.php
│   README.md
│   register.php
│
├───account
│       home.php
│       profile.php
│       wishlist.php
│
├───admin
│       categories_admin.php
│       contact_messages.php
│       dashboard.php
│       faq_admin.php
│       home_slides_admin.php
│       instruments_admin.php
│       rentals_admin.php
│       shop_orders_admin.php
│       users_admin.php
│       user_delete.php
│       user_edit.php
│
├───api
│       cart_add.php
│       cart_qty_delta.php
│       nav_counts.php
│
├───assets
│   ├───css
│   │       main.css
│   │
│   └───js
│           home_shop_carousel.js
│           nav-live.js
│           sol-swal.js
│
├───includes
│   │   app.php
│   │   db_config.php
│   │   home_slides.php
│   │   intl_address.php
│   │   layout_bottom.php
│   │   layout_top.php
│   │   mini_cart.php
│   │   rental_helpers.php
│   │
│   └───partials
│           checkout_iban_transfer_box.php
│           home_footer_strip.php
│           home_rent_shop.php
│           home_shop_carousel.php
│           home_slider.php
│           intl_address_fields.php
│           mini_cart_body.php
│           navbar.php
│           wishlist_fab.php
│
├───info
│   │   Music Rental Music Instrument Rental Platformullstack Project.docx
│   │   Project Details.pdf
│   │   sol-logo-dark.png
│   │   sol-logo.png
│   │
│   ├───ERP
│   │       ERD_SOL_Project.drawio
│   │       ERD_SOL_Project_basic.png
│   │       ERD_SOL_Project_final.drawio
│   │       ERD_SOL_Project_final.png
│   │       ERD_SOL_Project_Pro.png
│   │
│   ├───Project_management
│   │       67zsGkTz - music-instrument-rental-platform-project-sol-instrument.json
│   │       communication.png
│   │       Trello.png
│   │
│   ├───Proposal
│   │       music-instrument-rental-proposal_basic.canvas.tsx
│   │       music-instrument-rental-proposal_basic.html
│   │
│   └───Wireframe
│           sol-logo.png
│           wireframes-rental-lowfi.html
│
├───pictures
│   │   avatar.png
│   │   ...
│   └───home_slides
│       ...
│
├───products
│       create.php
│       delete.php
│       index.php
│       index_oop.php
│       suppliers.php
│       update.php
│
├───rent
│       checkout.php
│       my_rent_requests.php
│       product_details.php
│       rentcatalog.php
│       rentForm.php
│       rent_cart.php
│       rent_checkout_confirm.php
│       rent_success.php
│
└───shop
        cart.php
        catalog.php
        checkout_confirm.php
        my_orders.php
        order_success.php
        shopItems_details.php
