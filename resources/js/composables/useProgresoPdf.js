import { ref } from 'vue'

/**
 * Composable para exportar el progreso del usuario a PDF.
 *
 * Usa jspdf (ya en el bundle, lazy-loaded al hacer click) para generar
 * un PDF con:
 *   - Cover con nombre + fecha
 *   - Resumen de stats (streak, total workouts, total sets, esta semana)
 *   - Tabla de medidas corporales
 *   - Lista de metas
 *   - Lista de logros/medallas
 *
 * Uso:
 *   const { exportando, exportarPdf } = useProgresoPdf()
 *   await exportarPdf({ progresos, stats, metas, logros, userName })
 */
export function useProgresoPdf() {
    const exportando = ref(false)
    const error = ref(null)

    async function exportarPdf({ progresos = [], stats = {}, metas = [], logros = [], userName = 'Alumno' }) {
        exportando.value = true
        error.value = null
        try {
            // Lazy-load: jspdf pesa 386KB, solo lo bajamos cuando el user
            // pide el PDF. El bundle vendor-jspdf se cachea después.
            const { jsPDF } = await import('jspdf')
            const doc = new jsPDF({ unit: 'mm', format: 'a4' })

            const pageWidth = doc.internal.pageSize.getWidth()
            const pageHeight = doc.internal.pageSize.getHeight()
            const margin = 15
            const today = new Date().toLocaleDateString('es-AR', {
                year: 'numeric', month: 'long', day: 'numeric',
            })

            let y = margin

            // === Header ===
            doc.setFontSize(22)
            doc.setFont('helvetica', 'bold')
            doc.text('Reporte de Progreso', margin, y + 7)
            y += 12

            doc.setFontSize(11)
            doc.setFont('helvetica', 'normal')
            doc.text(`${userName} · Generado el ${today}`, margin, y)
            y += 8

            // Línea separadora
            doc.setDrawColor(99, 102, 241)
            doc.setLineWidth(0.5)
            doc.line(margin, y, pageWidth - margin, y)
            y += 8

            // === Resumen de stats ===
            doc.setFontSize(14)
            doc.setFont('helvetica', 'bold')
            doc.text('Resumen', margin, y)
            y += 7

            doc.setFontSize(10)
            doc.setFont('helvetica', 'normal')
            const statsRows = [
                ['Racha actual', `${stats.current_streak ?? 0} días`],
                ['Racha más larga', `${stats.longest_streak ?? 0} días`],
                ['Total entrenamientos', `${stats.total_workouts ?? 0}`],
                ['Total de sets', `${stats.total_sets ?? 0}`],
                ['Esta semana', `${stats.this_week ?? 0} entrenamientos`],
                ['Este mes', `${stats.this_month ?? 0} entrenamientos`],
                ['Últimos 30 días', `${stats.last_30_days ?? 0} entrenamientos`],
            ]
            statsRows.forEach(([k, v]) => {
                doc.text(`${k}:`, margin, y)
                doc.text(v, margin + 50, y)
                y += 5
            })
            y += 5

            // === Medidas corporales ===
            if (y > pageHeight - 50) { doc.addPage(); y = margin }
            doc.setFontSize(14)
            doc.setFont('helvetica', 'bold')
            doc.text('Medidas corporales', margin, y)
            y += 7

            if (!progresos.length) {
                doc.setFontSize(10)
                doc.setFont('helvetica', 'italic')
                doc.text('Sin registros todavía.', margin, y)
                y += 8
            } else {
                // Tabla de medidas: 10 columnas
                const cols = [
                    { label: 'Fecha', w: 24 },
                    { label: 'Peso', w: 16 },
                    { label: 'Altura', w: 16 },
                    { label: 'Cuello', w: 16 },
                    { label: 'Hombros', w: 17 },
                    { label: 'Pecho', w: 16 },
                    { label: 'Brazos', w: 16 },
                    { label: 'Cintura', w: 17 },
                    { label: 'Cadera', w: 16 },
                    { label: 'Muslos', w: 16 },
                ]

                // Helper: ¿hay una nueva página disponible?
                const ensureSpace = (needed = 8) => {
                    if (y + needed > pageHeight - margin) {
                        doc.addPage()
                        y = margin
                    }
                }

                // Header de tabla
                ensureSpace(10)
                doc.setFontSize(9)
                doc.setFont('helvetica', 'bold')
                doc.setFillColor(243, 244, 246)
                doc.rect(margin, y - 4, pageWidth - 2 * margin, 6, 'F')
                let x = margin + 1
                cols.forEach((c) => {
                    doc.text(c.label, x, y)
                    x += c.w
                })
                y += 4

                // Filas (ordenamos por fecha asc)
                const sorted = [...progresos].sort((a, b) =>
                    new Date(a.fecha) - new Date(b.fecha)
                )
                doc.setFont('helvetica', 'normal')
                sorted.forEach((p) => {
                    ensureSpace(6)
                    const fecha = new Date(p.fecha + 'T00:00:00')
                        .toLocaleDateString('es-AR', { day: '2-digit', month: '2-digit', year: '2-digit' })
                    const vals = [
                        fecha,
                        p.peso != null ? `${p.peso}` : '—',
                        p.altura != null ? `${p.altura}` : '—',
                        p.cuello != null ? `${p.cuello}` : '—',
                        p.hombros != null ? `${p.hombros}` : '—',
                        p.pecho != null ? `${p.pecho}` : '—',
                        p.brazos != null ? `${p.brazos}` : '—',
                        p.cintura != null ? `${p.cintura}` : '—',
                        p.cadera != null ? `${p.cadera}` : '—',
                        p.muslos != null ? `${p.muslos}` : '—',
                    ]
                    let cx = margin + 1
                    vals.forEach((v, i) => {
                        doc.text(String(v), cx, y)
                        cx += cols[i].w
                    })
                    y += 5
                })
                y += 5
            }

            // === Metas ===
            if (metas.length) {
                if (y > pageHeight - 40) { doc.addPage(); y = margin }
                doc.setFontSize(14)
                doc.setFont('helvetica', 'bold')
                doc.text('Metas', margin, y)
                y += 7
                doc.setFontSize(10)
                doc.setFont('helvetica', 'normal')
                metas.forEach((m) => {
                    if (y > pageHeight - margin - 5) { doc.addPage(); y = margin }
                    const check = m.completada ? '[X]' : '[ ]'
                    doc.text(`${check} ${m.titulo || m.descripcion || `Meta #${m.id}`}`, margin, y)
                    y += 5
                })
                y += 5
            }

            // === Logros ===
            if (logros.length) {
                if (y > pageHeight - 40) { doc.addPage(); y = margin }
                doc.setFontSize(14)
                doc.setFont('helvetica', 'bold')
                doc.text('Logros / Medallas', margin, y)
                y += 7
                doc.setFontSize(10)
                doc.setFont('helvetica', 'normal')
                logros.forEach((l) => {
                    if (y > pageHeight - margin - 5) { doc.addPage(); y = margin }
                    const fecha = l.fecha ? new Date(l.fecha).toLocaleDateString('es-AR') : ''
                    doc.text(`🏅 ${l.titulo || l.nombre || `Logro #${l.id}`}  —  ${fecha}`, margin, y)
                    y += 5
                })
            }

            // === Footer (en cada página) ===
            const pageCount = doc.internal.getNumberOfPages()
            for (let i = 1; i <= pageCount; i++) {
                doc.setPage(i)
                doc.setFontSize(8)
                doc.setFont('helvetica', 'normal')
                doc.setTextColor(150)
                doc.text(
                    `GymApp · Página ${i} de ${pageCount}`,
                    pageWidth / 2,
                    pageHeight - 8,
                    { align: 'center' }
                )
            }

            // === Descargar ===
            const filename = `progreso-${userName.toLowerCase().replace(/\s+/g, '-')}-${new Date().toISOString().split('T')[0]}.pdf`
            doc.save(filename)
        } catch (e) {
            console.error('Error exportando PDF:', e)
            error.value = e?.message || 'Error desconocido'
            throw e
        } finally {
            exportando.value = false
        }
    }

    return { exportando, error, exportarPdf }
}
