<template>
    <component
        :is="tag"
        :class="[
            shellClass,
            paddingClass,
            shadowClass,
            hoverClass,
            $attrs.class,
        ]"
    >
        <header v-if="title || $slots.header" class="flex items-start justify-between gap-3 mb-4">
            <div class="min-w-0 flex-1">
                <h3 v-if="title" class="text-lg font-bold text-gray-900 dark:text-white">
                    {{ title }}
                </h3>
                <p v-if="subtitle" class="text-sm text-gray-500 dark:text-gray-400 mt-0.5">
                    {{ subtitle }}
                </p>
                <slot name="header" />
            </div>
            <div v-if="$slots.actions" class="flex-shrink-0">
                <slot name="actions" />
            </div>
        </header>
        <slot />
        <footer
            v-if="$slots.footer"
            :class="[
                'mt-4 pt-4 border-t',
                variant === 'obsidian'
                    ? 'border-[var(--color-obsidian-border)]'
                    : 'border-gray-100 dark:border-gray-700',
            ]"
        >
            <slot name="footer" />
        </footer>
    </component>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    title: { type: String, default: '' },
    subtitle: { type: String, default: '' },
    tag: { type: String, default: 'div' },
    padding: {
        type: String,
        default: 'md',
        validator: (v) => ['none', 'sm', 'md', 'lg'].includes(v),
    },
    shadow: {
        type: String,
        default: 'sm',
        validator: (v) => ['none', 'sm', 'md', 'lg'].includes(v),
    },
    hover: { type: Boolean, default: false },
    variant: {
        type: String,
        default: 'default',
        validator: (v) => ['default', 'obsidian'].includes(v),
    },
});

const shellClass = computed(() => {
    if (props.variant === 'obsidian') {
        // Esquinas más grandes (rounded-2xl) y borde sutil obsidian
        return 'rounded-2xl border bg-white border-gray-200/70 shadow-sm dark:bg-[var(--color-obsidian-surface)] dark:border-[var(--color-obsidian-border)]';
    }
    return 'rounded-xl border border-gray-200 bg-white dark:bg-gray-800 dark:border-gray-700';
});

const paddingClass = computed(
    () =>
        ({
            none: '',
            sm: 'p-3',
            md: 'p-5',
            lg: 'p-6',
        })[props.padding]
);

const shadowClass = computed(() => {
    if (props.variant === 'obsidian') {
        // Sombras obsidian reemplazan a las utilities de Tailwind
        return (
            {
                none: '',
                sm: 'dark:shadow-[0_4px_16px_rgba(0,0,0,0.35)]',
                md: 'dark:shadow-[0_8px_24px_rgba(0,0,0,0.45)]',
                lg: 'dark:shadow-[0_12px_32px_rgba(0,0,0,0.5)]',
            })[props.shadow];
    }
    return (
        {
            none: '',
            sm: 'shadow-sm',
            md: 'shadow-md',
            lg: 'shadow-lg',
        })[props.shadow];
});

const hoverClass = computed(() =>
    props.hover
        ? 'transition-all hover:shadow-md hover:border-gray-300 dark:hover:border-gray-600'
        : ''
);
</script>
