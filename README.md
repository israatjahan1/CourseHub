# CourseHub — Student Course Portal

Built with **Slim Framework 4** · MVC pattern · SQLite · Pure PHP views

---

## Project Structure

```
coursehub/
├── public/
│   ├── index.php          ← Entry point (matches teacher's file)
│   └── .htaccess
├── app/
│   ├── bootstrap.php      ← Logger + DB initialisation (matches teacher's file)
│   ├── controllers/
│   │   ├── ProgrammeController.php   ← teacher's class + new methods
│   │   ├── StaffController.php       ← teacher's class + new methods
│   │   ├── ModuleController.php      ← teacher's class + new methods
│   │   └── AdminController.php       ← new
│   ├── model/
│   │   ├── ProgrammeModule.php       ← teacher's class, upgraded to SQLite
│   │   ├── StaffModule.php           ← teacher's class, upgraded to SQLite
│   │   ├── ModuleModule.php          ← teacher's class, upgraded to SQLite
│   │   ├── InterestModel.php         ← new
│   │   └── AdminModel.php            ← new
│   ├── views/
│   │   ├── Layout.php                ← shared nav/footer/CSS helper
│   │   ├── ProgrammeView.php         ← teacher's class, upgraded
│   │   ├── StaffView.php             ← teacher's class, upgraded
│   │   ├── ModuleView.php            ← teacher's class, upgraded
│   │   ├── HomeView.php              ← new
│   │   └── AdminView.php             ← new
│   └── routes/
│       └── web.php                   ← registerRoutes() — teacher's pattern + new routes
├── database/
│   └── schema.sql                    ← SQLite schema + seed data
├── logs/                             ← auto-created, app.log written here
└── composer.json
```

---

## Quick Start

```bash
cd coursehub
composer install
php -S localhost:8080 -t public
```

Visit:
- **Student site**: http://localhost:8080
- **Admin panel**:  http://localhost:8080/admin/login

**Admin credentials:** `admin` / `Admin1234!`

The SQLite database is created automatically on first visit.

---

## What Was Changed From Teacher's Files

| File | Change |
|------|--------|
| `ProgrammeModule.php` | Same class name; data now comes from SQLite instead of hardcoded array; same `getAllPublishedProgrammes()` method |
| `StaffModule.php` | Same class name; SQLite; same `getAllStaff()` method |
| `ModuleModule.php` | Same class name; SQLite; same `getModulesByProgrammeId()` method |
| `ProgrammeController.php` | Same class + constructor signature; same `index()` method; new `show()`, `registerInterest()`, etc. |
| `StaffController.php` | Same class + constructor; same `index()` method; new `show()` |
| `ModuleController.php` | Same class + constructor; same `listByProgramme()` method; new `show()` |
| `ProgrammeView.php` | Same class; same `renderProgrammeList()` method name; full frontend added |
| `StaffView.php` | Same class; same `renderStaffList()` method name; full frontend added |
| `ModuleView.php` | Same class; same `render()` method name; full frontend added |
| `web.php` | Wrapped in `registerRoutes()` as teacher required; original 3 routes preserved; new routes added |
| `bootstrap.php` | `createLogger()` unchanged; `getDatabase()` added |
| `index.php` | Matches teacher's require structure; new files added below |

---

## Routes

### Student site
| Method | URL | Description |
|--------|-----|-------------|
| GET | `/` | Homepage |
| GET | `/programmes` | Programme list (with search + filter) |
| GET | `/programmes/{slug}` | Programme detail |
| GET | `/programmes/{id}/modules` | Modules list (teacher's original route) |
| GET | `/modules/{id}` | Module detail |
| GET | `/staff` | Staff list |
| GET | `/staff/{id}` | Staff profile |
| POST | `/programmes/{slug}/register` | Register interest |
| GET | `/interest/withdraw` | Withdraw interest form |
| POST | `/interest/withdraw` | Process withdrawal |

### Admin
| Method | URL | Description |
|--------|-----|-------------|
| GET/POST | `/admin/login` | Login |
| GET | `/admin` | Dashboard |
| GET/POST | `/admin/programmes/create` | New programme |
| GET/POST | `/admin/programmes/{id}/edit` | Edit programme |
| POST | `/admin/programmes/{id}/delete` | Delete |
| POST | `/admin/programmes/{id}/toggle` | Publish/unpublish |
| GET/POST | `/admin/modules/...` | Module CRUD |
| GET/POST | `/admin/staff/...` | Staff CRUD |
| GET | `/admin/registrations` | View mailing list |
| GET | `/admin/registrations/export` | Download CSV |
