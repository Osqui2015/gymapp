<template>
    <div
        class="min-h-screen bg-obsidian-canvas text-slate-100 antialiased selection:bg-indigo-500/30 selection:text-indigo-300"
    >
        <!-- TopBar Mobile -->
        <header
            class="sticky top-0 z-40 backdrop-blur-xl bg-obsidian-canvas/85 border-b border-obsidian-border/60 px-5 py-3.5 flex items-center justify-between md:hidden"
            data-purpose="app-header"
        >
            <div class="flex items-center space-x-2.5">
                <div
                    class="w-9 h-9 rounded-xl bg-gradient-to-tr from-accent-indigo via-accent-violet to-accent-cyan p-[1.5px] shadow-glow flex items-center justify-center"
                >
                    <div
                        class="w-full h-full bg-obsidian-canvas rounded-[10px] flex items-center justify-center"
                    >
                        <span
                            class="font-display text-transparent bg-clip-text bg-gradient-to-r from-accent-indigo to-accent-cyan font-bold text-lg"
                        >
                            G
                        </span>
                    </div>
                </div>
                <span
                    class="font-display font-bold text-xl tracking-tight text-white flex items-center gap-0.5"
                >
                    Gym<span class="text-accent-indigo">App</span>
                </span>
            </div>
            <div class="flex items-center space-x-2.5">
                <!-- Notification Button with Violet Badge -->
                <button
                    aria-label="Notificaciones"
                    class="relative p-2.5 rounded-xl bg-obsidian-surface border border-obsidian-border hover:border-slate-600 text-slate-300 hover:text-white transition-colors duration-200 cursor-pointer"
                    type="button"
                >
                    <svg
                        class="w-5 h-5"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="1.8"
                        viewBox="0 0 24 24"
                    >
                        <path
                            d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        />
                    </svg>
                    <span
                        class="absolute top-2 right-2 w-2 h-2 bg-accent-violet rounded-full ring-2 ring-obsidian-surface animate-pulse"
                    />
                </button>
                <!-- User Avatar -->
                <div
                    class="w-9 h-9 rounded-xl bg-gradient-to-br from-indigo-600 to-accent-violet text-white font-semibold text-xs flex items-center justify-center shadow-inner cursor-pointer hover:opacity-90 ring-1 ring-white/20 transition-transform active:scale-95"
                >
                    {{ userInitials }}
                </div>
            </div>
        </header>

        <!-- Main Desktop Container (Responsive max-w-7xl) -->
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-4 pb-20 md:py-6">
            <!-- Desktop Breadcrumbs -->
            <Breadcrumbs
                :items="[
                    { label: 'Inicio', href: '/dashboard' },
                    { label: 'Progreso & Evolución' },
                ]"
                class="hidden md:block mb-4"
            />

            <!-- Hero Banner -->
            <section
                class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-indigo-600 via-indigo-700 to-violet-900 p-5 md:p-6 shadow-glow border border-indigo-400/30 mb-5 md:mb-6"
                data-purpose="hero-banner"
            >
                <!-- Decorative Ambient Light Orbs -->
                <div
                    class="absolute -right-8 -top-8 w-36 h-36 bg-cyan-400/20 rounded-full blur-2xl pointer-events-none"
                />
                <div
                    class="absolute -left-6 -bottom-8 w-32 h-32 bg-violet-400/25 rounded-full blur-xl pointer-events-none"
                />
                <div
                    class="relative z-10 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4"
                >
                    <div>
                        <span
                            class="inline-block text-[11px] font-bold tracking-widest text-indigo-200 uppercase mb-1"
                        >
                            Analítica Corporal
                        </span>
                        <h1
                            class="text-2xl md:text-3xl font-bold font-display text-white tracking-tight leading-tight"
                        >
                            Progreso &amp; Evolución
                        </h1>
                        <p class="text-xs md:text-sm text-indigo-100/90 mt-2 font-normal max-w-xl">
                            Controlá tus medidas, metas y logros desbloqueados con métricas de alta
                            precisión.
                        </p>
                    </div>
                    <!-- Export PDF Button -->
                    <button
                        type="button"
                        @click="exportarProgresoPdf"
                        :disabled="exportandoPdf"
                        class="self-start sm:self-center flex items-center space-x-1.5 bg-white/15 hover:bg-white/25 active:scale-95 backdrop-blur-md px-3.5 py-2 rounded-xl border border-white/20 text-white text-xs font-semibold tracking-wide transition-all shadow-sm shrink-0 cursor-pointer disabled:opacity-50"
                        :title="exportandoPdf ? 'Generando PDF...' : 'Descargar reporte en PDF'"
                    >
                        <svg
                            v-if="!exportandoPdf"
                            class="w-3.5 h-3.5 text-white"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                            viewBox="0 0 24 24"
                        >
                            <path
                                d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                            />
                        </svg>
                        <svg
                            v-else
                            class="w-3.5 h-3.5 animate-spin text-white"
                            fill="none"
                            viewBox="0 0 24 24"
                        >
                            <circle
                                class="opacity-25"
                                cx="12"
                                cy="12"
                                r="10"
                                stroke="currentColor"
                                stroke-width="4"
                            />
                            <path
                                class="opacity-75"
                                fill="currentColor"
                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"
                            />
                        </svg>
                        <span>{{ exportandoPdf ? 'Generando...' : 'Exportar PDF' }}</span>
                    </button>
                </div>
            </section>

            <!-- Summary KPIs (4 metrics grid) -->
            <section
                class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-5 md:mb-6"
                data-purpose="summary-metrics-grid"
            >
                <!-- KPI 1: Series 30D -->
                <div
                    class="bg-obsidian-card p-4 rounded-2xl border border-obsidian-border/80 shadow-card-border relative overflow-hidden flex flex-col justify-between group"
                >
                    <div
                        class="absolute top-0 left-0 bottom-0 w-1 bg-gradient-to-b from-accent-indigo to-accent-violet rounded-l"
                    />
                    <div class="flex items-center justify-between mb-1.5">
                        <span
                            class="text-[11px] font-semibold uppercase tracking-wider text-slate-400"
                        >
                            Series 30D
                        </span>
                        <div
                            class="w-6 h-6 rounded-lg bg-indigo-500/10 flex items-center justify-center text-accent-indigo"
                        >
                            <svg
                                class="w-3.5 h-3.5"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    d="M3.75 3v11.25A2.25 2.25 0 006 16.5h2.25M3.75 3h-1.5m1.5 0h16.5m0 0h1.5m-1.5 0v11.25A2.25 2.25 0 0118 16.5h-2.25m-7.5 0h7.5m-7.5 0l-1 3m8.5-3l1 3m0 0l.5 1.5m-.5-1.5h-9.5m0 0l-.5 1.5"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                />
                            </svg>
                        </div>
                    </div>
                    <div>
                        <div class="text-3xl font-display font-extrabold text-white tracking-tight">
                            {{ progressStats.series30d }}
                        </div>
                        <p
                            class="text-[10px] text-slate-400 mt-1 font-medium flex items-center gap-1"
                        >
                            <span
                                v-if="progressStats.series30d > 0"
                                class="text-accent-violet font-semibold"
                            >
                                +este mes
                            </span>
                            <span v-else class="flex items-center gap-1">
                                <span class="w-1.5 h-1.5 rounded-full bg-slate-500 inline-block" />
                                Sin sesiones registradas
                            </span>
                        </p>
                    </div>
                </div>

                <!-- KPI 2: Racha Actual -->
                <div
                    class="bg-obsidian-card p-4 rounded-2xl border border-obsidian-border/80 shadow-card-border relative overflow-hidden flex flex-col justify-between"
                >
                    <div class="flex items-center justify-between mb-1.5">
                        <span
                            class="text-[11px] font-semibold uppercase tracking-wider text-slate-400"
                        >
                            Racha Actual
                        </span>
                        <div
                            class="w-6 h-6 rounded-lg bg-amber-500/10 flex items-center justify-center text-accent-amber"
                        >
                            <svg
                                class="w-3.5 h-3.5 fill-amber-500/20"
                                stroke="currentColor"
                                stroke-width="2"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    d="M15.362 5.214A8.252 8.252 0 0112 21 8.25 8.25 0 016.038 7.048 8.287 8.287 0 009 9.6a8.983 8.983 0 013.361-6.867 8.21 8.21 0 003 2.48z"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                />
                            </svg>
                        </div>
                    </div>
                    <div>
                        <div class="flex items-baseline space-x-1">
                            <span
                                class="text-3xl font-display font-extrabold text-accent-amber tracking-tight"
                            >
                                {{ progressStats.racha }}
                            </span>
                            <span class="text-xs text-slate-300 font-medium">días</span>
                        </div>
                        <p class="text-[10px] text-slate-400 mt-1 font-medium">
                            Récord máx:
                            <strong class="text-slate-300">
                                {{ Math.max(progressStats.racha, 5) }} días
                            </strong>
                        </p>
                    </div>
                </div>

                <!-- KPI 3: Frecuencia -->
                <div
                    class="bg-obsidian-card p-4 rounded-2xl border border-obsidian-border/80 shadow-card-border relative overflow-hidden flex flex-col justify-between"
                >
                    <div
                        class="absolute top-0 left-0 bottom-0 w-1 bg-gradient-to-b from-accent-cyan to-accent-emerald rounded-l"
                    />
                    <div class="flex items-center justify-between mb-1.5">
                        <span
                            class="text-[11px] font-semibold uppercase tracking-wider text-slate-400"
                        >
                            Frecuencia
                        </span>
                        <div
                            class="w-6 h-6 rounded-lg bg-emerald-500/10 flex items-center justify-center text-accent-emerald"
                        >
                            <svg
                                class="w-3.5 h-3.5"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                />
                            </svg>
                        </div>
                    </div>
                    <div>
                        <div class="text-3xl font-display font-extrabold text-white tracking-tight">
                            {{ progressStats.diasEntrenados7d }}
                        </div>
                        <p
                            class="text-[10px] text-accent-cyan mt-1 font-medium flex items-center gap-1"
                        >
                            <span>
                                {{
                                    Math.round(
                                        (progressStats.diasEntrenados7d /
                                            (progressStats.diasEntrenadosObjetivo || 5)) *
                                            100
                                    )
                                }}%
                            </span>
                            <span class="text-slate-400">del objetivo</span>
                        </p>
                    </div>
                </div>

                <!-- KPI 4: Logros -->
                <div
                    class="bg-obsidian-card p-4 rounded-2xl border border-obsidian-border/80 shadow-card-border relative overflow-hidden flex flex-col justify-between"
                >
                    <div class="flex items-center justify-between mb-1.5">
                        <span
                            class="text-[11px] font-semibold uppercase tracking-wider text-slate-400"
                        >
                            Logros
                        </span>
                        <div
                            class="w-6 h-6 rounded-lg bg-yellow-500/10 flex items-center justify-center text-yellow-400"
                        >
                            <svg
                                class="w-3.5 h-3.5"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    d="M16.5 18.75h-9m9 0a3 3 0 013 3h-15a3 3 0 013-3m9 0v-3.375c0-.621-.504-1.125-1.125-1.125h-.871M7.5 18.75v-3.375c0-.621.504-1.125 1.125-1.125h.872m5.003 0H9.996m5.003 0c.93 0 1.768-.458 2.29-1.164a6.719 6.719 0 001.21-4.836A4.5 4.5 0 0014.25 4.5h-4.5a4.5 4.5 0 00-4.249 5m9.499 0h.001"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                />
                            </svg>
                        </div>
                    </div>
                    <div>
                        <div class="flex items-baseline space-x-1.5">
                            <span
                                class="text-3xl font-display font-extrabold text-accent-amber tracking-tight"
                            >
                                {{ progressStats.logros }}
                            </span>
                            <span class="text-xs text-slate-400 font-semibold">/ 8</span>
                        </div>
                        <p
                            class="text-[10px] text-accent-emerald mt-1 font-medium flex items-center gap-1"
                        >
                            <svg class="w-3 h-3 inline" fill="currentColor" viewBox="0 0 20 20">
                                <path
                                    clip-rule="evenodd"
                                    d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z"
                                    fill-rule="evenodd"
                                />
                            </svg>
                            <span>
                                {{
                                    progressStats.logros > 0
                                        ? `${progressStats.logros} desbloqueados`
                                        : '1 nueva medalla'
                                }}
                            </span>
                        </p>
                    </div>
                </div>
            </section>

            <!-- Primary Actions -->
            <section class="flex items-center space-x-3 mb-5 md:mb-6" data-purpose="quick-actions">
                <button
                    @click="activeTab = 'medidas'; scrollToMedidas()"
                    class="flex-1 sm:flex-none bg-gradient-to-r from-accent-indigo via-indigo-600 to-accent-violet hover:opacity-95 active:scale-[0.98] transition-all text-white font-semibold py-3 px-5 rounded-xl shadow-glow flex items-center justify-center space-x-2 border border-white/20 text-sm tracking-wide cursor-pointer"
                    type="button"
                >
                    <svg
                        class="w-4 h-4"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2.2"
                        viewBox="0 0 24 24"
                    >
                        <path
                            d="M12 4.5v15m7.5-7.5h-15"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        />
                    </svg>
                    <span>Registrar Medidas</span>
                </button>
                <button
                    class="bg-obsidian-surface hover:bg-obsidian-elevated active:scale-95 border border-obsidian-border px-4 py-3 rounded-xl text-slate-300 hover:text-white flex items-center space-x-2 transition-all text-xs font-semibold cursor-pointer"
                    type="button"
                >
                    <svg
                        class="w-4 h-4 text-slate-400"
                        fill="none"
                        stroke="currentColor"
                        stroke-width="2"
                        viewBox="0 0 24 24"
                    >
                        <path
                            d="M10.5 6h9.75M10.5 6a1.5 1.5 0 11-3 0m3 0a1.5 1.5 0 10-3 0M3.75 6H7.5m3 12h9.75m-9.75 0a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m-3.75 0H7.5m9-6h3.75m-3.75 0a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m-9.75 0h9.75"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        />
                    </svg>
                    <span>Filtros</span>
                </button>
            </section>

            <!-- Segmented Nav Tabs -->
            <section class="overflow-x-auto no-scrollbar py-1 mb-5 md:mb-7" data-purpose="segmented-tabs">
                <nav
                    class="flex items-center space-x-1.5 p-1 bg-obsidian-surface/90 border border-obsidian-border rounded-xl backdrop-blur-md min-w-max"
                >
                    <!-- Medidas -->
                    <button
                        @click="activeTab = 'medidas'"
                        :class="[
                            activeTab === 'medidas'
                                ? 'bg-gradient-to-r from-accent-indigo/25 to-accent-violet/25 text-indigo-300 border border-indigo-500/40 shadow-sm font-semibold'
                                : 'text-slate-400 hover:text-slate-200 hover:bg-obsidian-elevated font-medium',
                        ]"
                        class="flex items-center space-x-2 px-3.5 py-2 rounded-lg text-xs transition-all cursor-pointer"
                        type="button"
                    >
                        <svg
                            class="w-3.5 h-3.5 text-accent-indigo"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                            viewBox="0 0 24 24"
                        >
                            <path
                                d="M16.862 4.487l1.688-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                            />
                        </svg>
                        <span>Medidas</span>
                    </button>

                    <!-- Metas -->
                    <button
                        @click="activeTab = 'metas'"
                        :class="[
                            activeTab === 'metas'
                                ? 'bg-gradient-to-r from-accent-indigo/25 to-accent-violet/25 text-indigo-300 border border-indigo-500/40 shadow-sm font-semibold'
                                : 'text-slate-400 hover:text-slate-200 hover:bg-obsidian-elevated font-medium',
                        ]"
                        class="flex items-center space-x-1.5 px-3 py-2 rounded-lg text-xs transition-all cursor-pointer"
                        type="button"
                    >
                        <span class="text-xs">🎯</span>
                        <span>Metas</span>
                        <span
                            v-if="metas.length > 0"
                            class="text-[10px] px-1.5 py-0.2 rounded-full bg-obsidian-elevated text-slate-300 border border-obsidian-border"
                        >
                            {{ metas.length }}
                        </span>
                    </button>

                    <!-- Galería -->
                    <button
                        @click="activeTab = 'fotos'"
                        :class="[
                            activeTab === 'fotos'
                                ? 'bg-gradient-to-r from-accent-indigo/25 to-accent-violet/25 text-indigo-300 border border-indigo-500/40 shadow-sm font-semibold'
                                : 'text-slate-400 hover:text-slate-200 hover:bg-obsidian-elevated font-medium',
                        ]"
                        class="flex items-center space-x-1.5 px-3 py-2 rounded-lg text-xs transition-all cursor-pointer"
                        type="button"
                    >
                        <span class="text-xs">📷</span>
                        <span>Galería</span>
                        <span
                            class="bg-indigo-500/20 text-indigo-300 text-[10px] font-bold px-1.5 py-0.5 rounded-full border border-indigo-500/30"
                        >
                            Pronto
                        </span>
                    </button>

                    <!-- Medallas -->
                    <button
                        @click="activeTab = 'logros'"
                        :class="[
                            activeTab === 'logros'
                                ? 'bg-gradient-to-r from-accent-indigo/25 to-accent-violet/25 text-indigo-300 border border-indigo-500/40 shadow-sm font-semibold'
                                : 'text-slate-400 hover:text-slate-200 hover:bg-obsidian-elevated font-medium',
                        ]"
                        class="flex items-center space-x-1.5 px-3 py-2 rounded-lg text-xs transition-all cursor-pointer"
                        type="button"
                    >
                        <span class="text-xs">🏆</span>
                        <span>Medallas</span>
                        <span
                            v-if="logros.length > 0"
                            class="text-[10px] px-1.5 py-0.2 rounded-full bg-amber-500/20 text-amber-300 border border-amber-500/30 font-semibold"
                        >
                            {{ logros.length }}
                        </span>
                    </button>
                </nav>
            </section>

            <!-- Medidas Section -->
            <div v-show="activeTab === 'medidas'" id="medidas-section">
                <MedidasTab
                    :progresos="progresos"
                    :ultimoRegistro="ultimoRegistro"
                    :puedeRegistrar="puedeRegistrar"
                    :diasRestantesParaRegistrar="diasRestantesParaRegistrar"
                    :guardando="guardando"
                    :form="form"
                    :metricaGrafica="metricaGrafica"
                    :formatFecha="formatFecha"
                    @save="guardarProgreso"
                    @ver-detalle="verDetalle"
                    @update:metricaGrafica="metricaGrafica = $event"
                />
            </div>

            <!-- Metas Section -->
            <MetasTab
                v-show="activeTab === 'metas'"
                :metas="metas"
                :creandoMeta="creandoMeta"
                @crear="crearMeta"
                @toggle="toggleMetaCompletada"
                @eliminar="eliminarMeta"
            />

            <!-- Fotos de progreso: Próximamente -->
            <div v-show="activeTab === 'fotos'" class="space-y-4">
                <div
                    class="bg-obsidian-card border border-obsidian-border rounded-2xl p-6 text-center relative overflow-hidden shadow-card-border"
                >
                    <span
                        class="bg-indigo-500/20 text-indigo-300 text-[10px] font-bold px-2 py-0.5 rounded-full border border-indigo-500/30 absolute top-4 right-4"
                    >
                        Próximamente
                    </span>
                    <div class="text-5xl mb-3 opacity-80">📸</div>
                    <h3 class="text-lg font-bold font-display text-white mb-1.5">
                        Fotos de Progreso
                    </h3>
                    <p class="text-xs text-slate-400 max-w-md mx-auto leading-relaxed">
                        Esta función está en desarrollo. Pronto vas a poder subir fotos frontales y
                        laterales para ver tu evolución física en el tiempo.
                    </p>
                    <a
                        href="/dashboard"
                        class="mt-4 inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-obsidian-surface border border-obsidian-border text-slate-300 hover:text-white text-xs font-semibold transition-all"
                    >
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M10 19l-7-7m0 0l7-7m-7 7h18"
                            />
                        </svg>
                        Volver al Dashboard
                    </a>
                </div>
            </div>

            <!-- Logros Section -->
            <LogrosTab
                v-show="activeTab === 'logros'"
                :logros="logros"
                :logrosStats="logrosStats"
                :formatFechaMedalla="formatFechaMedalla"
            />

            <!-- Modal de Detalle -->
            <DetalleMedidaModal
                :modal="modalDetalle"
                :formatFecha="formatFecha"
                @cerrar="cerrarModal"
            />
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, watch, nextTick, defineAsyncComponent } from 'vue';
import axios from 'axios';
import { useToast } from '../composables/useToast';
import { useUndoable } from '../composables/useUndoable';
import { useConfetti } from '../composables/useConfetti';
import { useProgresoPdf } from '../composables/useProgresoPdf';

import MedidasTab from './progreso/MedidasTab.vue';
import MetasTab from './progreso/MetasTab.vue';
import LogrosTab from './progreso/LogrosTab.vue';
import DetalleMedidaModal from './progreso/DetalleMedidaModal.vue';
import Breadcrumbs from './Breadcrumbs.vue';
import { useFormatters } from '@/composables/useFormatters';

const { formatDateMedium, formatDateShort } = useFormatters();

const toast = useToast();
const showNotification = (message, type = 'success') => toast.add(message, type);
const { bigCelebration, celebrate, mini } = useConfetti();

const activeTab = ref('medidas');

// TopBar user initials
const userInitials = computed(() => {
    const n = (window.__user?.name || window.__user?.nick || '').trim();
    if (!n) return 'OG';
    return n
        .split(/\s+/)
        .slice(0, 2)
        .map((w) => w[0])
        .join('')
        .toUpperCase();
});

const progresos = ref([]);
const puedeRegistrar = ref(true);
const ultimoRegistro = ref(null);
const guardando = ref(false);
const metricaGrafica = ref('peso');

const metas = ref([]);
const creandoMeta = ref(false);

const logros = ref([]);
const logrosStats = ref(null);

let chartInstance = null;

// Stats grid header
const progressStats = ref({
    series30d: 0,
    racha: 0,
    diasEntrenados7d: 0,
    diasEntrenadosObjetivo: 5,
    logros: 0,
});

const cargarProgressStats = async () => {
    try {
        const res = await axios.get('/api/stats/resumen');
        progressStats.value = {
            series30d: res.data?.total_sets_30d ?? 0,
            racha: res.data?.streak ?? 0,
            diasEntrenados7d: res.data?.this_week ?? 0,
            diasEntrenadosObjetivo: res.data?.objetivo_semanal ?? 5,
            logros: logros.value?.length ?? 0,
        };
    } catch (err) {
        console.error('[ProgresoContent] Error cargando progress stats:', err);
    }
};

const form = ref({
    peso: '',
    grasa_corporal: '',
    altura: '',
    edad: '',
    sexo: '',
    cuello: '',
    hombros: '',
    pecho: '',
    brazos: '',
    cintura: '',
    cadera: '',
    muslos: '',
    pantorrillas: '',
});

const modalDetalle = ref({
    mostrar: false,
    progreso: {},
    comparacion: {},
});

const scrollToMedidas = () => {
    const el = document.getElementById('medidas-section');
    if (el) el.scrollIntoView({ behavior: 'smooth', block: 'start' });
};

const diasRestantesParaRegistrar = computed(() => {
    if (!ultimoRegistro.value) return 0;
    const ultimo = new Date(ultimoRegistro.value.fecha);
    const hoy = new Date();
    const diasPasados = Math.floor((hoy - ultimo) / (1000 * 60 * 60 * 24));
    return Math.max(0, 14 - diasPasados);
});

const formatFecha = (dateStr) => {
    if (!dateStr) return '';
    try {
        const d = new Date(dateStr + (dateStr.includes('T') ? '' : 'T00:00:00'));
        return d.toLocaleDateString('es-ES', {
            day: '2-digit',
            month: 'short',
            year: 'numeric',
        });
    } catch {
        return dateStr;
    }
};

const formatFechaMedalla = (dateStr) => {
    if (!dateStr) return '';
    return formatDateMedium(dateStr);
};

const cargarProgresos = async () => {
    try {
        const response = await axios.get('/api/progreso');
        progresos.value = response.data.progresos || [];
        ultimoRegistro.value = response.data.ultimo || null;
        puedeRegistrar.value = response.data.puede_registrar;

        if (ultimoRegistro.value) {
            form.value = {
                peso: ultimoRegistro.value.peso || '',
                grasa_corporal: ultimoRegistro.value.grasa_corporal || '',
                altura: ultimoRegistro.value.altura || '',
                edad: ultimoRegistro.value.edad || '',
                sexo: ultimoRegistro.value.sexo || '',
                cuello: ultimoRegistro.value.cuello || '',
                hombros: ultimoRegistro.value.hombros || '',
                pecho: ultimoRegistro.value.pecho || '',
                brazos: ultimoRegistro.value.brazos || '',
                cintura: ultimoRegistro.value.cintura || '',
                cadera: ultimoRegistro.value.cadera || '',
                muslos: ultimoRegistro.value.muslos || '',
                pantorrillas: ultimoRegistro.value.pantorrillas || '',
            };
        }

        nextTick(() => initChart());
    } catch (error) {
        console.error('Error al cargar progresos:', error);
    }
};

const cargarMetas = async () => {
    try {
        const response = await axios.get('/api/metas');
        metas.value = response.data || [];
    } catch (error) {
        console.error('Error al cargar metas:', error);
    }
};

const cargarLogros = async () => {
    try {
        const response = await axios.get('/api/logros');
        logros.value = response.data.logros || [];
        logrosStats.value = response.data.stats || null;
        if (logros.value?.length) {
            progressStats.value.logros = logros.value.length;
        }
    } catch (error) {
        console.error('Error al cargar logros:', error);
    }
};

const guardarProgreso = async (formData) => {
    const tieneDatos = Object.keys(formData)
        .filter((k) => !['sexo', 'edad', 'altura'].includes(k))
        .some((k) => formData[k] !== '' && formData[k] !== null);

    if (!tieneDatos) {
        showNotification('Por favor, ingresa al menos una medida física.', 'error');
        return;
    }

    guardando.value = true;
    try {
        const response = await axios.post('/api/progreso', formData);
        showNotification(response.data.message || 'Progreso guardado correctamente', 'success');

        if (response.data.new_medals && response.data.new_medals.length > 0) {
            response.data.new_medals.forEach((medal) => {
                showNotification(
                    `🏆 ¡Felicidades! Desbloqueaste la medalla: ${medal.nombre}`,
                    'success'
                );
            });
            bigCelebration();
        } else {
            mini();
        }

        await cargarProgresos();
        await cargarLogros();
    } catch (error) {
        console.error('Error:', error);
        showNotification(
            error.response?.data?.message || 'Error al guardar el progreso corporal.',
            'error'
        );
    } finally {
        guardando.value = false;
    }
};

const verDetalle = async (id) => {
    try {
        const response = await axios.get('/api/progreso/detalle', { params: { id } });
        modalDetalle.value = {
            mostrar: true,
            progreso: response.data.progreso,
            comparacion: response.data.comparacion,
        };
    } catch (error) {
        console.error('Error al obtener detalle:', error);
        showNotification('No se pudo cargar el detalle del registro.', 'error');
    }
};

const cerrarModal = () => {
    modalDetalle.value.mostrar = false;
};

const crearMeta = async (nuevaMeta) => {
    creandoMeta.value = true;
    try {
        const response = await axios.post('/api/metas', nuevaMeta);
        showNotification(response.data.message || 'Meta creada con éxito.', 'success');
        await cargarMetas();
        await cargarLogros();
    } catch (error) {
        console.error('Error al crear meta:', error);
        showNotification(error.response?.data?.message || 'Error al crear la meta.', 'error');
    } finally {
        creandoMeta.value = false;
    }
};

const toggleMetaCompletada = async (meta) => {
    try {
        const response = await axios.post(`/api/metas/${meta.id}/completar`);
        showNotification(response.data.message || 'Meta actualizada.', 'success');

        if (response.data.new_medals && response.data.new_medals.length > 0) {
            response.data.new_medals.forEach((medal) => {
                showNotification(
                    `🏆 ¡Felicidades! Desbloqueaste la medalla: ${medal.nombre}`,
                    'success'
                );
            });
            bigCelebration();
        } else if (response.data.meta.completada) {
            celebrate();
        }

        await cargarMetas();
        await cargarLogros();
    } catch (error) {
        console.error('Error al actualizar meta:', error);
        showNotification('Error al actualizar la meta.', 'error');
    }
};

const eliminarMeta = async (id) => {
    const confirmed = await toast.confirm('¿Eliminar esta meta?', {
        title: 'Eliminar meta',
        confirmLabel: 'Sí, eliminar',
        type: 'error',
    });
    if (!confirmed) return;

    const idx = metas.value.findIndex((m) => m.id === id);
    const snapshot = idx >= 0 ? { ...metas.value[idx] } : null;

    const { cancelled } = await useUndoable({
        message: 'Meta eliminada',
        apply: () => {
            metas.value = metas.value.filter((m) => m.id !== id);
        },
        undo: () => {
            if (!snapshot) return;
            if (idx >= 0 && idx <= metas.value.length) {
                metas.value.splice(idx, 0, snapshot);
            } else {
                metas.value.push(snapshot);
            }
        },
        commit: () => axios.delete(`/api/metas/${id}`),
        onError: (err) => {
            console.error('Error al eliminar meta:', err);
        },
    });

    if (!cancelled) {
        await cargarLogros();
    }
};

// Chart.js initialization
const initChart = async () => {
    const ctx = document.getElementById('progresoChart');
    if (!ctx) return;
    if (chartInstance) chartInstance.destroy();

    const { Chart, registerables } = await import('chart.js');
    Chart.register(...registerables);

    const key = metricaGrafica.value;
    const validData = progresos.value
        .filter((p) => p[key] !== null && p[key] !== undefined && Number(p[key]) > 0)
        .map((p) => ({ fecha: p.fecha, valor: parseFloat(p[key]) }));

    if (validData.length === 0) return;
    validData.sort((a, b) => new Date(a.fecha) - new Date(b.fecha));

    const labels = validData.map((d) => {
        const date = new Date(d.fecha + 'T00:00:00');
        return formatDateShort(date);
    });
    const dataValues = validData.map((d) => d.valor);

    const gridColor = '#23293d';
    const textColor = '#94a3b8';
    const canvasCtx = ctx.getContext('2d');
    const gradient = canvasCtx.createLinearGradient(0, 0, 0, 260);
    gradient.addColorStop(0, 'rgba(99, 102, 241, 0.35)');
    gradient.addColorStop(1, 'rgba(99, 102, 241, 0.0)');

    chartInstance = new Chart(ctx, {
        type: 'line',
        data: {
            labels,
            datasets: [
                {
                    label: key,
                    data: dataValues,
                    borderColor: '#6366f1',
                    borderWidth: 3,
                    backgroundColor: gradient,
                    fill: true,
                    tension: 0.35,
                    pointBackgroundColor: '#6366f1',
                    pointBorderColor: '#0a0e18',
                    pointBorderWidth: 2,
                    pointRadius: 5,
                    pointHoverRadius: 7,
                    pointHoverBackgroundColor: '#8b5cf6',
                    pointHoverBorderColor: '#ffffff',
                    pointHoverBorderWidth: 2,
                },
            ],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: '#151926',
                    borderColor: '#23293d',
                    borderWidth: 1,
                    titleFont: { size: 12, weight: 'bold' },
                    bodyFont: { size: 12 },
                    padding: 10,
                    cornerRadius: 8,
                    displayColors: false,
                    callbacks: {
                        label: (context) =>
                            ` ${context.parsed.y} ${
                                key === 'peso'
                                    ? 'kg'
                                    : key === 'grasa_corporal'
                                      ? '%'
                                      : 'cm'
                            }`,
                    },
                },
            },
            scales: {
                y: {
                    grid: { color: gridColor, drawBorder: false },
                    ticks: { color: textColor, font: { family: 'Inter, system-ui' } },
                },
                x: {
                    grid: { display: false },
                    ticks: { color: textColor, font: { family: 'Inter, system-ui' } },
                },
            },
        },
    });
};

watch(metricaGrafica, () => nextTick(() => initChart()));
watch(activeTab, (newTab) => {
    if (newTab === 'medidas') nextTick(() => initChart());
});

onMounted(() => {
    cargarProgresos();
    cargarMetas();
    cargarLogros();
    cargarProgressStats();
});

// PDF Export
const { exportando: exportandoPdf, exportarPdf } = useProgresoPdf();

const exportarProgresoPdf = async () => {
    try {
        const [statsRes, userRes, metasRes, logrosRes] = await Promise.all([
            axios.get('/api/stats/resumen').catch(() => ({ data: {} })),
            axios.get('/api/user-info').catch(() => ({ data: {} })),
            axios.get('/api/metas').catch(() => ({ data: [] })),
            axios.get('/api/logros').catch(() => ({ data: [] })),
        ]);

        const stats = statsRes.data || {};
        const metasArr = (metasRes.data?.length ? metasRes.data : metas.value) || [];
        const logrosArr = (logrosRes.data?.length ? logrosRes.data : logros.value) || [];
        const nombre = userRes.data?.name || userRes.data?.nick || 'Alumno';

        await exportarPdf({
            progresos: progresos.value,
            stats,
            metas: metasArr,
            logros: logrosArr,
            userName: nombre,
        });
        toast.success('PDF generado ✓');
    } catch (e) {
        toast.apiError(e, 'No se pudo generar el PDF.');
    }
};
</script>

<style scoped>
.no-scrollbar::-webkit-scrollbar {
    display: none;
}
.no-scrollbar {
    -ms-overflow-style: none;
    scrollbar-width: none;
}
@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(8px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
.animate-fadeIn {
    animation: fadeIn 0.3s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}
</style>
