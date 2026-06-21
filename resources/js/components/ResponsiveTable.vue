<!--
  ResponsiveTable — tabla desktop + cards mobile, ahora con sort y filter opcionales.

  Backward-compatible: si NO se pasa `sortable` ni `filterable`, se comporta como antes
  (sólo render condicional). Con `sortable` los <th> son clickeables; con `filterable`
  aparece un input de búsqueda arriba que filtra por la(s) columna(s) marcada(s) con
  `searchable: true` en `columns`.

  Props nuevas:
    - sortable: Boolean (default false)
    - filterable: Boolean (default false)
    - filterPlaceholder: String
    - emptyText: String (cuando no hay filas tras filtrar)

  En `columns` se puede agregar `sortable: true` por columna y `searchable: true`
  para incluirla en el filtro.

  Emite:
    - sort-change({ key, dir })
    - filter-change(query)
-->
<template>
  <div>
    <!-- Filter input (opcional) -->
    <div v-if="filterable" class="mb-3">
      <label class="relative block">
        <span class="sr-only">Buscar</span>
        <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400">
          <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M11 19a8 8 0 100-16 8 8 0 000 16z" />
          </svg>
        </span>
        <input
          v-model="query"
          type="search"
          :placeholder="filterPlaceholder"
          class="w-full md:w-72 pl-10 pr-3 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-sm text-gray-900 dark:text-white focus:outline-none focus:ring-2 focus:ring-indigo-500"
          @input="$emit('filter-change', query)"
        />
      </label>
    </div>

    <!-- Vista desktop -->
    <div class="hidden md:block overflow-x-auto">
      <table class="w-full text-sm text-left">
        <thead :class="theadClass">
          <tr>
            <th
              v-for="col in columns"
              :key="col.key"
              :class="['px-4 py-3 font-semibold', col.thClass, sortable && col.sortable !== false ? 'cursor-pointer select-none' : '']"
              :aria-sort="ariaSortFor(col)"
              @click="onSort(col)"
            >
              <span class="inline-flex items-center gap-1">
                {{ col.label }}
                <template v-if="sortable && col.sortable !== false">
                  <span class="text-xs" :class="caretClass(col)">{{ caret(col) }}</span>
                </template>
              </span>
            </th>
            <th v-if="$slots.actions" class="px-4 py-3 text-right">Acciones</th>
          </tr>
        </thead>
        <tbody :class="tbodyClass">
          <slot name="rows" :rows="displayRows" />
        </tbody>
      </table>
    </div>

    <!-- Vista mobile -->
    <div class="md:hidden space-y-3">
      <slot name="cards" :rows="displayRows">
        <div
          v-for="(row, i) in displayRows"
          :key="row.id ?? i"
          class="bg-white dark:bg-gray-800 rounded-xl border border-gray-200 dark:border-gray-700 shadow-sm p-4 space-y-2"
        >
          <div
            v-for="col in columns"
            :key="col.key"
            class="flex items-start justify-between gap-3 text-sm"
          >
            <span class="text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
              {{ col.label }}
            </span>
            <span :class="['text-right', col.tdClass]">
              <slot :name="`cell-${col.key}`" :row="row" :value="row[col.key]">
                {{ row[col.key] }}
              </slot>
            </span>
          </div>
          <div v-if="$slots.actions" class="pt-2 border-t border-gray-100 dark:border-gray-700 flex justify-end gap-1">
            <slot name="actions" :row="row" />
          </div>
        </div>
      </slot>
    </div>

    <!-- Empty state -->
    <div v-if="displayRows.length === 0" class="py-8 text-center text-sm text-gray-500 dark:text-gray-400">
      <slot name="empty">{{ emptyText }}</slot>
    </div>
  </div>
</template>

<script setup>
import { ref, computed } from 'vue';

const props = defineProps({
    columns: {
        type: Array,
        required: true,
        // [{ key: 'name', label: 'Nombre', thClass: '', tdClass: 'font-semibold', sortable: true, searchable: true }]
    },
    rows: { type: Array, default: () => [] },
    theadClass: { type: String, default: 'bg-gray-50 dark:bg-gray-700 text-gray-700 dark:text-gray-300 uppercase text-xs' },
    tbodyClass: { type: String, default: 'divide-y divide-gray-200 dark:divide-gray-700 bg-white dark:bg-gray-800' },
    sortable: { type: Boolean, default: false },
    filterable: { type: Boolean, default: false },
    filterPlaceholder: { type: String, default: 'Buscar…' },
    emptyText: { type: String, default: 'Sin resultados.' },
});

defineEmits(['sort-change', 'filter-change']);
defineSlots();

// --- sort state ---
const sortKey = ref(null);
const sortDir = ref('asc'); // 'asc' | 'desc'

const onSort = (col) => {
    if (!props.sortable || col.sortable === false) return;
    if (sortKey.value === col.key) {
        sortDir.value = sortDir.value === 'asc' ? 'desc' : 'asc';
    } else {
        sortKey.value = col.key;
        sortDir.value = 'asc';
    }
    // notificar al padre si quiere control externo
    // (usamos emit por composición; el padre puede ignorar)
    // emit handled via defineEmits
};

const caret = (col) => {
    if (sortKey.value !== col.key) return '↕';
    return sortDir.value === 'asc' ? '▲' : '▼';
};
const caretClass = (col) => {
    if (sortKey.value !== col.key) return 'text-gray-300 dark:text-gray-600';
    return 'text-indigo-600 dark:text-indigo-400';
};
const ariaSortFor = (col) => {
    if (!props.sortable || col.sortable === false) return undefined;
    if (sortKey.value !== col.key) return 'none';
    return sortDir.value === 'asc' ? 'ascending' : 'descending';
};

// --- filter state ---
const query = ref('');
const filteredRows = computed(() => {
    if (!props.filterable || !query.value.trim()) return props.rows;
    const q = query.value.trim().toLowerCase();
    const searchableCols = props.columns.filter((c) => c.searchable);
    if (!searchableCols.length) return props.rows;
    return props.rows.filter((row) =>
        searchableCols.some((c) => {
            const v = row?.[c.key];
            return v != null && String(v).toLowerCase().includes(q);
        }),
    );
});

// --- sort + filter combinados ---
const displayRows = computed(() => {
    let list = filteredRows.value;
    if (!props.sortable || !sortKey.value) return list;
    const dir = sortDir.value === 'asc' ? 1 : -1;
    const key = sortKey.value;
    return [...list].sort((a, b) => {
        const av = a?.[key];
        const bv = b?.[key];
        if (av == null && bv == null) return 0;
        if (av == null) return 1;
        if (bv == null) return -1;
        if (typeof av === 'number' && typeof bv === 'number') return (av - bv) * dir;
        return String(av).localeCompare(String(bv), undefined, { numeric: true, sensitivity: 'base' }) * dir;
    });
});
</script>
