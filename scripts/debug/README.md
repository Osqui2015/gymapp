# scripts/debug/

Scripts PHP de un solo uso para verificar el estado de la DB después de
correr `php artisan migrate:fresh --seed`. No son parte del flujo de la
app, son herramientas de inspección.

Uso:

```bash
php scripts/debug/check-users.php          # cuenta usuarios / ejercicios / rutinas
php scripts/debug/check-backfill.php       # verifica ejercicio_id FK en rutinas e historiales
php scripts/debug/check-unmatched.php      # lista las filas sin FK
php scripts/debug/check-historial-match.php # nombres en historiales vs biblioteca
php scripts/debug/check-sim.php            # busca similares por nombre
```

Requieren bootstrap manual de Laravel (incluido en cada script).
Son read-only — no modifican la DB.
