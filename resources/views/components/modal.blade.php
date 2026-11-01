{{--
    Modal reutilizable (reemplaza el partial Blade con Alpine).

    Antes: `x-data="{ show: ... }"` + focus trap en Alpine + eventos
    `open-modal` / `close-modal` en `window`.

    Ahora: el markup y el estado viven en el componente Vue
    `resources/js/components/AppModal.vue`, que usa el composable
    `useFocusTrap` (ya existente en el repo) para el focus trap.

    La apertura se dispara con un evento DOM nativo `open-modal` sobre
    `window`, con `detail` = nombre del modal:

        window.dispatchEvent(new CustomEvent('open-modal', { detail: 'mi-modal' }))

    Para cerrarlo: `window.dispatchEvent(new CustomEvent('close-modal', { detail: 'mi-modal' }))`
    o el evento `close` (sin detail) para cerrar cualquier modal.
--}}
@props([
    'name',
    'show' => false,
    'maxWidth' => '2xl'
])

<app-modal
    name="{{ $name }}"
    :show="{{ $show ? 'true' : 'false' }}"
    max-width="{{ $maxWidth }}"
>
    {{ $slot }}
</app-modal>
