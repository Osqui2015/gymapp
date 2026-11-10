<script setup>
defineProps({
    /** Año actual mostrado en el copyright. */
    anioActual: {
        type: Number,
        default: new Date().getFullYear(),
    },
});
</script>

<template>
    <footer class="w-full bg-surface-container-lowest mt-space-xl">
        <div
            class="max-w-[1280px] mx-auto px-margin md:px-margin-tablet lg:px-margin-desktop py-space-xl flex flex-col md:flex-row items-center justify-between gap-space-md"
        >
            <div class="flex items-center gap-space-sm">
                <div
                    class="w-6 h-6 rounded-lg bg-secondary-container flex items-center justify-center"
                >
                    <span class="material-symbols-outlined text-secondary text-[16px]"
                        >fitness_center</span
                    >
                </div>
                <span class="font-label-md text-label-md text-on-surface-variant">
                    © {{ anioActual }} GymApp Pro Telemetry. Todos los derechos reservados.
                </span>
            </div>
            <div class="flex items-center gap-space-lg">
                <a
                    class="font-label-sm text-label-sm text-on-surface-variant hover:text-on-surface transition-colors"
                    href="#soporte"
                    >Soporte</a
                >
                <a
                    class="font-label-sm text-label-sm text-on-surface-variant hover:text-on-surface transition-colors"
                    href="#terminos"
                    >Términos del Servicio</a
                >
                <a
                    class="font-label-sm text-label-sm text-on-surface-variant hover:text-on-surface transition-colors"
                    href="#privacidad"
                    >Privacidad</a
                >
            </div>
        </div>
    </footer>
</template>
