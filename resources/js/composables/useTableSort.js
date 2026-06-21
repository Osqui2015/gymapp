/**
 * Composable para sort + filter de tablas.
 *
 * Pensado para integrarse con `ResponsiveTable.vue` pero es genérico:
 *   const { sortedRows, sortKey, sortDir, toggleSort, query, setQuery, filteredRows } =
 *       useTableSort(rows, {
 *           initialKey: 'name',
 *           initialDir: 'asc',
 *           searchable: (row) => [row.name, row.email].join(' '),
 *       });
 *
 *   <input v-model="query" />
 *   <th @click="toggleSort('name')">Nombre {{ caret('name') }}</th>
 */

import { ref, computed } from 'vue';

export function useTableSort(rows, options = {}) {
    const {
        initialKey = null,
        initialDir = 'asc',
        searchable = null, // (row) => string para filter genérico
    } = options;

    const sortKey = ref(initialKey);
    const sortDir = ref(initialDir); // 'asc' | 'desc'
    const query = ref('');

    const filteredRows = computed(() => {
        if (!searchable || !query.value.trim()) return rows.value ?? rows;
        const q = query.value.trim().toLowerCase();
        const list = rows.value ?? rows;
        return list.filter((row) => {
            try {
                return String(searchable(row)).toLowerCase().includes(q);
            } catch {
                return false;
            }
        });
    });

    const sortedRows = computed(() => {
        const list = filteredRows.value;
        if (!sortKey.value) return list;
        const dir = sortDir.value === 'asc' ? 1 : -1;
        const key = sortKey.value;
        // copia defensiva
        return [...list].sort((a, b) => {
            const av = a?.[key];
            const bv = b?.[key];
            // nulls/undefined al final
            if (av == null && bv == null) return 0;
            if (av == null) return 1;
            if (bv == null) return -1;
            if (typeof av === 'number' && typeof bv === 'number') return (av - bv) * dir;
            return String(av).localeCompare(String(bv), undefined, { numeric: true, sensitivity: 'base' }) * dir;
        });
    });

    const toggleSort = (key) => {
        if (sortKey.value === key) {
            sortDir.value = sortDir.value === 'asc' ? 'desc' : 'asc';
        } else {
            sortKey.value = key;
            sortDir.value = 'asc';
        }
    };

    const caret = (key) => {
        if (sortKey.value !== key) return '';
        return sortDir.value === 'asc' ? '▲' : '▼';
    };

    const setQuery = (val) => { query.value = val; };
    const reset = () => { sortKey.value = initialKey; sortDir.value = initialDir; query.value = ''; };

    return {
        sortKey,
        sortDir,
        query,
        sortedRows,
        filteredRows,
        toggleSort,
        caret,
        setQuery,
        reset,
    };
}
