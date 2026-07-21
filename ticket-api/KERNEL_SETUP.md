# Registro del middleware "admin"

En tu proyecto Laravel recién creado, abrí `app/Http/Kernel.php` y agregá la
entrada `'admin'` dentro del array `$middlewareAliases` (o `$routeMiddleware`
si usás una versión anterior de Laravel 10):

```php
protected $middlewareAliases = [
    // ... resto de los middlewares ya existentes
    'admin' => \App\Http\Middleware\AdminMiddleware::class,
];
```

No hace falta tocar nada más: el archivo `AdminMiddleware.php` ya viene
incluido en `app/Http/Middleware/` dentro de este paquete de código.

## Sanctum

Este proyecto usa **tokens Bearer de Sanctum** (no cookies SPA), ideal para
tener dos frontends Angular separados (`ticket-web` y `ticket-admin`) en
distintos puertos, sin problemas de dominio compartido.

Asegurate de que en `config/auth.php` el guard `api` use el driver `sanctum`:

```php
'guards' => [
    'web' => [
        'driver' => 'session',
        'provider' => 'users',
    ],
    'api' => [
        'driver' => 'sanctum',
        'provider' => 'users',
    ],
],
```

Y que el modelo `User` (ya incluido en este paquete) tenga el trait
`Laravel\Sanctum\HasApiTokens`.
