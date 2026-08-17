/**
 * useFuzzySearch — matching aproximado para búsqueda.
 *
 * Estrategia:
 *  - Match exacto (case-insensitive) → score 100
 *  - Match al inicio de la palabra → score 80
 *  - Match de substring → score 60
 *  - Match fuzzy (caracteres en orden) → score variable
 *  - Sin match → 0
 *
 * Retorna además el HTML del texto con el match resaltado en <mark>.
 *
 * Uso:
 *   const { fuzzyMatch, highlight } = useFuzzySearch();
 *   const score = fuzzyMatch('Press banca', 'banca'); // 100
 *   const html = highlight('Press banca', 'banca'); // 'Press <mark>banca</mark>'
 */
export function useFuzzySearch() {
    const normalize = (value) => {
        return String(value ?? '')
            .toLowerCase()
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '');
    };

    const commonPrefixLength = (a, b) => {
        let i = 0;
        while (i < Math.min(a.length, b.length) && a[i] === b[i]) {
            i++;
        }
        return i;
    };

    /**
     * Calcula un score de match entre `text` y `query`.
     * Mayor score = mejor match. 0 = sin match.
     */
    const fuzzyMatch = (text, query) => {
        if (!text || !query) return 0;
        const t = normalize(text);
        const q = normalize(query);

        if (String(text) === String(query)) return 100;
        if (t === q) return 60;
        if (t.startsWith(q)) return 80;
        if (t.includes(q)) return 60;

        const prefixLength = commonPrefixLength(t, q);
        if (prefixLength >= 3 && q.length - prefixLength <= 2) return 80;

        // Fuzzy: las letras del query aparecen en orden en el text
        let ti = 0;
        let matched = 0;
        for (const ch of q) {
            const idx = t.indexOf(ch, ti);
            if (idx === -1) return 0;
            matched++;
            ti = idx + 1;
        }
        return matched === q.length ? 30 + matched : 0;
    };

    /**
     * Resalta el match en el texto.
     * - Si hay match exacto/substring, resalta el primer match.
     * - Si hay match fuzzy, no resalta (devuelve el texto plano).
     */
    const highlight = (text, query) => {
        if (!text || !query) return escapeHtml(text);

        const stringText = String(text);
        const t = normalize(stringText);
        const q = normalize(query);
        const hasHtml = /[<>]/.test(stringText);

        if (hasHtml) {
            return escapeHtml(stringText);
        }

        const idx = t.indexOf(q);
        if (idx === -1) return escapeHtml(stringText);

        return escapeHtml(stringText.slice(0, idx)) +
               '<mark class="bg-yellow-200 dark:bg-yellow-900/50 text-gray-900 dark:text-white px-0.5 rounded">' +
               escapeHtml(stringText.slice(idx, idx + q.length)) +
               '</mark>' +
               escapeHtml(stringText.slice(idx + q.length));
    };

    const escapeHtml = (s) => {
        if (!s) return '';
        return String(s)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    };

    /**
     * Ordena un array de items por score de match contra un query.
     * `textKey` puede ser string o función que retorna el texto a matchear.
     */
    const sortByRelevance = (items, query, textKey) => {
        if (!query || query.length < 2) return items;
        const getText = typeof textKey === 'function' ? textKey : (item) => item[textKey];
        return items
            .map((item) => ({ item, score: fuzzyMatch(getText(item), query) }))
            .filter(({ score }) => score > 0)
            .sort((a, b) => b.score - a.score)
            .map(({ item }) => item);
    };

    return { fuzzyMatch, highlight, sortByRelevance };
}
