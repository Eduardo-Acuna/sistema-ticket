# Ticket System API (Laravel)

Este paquete contiene el **código de aplicación** (migrations, models,
controllers, middleware, routes, seeders) del backend del sistema de tickets.
No incluye el framework de Laravel en sí, así que hay que crear el proyecto
base primero y copiar estos archivos adentro.

## 1. Crear el proyecto base

```bash
composer create-project laravel/laravel ticket-api
cd ticket-api
composer require laravel/sanctum
```

## 2. Copiar los archivos de este paquete

Copiá (sobrescribiendo cuando corresponda) las siguientes carpetas/archivos
de este paquete dentro de tu proyecto recién creado:

```
app/Models/*.php                      → app/Models/
app/Http/Controllers/Api/*.php        → app/Http/Controllers/Api/
app/Http/Middleware/AdminMiddleware.php → app/Http/Middleware/
database/migrations/*.php             → database/migrations/
database/seeders/*.php                → database/seeders/
routes/api.php                        → routes/ (reemplaza el existente)
config/cors.php                       → config/ (reemplaza el existente)
```

## 3. Registrar el middleware "admin"

Seguí las instrucciones de `KERNEL_SETUP.md` (incluido en este paquete) para
registrar el middleware y configurar el guard de Sanctum.

## 4. Configurar variables de entorno

Editá tu `.env` agregando/actualizando lo indicado en
`.env.example.snippet` (incluido en este paquete): nombre de la app, conexión
a la base de datos `ticket_system` y los orígenes CORS permitidos
(`http://localhost:4200,http://localhost:4201`).

## 5. Migrar y poblar la base de datos

```bash
php artisan key:generate
php artisan migrate --seed
```

Esto crea las 9 tablas del sistema y carga datos de prueba:
- 2 usuarios: `admin@ticketsystem.com` / `user@ticketsystem.com` (password: `password`)
- 5 categorías
- 5 lugares (venues)
- 8 eventos (varios destacados), con sectores y asientos generados
- 2 órdenes de ejemplo con tickets ya vendidos

## 6. Levantar el servidor

```bash
php artisan serve
```

La API queda disponible en `http://localhost:8000/api`.

## Endpoints principales

| Método | Ruta | Acceso |
|---|---|---|
| POST | `/api/register` | Público |
| POST | `/api/login` | Público |
| GET | `/api/events` | Público (filtros: `category_id`, `venue_id`, `search`, `date_from`, `date_to`, `sort_by`, `sort_dir`, `per_page`) |
| GET | `/api/events/featured` | Público |
| GET | `/api/events/{id}` | Público |
| GET | `/api/categories` | Público |
| GET | `/api/venues` | Público |
| GET | `/api/sectors/{id}/layout` | Público |
| POST | `/api/logout` | Autenticado |
| GET | `/api/user` | Autenticado |
| GET | `/api/user/tickets` | Autenticado |
| POST | `/api/orders` | Autenticado |
| GET | `/api/orders/{id}` | Autenticado |
| POST | `/api/sectors/{id}/reserve` | Autenticado |
| POST | `/api/sectors/{id}/release` | Autenticado |
| GET | `/api/dashboard/stats` | Admin |
| GET | `/api/dashboard/sales` | Admin |
| POST/PUT/DELETE | `/api/events`, `/api/events/{id}` | Admin |
| POST | `/api/events/{id}/publish` | Admin |
| POST | `/api/events/{id}/duplicate` | Admin |
| POST/PUT/DELETE | `/api/categories`, `/api/venues` | Admin |
| POST/PUT/DELETE | `/api/events/{id}/sectors`, `/api/sectors/{id}` | Admin |
| POST | `/api/sectors/{id}/generate-seats` | Admin |

## Autenticación

Usá el token devuelto por `/login` o `/register` como header
`Authorization: Bearer {token}` en todas las peticiones protegidas.

## Notas de diseño

- **Reserva de asientos**: al reservar, el asiento queda en estado `reserved`
  con `reserved_until` = ahora + 15 minutos. `getLayout` y `reserveSeats`
  liberan automáticamente las reservas vencidas antes de operar.
- **Sectores sin mapa de asientos**: si un sector no tiene `seats` generados,
  la compra se hace por cantidad usando el campo `available` del sector
  (ideal para "general" / campo).
- **Protecciones**: no se puede editar/eliminar un evento o sector que ya
  tenga tickets vendidos; no se puede publicar un evento sin sectores con
  asientos generados (o al menos un sector, según tu regla de negocio —
  ajustá `Event::canBePublished()` si querés permitir sectores sin asientos).
- **Pago**: `OrderController@store` simula el pago y marca la orden como
  `paid` inmediatamente. Si integrás una pasarela real, cambiá el flujo para
  crear la orden en `pending` y confirmarla en un webhook.

## Próximos pasos

Una vez que tengas esta API corriendo, avisame y seguimos con
`ticket-web` (frontend público en Angular) y `ticket-admin` (panel admin en
Angular).
