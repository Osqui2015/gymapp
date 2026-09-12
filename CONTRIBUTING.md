# Guía de Contribución - GymApp

¡Bienvenido al repositorio de GymApp! Para mantener la consistencia, estabilidad y calidad del código, todos los colaboradores deben seguir las siguientes pautas.

---

## 1. Convención de Commits (Conventional Commits)

GymApp adopta la especificación de **[Conventional Commits 1.0.0](https://www.conventionalcommits.org/)**.

### Estructura del mensaje:
```text
<tipo>(<ámbito opcional>): <descripción concisa en minúsculas y presente>

[cuerpo opcional detallando el motivo del cambio]

[pie opcional, ej: refs #123 o BREAKING CHANGE: ...]
```

### Tipos permitidos:
- **`feat`**: Nueva funcionalidad para el usuario (ej: `feat(rutinas): agregar selector de superseries`).
- **`fix`**: Corrección de un bug o fallo (ej: `fix(auth): corregir bloqueo de rate limiter en reset password`).
- **`docs`**: Cambios exclusivos en la documentación (ej: `docs: actualizar guía de despliegue en README`).
- **`style`**: Formateo, espacios en blanco o puntos y comas sin alterar lógica (ej: `style: formatear con prettier`).
- **`refactor`**: Refactorización de código que no agrega funcionalidad ni arregla bugs (ej: `refactor(services): mover cálculo de 1rm a servicio dedicado`).
- **`test`**: Agregar o corregir tests unitarios, de integración o e2e (ej: `test(admin): agregar prueba de cálculo de retención`).
- **`chore`**: Tareas de mantenimiento, dependencias o configuración de build (ej: `chore: actualizar dependencias en package.json`).

---

## 2. Flujo de Trabajo y Ramas

1. **Ramas principales**:
   - `main`: Rama de producción estable. Todo cambio debe llegar mediante Pull Request verificado.
2. **Nombres de ramas**:
   - `feature/nombre-de-la-mejora`
   - `fix/descripcion-del-bug`
   - `refactor/area-a-refactorizar`
3. **Reglas de Pull Request**:
   - Todo PR debe tener todos los tests en verde (PHPUnit + Vitest).
   - El código debe pasar los linters sin errores (`pint` y `eslint`).
   - No hacer push directo a `main`.

---

## 3. Checklist Antes de Enviar Cambios

Antes de hacer commit o abrir un PR, ejecutá las siguientes verificaciones locales:

### Backend (PHP / Laravel)
```bash
# Formatear código con Pint (automático)
composer run format

# Verificar estándares de código (linter)
composer run lint

# Ejecutar suite de pruebas PHPUnit
& "I:\laragon\bin\php\php-8.2.30-Win32-vs16-x64\php.exe" "I:\laragon\www\GymApp\artisan" test
```

### Frontend (Vue 3 / JavaScript)
```bash
# Formatear código con Prettier
npm run format

# Ejecutar linter de Vue / JS
npm run lint

# Ejecutar suite de pruebas Vitest
npm run test:run

# Verificar que el build de producción compile correctamente
npm run build
```

---

## 4. Políticas de Base de Datos y Seguridad

1. **Migraciones**:
   - Siempre escribir migraciones reversibles (`down()`) e idempotentes cuando sea posible.
   - Nunca incluir datos sensibles en los seeders (`DatabaseSeeder`).
2. **Backups**:
   - Nunca commitear archivos `.sql`, `.sqlite` de prueba o respaldos temporales con datos reales de usuarios al repositorio.
3. **Variables de Entorno**:
   - Nunca subir claves secretas en `.env`. Usar `.env.example` para declarar variables nuevas requeridas.
