<template>
    <div>
        <!-- Reminder / last record banners -->
        <div
            v-if="puedeRegistrar && progresos.length > 0"
            class="mb-6 p-4 bg-amber-500/10 border border-amber-500/30 rounded-2xl flex items-center gap-3 animate-pulse"
        >
            <div class="flex-shrink-0">
                <svg
                    class="w-6 h-6 text-accent-amber"
                    fill="none"
                    stroke="currentColor"
                    viewBox="0 0 24 24"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        stroke-width="2"
                        d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"
                    />
                </svg>
            </div>
            <div>
                <p class="font-bold text-accent-amber text-sm font-display">
                    ¡Es hora de tu medición!
                </p>
                <p class="text-xs text-slate-300 mt-0.5">
                    Han pasado más de 15 días desde tu último registro. Ingresa tus nuevas
                    medidas para ver tu progreso.
                </p>
            </div>
        </div>

        <div
            v-if="ultimoRegistro && !puedeRegistrar"
            class="mb-6 p-4 bg-emerald-500/10 border border-emerald-500/30 rounded-2xl"
        >
            <div class="flex items-center justify-between flex-wrap gap-2 text-sm">
                <div class="flex items-center gap-3">
                    <div class="flex-shrink-0">
                        <svg
                            class="w-6 h-6 text-accent-emerald"
                            fill="none"
                            stroke="currentColor"
                            viewBox="0 0 24 24"
                        >
                            <path
                                stroke-linecap="round"
                                stroke-linejoin="round"
                                stroke-width="2"
                                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"
                            />
                        </svg>
                    </div>
                    <div>
                        <p class="font-bold text-accent-emerald">Último registro</p>
                        <p class="text-xs text-slate-300" id="fecha-ultimo">
                            {{ formatFecha(ultimoRegistro.fecha) }}
                        </p>
                    </div>
                </div>
                <div class="text-xs font-semibold text-accent-cyan">
                    Podrás registrar un nuevo progreso en {{ diasRestantesParaRegistrar }} días
                </div>
            </div>
        </div>

        <!-- 2-Column Responsive Layout (Desktop: Side-by-Side, Mobile: Stacked) -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 items-start">
            <!-- Left Column: Formulario Registrar Medidas -->
            <div class="space-y-4">
                <!-- Section Header -->
                <div class="flex items-center space-x-2 pt-1 pb-0.5">
                    <div
                        class="w-8 h-8 rounded-lg bg-indigo-500/10 border border-indigo-500/20 flex items-center justify-center text-accent-indigo"
                    >
                        <svg
                            class="w-4 h-4"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                            viewBox="0 0 24 24"
                        >
                            <path
                                d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25zM6.75 12h.008v.008H6.75V12zm0 3h.008v.008H6.75V15zm0 3h.008v.008H6.75V18z"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                            />
                        </svg>
                    </div>
                    <div>
                        <h2 class="text-lg font-bold font-display tracking-tight text-white">
                            Registrar Medidas
                        </h2>
                        <p class="text-[11px] text-slate-400">
                            Actualiza tus registros corporales semanales
                        </p>
                    </div>
                </div>

                <form @submit.prevent="$emit('save', form)" class="space-y-4">
                    <!-- SUBSECTION 1: Datos Personales -->
                    <div
                        class="bg-obsidian-card border border-obsidian-border rounded-2xl p-4 shadow-card-border space-y-3.5"
                    >
                        <div class="flex items-center space-x-2 border-b border-obsidian-border/70 pb-3">
                            <span class="text-base">📋</span>
                            <h3 class="text-sm font-semibold text-white tracking-wide">
                                Datos Personales
                            </h3>
                        </div>

                        <!-- Peso & Grasa Grid -->
                        <div class="grid grid-cols-2 gap-3">
                            <div class="space-y-1.5">
                                <label
                                    class="block text-[11px] font-medium text-slate-300"
                                    for="peso"
                                >
                                    Peso <span class="text-slate-400 font-normal">(kg)</span>
                                </label>
                                <input
                                    id="peso"
                                    v-model.number="form.peso"
                                    type="number"
                                    step="0.01"
                                    placeholder="Ej: 75.5"
                                    :required="progresos.length === 0"
                                    class="w-full bg-obsidian-input border border-obsidian-border focus:border-accent-indigo focus:ring-1 focus:ring-accent-indigo rounded-xl px-3.5 py-2.5 text-sm text-white placeholder-slate-500 font-medium transition-all outline-none"
                                />
                            </div>
                            <div class="space-y-1.5">
                                <label
                                    class="block text-[11px] font-medium text-slate-300 truncate"
                                    for="grasa"
                                >
                                    % Grasa <span class="text-slate-400 font-normal">(opcional)</span>
                                </label>
                                <input
                                    id="grasa"
                                    v-model.number="form.grasa_corporal"
                                    type="number"
                                    step="0.1"
                                    min="3"
                                    max="65"
                                    placeholder="Ej: 15.2"
                                    class="w-full bg-obsidian-input border border-obsidian-border focus:border-accent-indigo focus:ring-1 focus:ring-accent-indigo rounded-xl px-3.5 py-2.5 text-sm text-white placeholder-slate-500 font-medium transition-all outline-none"
                                />
                            </div>
                        </div>

                        <!-- Altura & Edad Grid -->
                        <div class="grid grid-cols-2 gap-3">
                            <div class="space-y-1.5">
                                <label
                                    class="block text-[11px] font-medium text-slate-300"
                                    for="altura"
                                >
                                    Altura <span class="text-slate-400 font-normal">(cm)</span>
                                </label>
                                <input
                                    id="altura"
                                    v-model.number="form.altura"
                                    type="number"
                                    step="0.01"
                                    placeholder="Ej: 178"
                                    :required="progresos.length === 0"
                                    class="w-full bg-obsidian-input border border-obsidian-border focus:border-accent-indigo focus:ring-1 focus:ring-accent-indigo rounded-xl px-3.5 py-2.5 text-sm text-white placeholder-slate-500 font-medium transition-all outline-none"
                                />
                            </div>
                            <div class="space-y-1.5">
                                <label
                                    class="block text-[11px] font-medium text-slate-300"
                                    for="edad"
                                >
                                    Edad
                                </label>
                                <input
                                    id="edad"
                                    v-model.number="form.edad"
                                    type="number"
                                    placeholder="Ej: 25"
                                    :required="progresos.length === 0"
                                    class="w-full bg-obsidian-input border border-obsidian-border focus:border-accent-indigo focus:ring-1 focus:ring-accent-indigo rounded-xl px-3.5 py-2.5 text-sm text-white placeholder-slate-500 font-medium transition-all outline-none"
                                />
                            </div>
                        </div>

                        <!-- Field: Sexo -->
                        <div class="space-y-1.5">
                            <label
                                class="block text-[11px] font-medium text-slate-300"
                                for="sexo"
                            >
                                Sexo Biológico
                            </label>
                            <div class="relative">
                                <select
                                    id="sexo"
                                    v-model="form.sexo"
                                    :required="progresos.length === 0"
                                    class="w-full bg-obsidian-input border border-obsidian-border focus:border-accent-indigo focus:ring-1 focus:ring-accent-indigo rounded-xl px-3.5 py-2.5 text-sm text-white font-medium transition-all appearance-none cursor-pointer pr-10 outline-none"
                                >
                                    <option class="bg-obsidian-card text-slate-400" value="">
                                        Seleccionar
                                    </option>
                                    <option class="bg-obsidian-card text-white" value="masculino">
                                        Masculino
                                    </option>
                                    <option class="bg-obsidian-card text-white" value="femenino">
                                        Femenino
                                    </option>
                                    <option class="bg-obsidian-card text-white" value="otro">
                                        Otro / Prefiero no decir
                                    </option>
                                </select>
                                <div
                                    class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3.5 text-slate-400"
                                >
                                    <svg
                                        class="w-4 h-4"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="2"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            d="M19.5 8.25l-7.5 7.5-7.5-7.5"
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                        />
                                    </svg>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- SUBSECTION 2: Medidas Corporales (Lado Derecho) -->
                    <div
                        class="bg-obsidian-card border border-obsidian-border rounded-2xl p-4 shadow-card-border space-y-4"
                    >
                        <!-- Subtitle and consistency note -->
                        <div class="border-b border-obsidian-border/70 pb-3">
                            <div class="flex items-center space-x-2 mb-1">
                                <div
                                    class="w-6 h-6 rounded-md bg-accent-cyan/10 flex items-center justify-center text-accent-cyan"
                                >
                                    <svg
                                        class="w-3.5 h-3.5 rotate-45"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="2"
                                        viewBox="0 0 24 24"
                                    >
                                        <path
                                            d="M3.75 3.75v4.5m0-4.5h4.5m-4.5 0L9 9M3.75 20.25v-4.5m0 4.5h4.5m-4.5 0L9 15M20.25 3.75h-4.5m4.5 0v4.5m0-4.5L15 9m5.25 11.25h-4.5m4.5 0v-4.5m0 4.5L15 15"
                                            stroke-linecap="round"
                                            stroke-linejoin="round"
                                        />
                                    </svg>
                                </div>
                                <h3 class="text-sm font-semibold text-white tracking-wide">
                                    Medidas Corporales <span class="text-accent-cyan">(Lado Derecho)</span>
                                </h3>
                            </div>
                            <p class="text-[11px] text-slate-400 italic flex items-center gap-1.5 mt-1">
                                <svg
                                    class="w-3 h-3 text-accent-cyan/80 shrink-0"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                    />
                                </svg>
                                Mide siempre del mismo lado para mantener la consistencia.
                            </p>
                        </div>

                        <!-- Measurement Fields List with Context Badges -->
                        <div class="space-y-3">
                            <MedidaInput
                                v-for="m in medidasInputs"
                                :key="m.campo"
                                :value="form[m.campo]"
                                :label="m.label"
                                :hint="m.hint"
                                :badgeClass="m.badgeClass"
                                :placeholder="m.placeholder"
                                @change="(v) => (form[m.campo] = v)"
                            />
                        </div>

                        <!-- Submit Button Inside Card -->
                        <div class="pt-2">
                            <button
                                type="submit"
                                :disabled="guardando || !puedeRegistrar"
                                class="w-full bg-gradient-to-r from-accent-indigo via-accent-violet to-fuchsia-600 hover:opacity-95 active:scale-[0.99] text-white font-bold py-3.5 px-4 rounded-xl shadow-glow-violet flex items-center justify-center space-x-2 border border-white/20 transition-all disabled:opacity-50 disabled:cursor-not-allowed disabled:transform-none cursor-pointer"
                            >
                                <svg
                                    v-if="!guardando"
                                    class="w-5 h-5"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2.2"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        d="M4.5 12.75l6 6 9-13.5"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                    />
                                </svg>
                                <svg
                                    v-else
                                    class="animate-spin w-5 h-5 text-white"
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
                                <span class="text-sm tracking-wide">
                                    {{ guardando ? 'Guardando...' : 'Guardar Progreso' }}
                                </span>
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Right Column: Gráfico, Historial y Tips -->
            <div class="space-y-6">
                <!-- Evolution Chart Card (when progresos.length > 1) -->
                <div
                    v-show="progresos.length > 1"
                    class="bg-obsidian-card border border-obsidian-border rounded-2xl p-5 shadow-card-border"
                >
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4">
                        <div>
                            <h3
                                class="text-sm font-bold font-display text-white tracking-wide flex items-center gap-2"
                            >
                                <span>📈</span> Gráfico de Evolución Física
                            </h3>
                            <p class="text-[11px] text-slate-400 mt-0.5">
                                Monitorea los cambios de tus medidas en el tiempo
                            </p>
                        </div>
                        <div class="relative">
                            <select
                                :value="metricaGrafica"
                                @change="$emit('update:metricaGrafica', $event.target.value)"
                                class="bg-obsidian-input border border-obsidian-border rounded-xl px-3 py-1.5 text-xs text-white font-semibold focus:border-accent-indigo focus:ring-1 focus:ring-accent-indigo appearance-none pr-8 cursor-pointer outline-none"
                            >
                                <option class="bg-obsidian-card text-white" value="peso">
                                    Peso (kg)
                                </option>
                                <option class="bg-obsidian-card text-white" value="grasa_corporal">
                                    % Grasa corporal
                                </option>
                                <option class="bg-obsidian-card text-white" value="cintura">
                                    Cintura (cm)
                                </option>
                                <option class="bg-obsidian-card text-white" value="brazos">
                                    Brazos/Bíceps (cm)
                                </option>
                                <option class="bg-obsidian-card text-white" value="pecho">
                                    Pecho (cm)
                                </option>
                                <option class="bg-obsidian-card text-white" value="hombros">
                                    Hombros (cm)
                                </option>
                                <option class="bg-obsidian-card text-white" value="muslos">
                                    Muslos (cm)
                                </option>
                            </select>
                            <div
                                class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2 text-slate-400"
                            >
                                <svg
                                    class="w-3.5 h-3.5"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        d="M19.5 8.25l-7.5 7.5-7.5-7.5"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                    />
                                </svg>
                            </div>
                        </div>
                    </div>

                    <div
                        class="relative w-full bg-obsidian-surface/60 rounded-xl p-3 min-h-[260px] flex items-center justify-center"
                    >
                        <canvas id="progresoChart" class="w-full max-h-[260px]"></canvas>
                    </div>
                </div>

                <!-- Historial de Progreso -->
                <section
                    class="bg-obsidian-card border border-obsidian-border rounded-2xl p-5 shadow-card-border space-y-4"
                    data-purpose="history-section"
                >
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-2.5">
                            <div
                                class="w-7 h-7 rounded-lg bg-indigo-500/15 flex items-center justify-center text-accent-indigo"
                            >
                                <svg
                                    class="w-4 h-4"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2"
                                    viewBox="0 0 24 24"
                                >
                                    <path
                                        d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                    />
                                </svg>
                            </div>
                            <h3 class="text-sm font-bold font-display text-white tracking-wide">
                                Historial de Progreso
                            </h3>
                        </div>
                        <span
                            v-if="progresos.length > 0"
                            class="text-xs text-slate-400 font-medium"
                        >
                            {{ progresos.length }} {{ progresos.length === 1 ? 'registro' : 'registros' }}
                        </span>
                    </div>

                    <!-- Empty state cuando no hay registros -->
                    <div
                        v-if="progresos.length === 0"
                        class="py-9 px-4 flex flex-col items-center justify-center text-center rounded-xl bg-obsidian-surface/60 border border-dashed border-obsidian-border"
                    >
                        <div
                            class="w-14 h-14 rounded-2xl bg-obsidian-elevated flex items-center justify-center mb-3 shadow-inner border border-obsidian-border text-slate-500"
                        >
                            <svg
                                class="w-7 h-7 stroke-slate-500"
                                fill="none"
                                stroke-width="1.6"
                                viewBox="0 0 24 24"
                            >
                                <path
                                    d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                />
                            </svg>
                        </div>
                        <p class="text-sm font-semibold text-slate-200 mb-0.5">
                            No hay registros aún
                        </p>
                        <p class="text-xs text-slate-400 max-w-[220px]">
                            Ingresa tus medidas a la izquierda para comenzar tu historial evolutivo.
                        </p>
                    </div>

                    <!-- Desktop Table (hidden md:block) -->
                    <div
                        v-else
                        class="hidden md:block overflow-x-auto rounded-xl border border-obsidian-border/70"
                    >
                        <table class="min-w-full divide-y divide-obsidian-border/60">
                            <thead class="bg-obsidian-surface/90">
                                <tr>
                                    <th
                                        class="px-3 py-2.5 text-left text-[11px] font-semibold text-slate-400 uppercase tracking-wider"
                                    >
                                        Fecha
                                    </th>
                                    <th
                                        class="px-3 py-2.5 text-center text-[11px] font-semibold text-slate-400 uppercase tracking-wider"
                                    >
                                        Peso
                                    </th>
                                    <th
                                        class="px-3 py-2.5 text-center text-[11px] font-semibold text-slate-400 uppercase tracking-wider"
                                    >
                                        % Grasa
                                    </th>
                                    <th
                                        class="px-3 py-2.5 text-center text-[11px] font-semibold text-slate-400 uppercase tracking-wider"
                                    >
                                        Cintura
                                    </th>
                                    <th
                                        class="px-3 py-2.5 text-center text-[11px] font-semibold text-slate-400 uppercase tracking-wider"
                                    >
                                        Pecho
                                    </th>
                                    <th
                                        class="px-3 py-2.5 text-center text-[11px] font-semibold text-slate-400 uppercase tracking-wider"
                                    >
                                        Brazos
                                    </th>
                                    <th
                                        class="px-3 py-2.5 text-center text-[11px] font-semibold text-slate-400 uppercase tracking-wider"
                                    >
                                        Acción
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-obsidian-border/50 bg-obsidian-card">
                                <tr
                                    v-for="p in progresos"
                                    :key="p.id"
                                    class="hover:bg-obsidian-elevated/50 transition-colors"
                                >
                                    <td
                                        class="px-3 py-2.5 whitespace-nowrap text-xs font-semibold text-white"
                                    >
                                        {{ formatFecha(p.fecha) }}
                                    </td>
                                    <td
                                        class="px-3 py-2.5 whitespace-nowrap text-xs text-center text-slate-300 font-mono"
                                    >
                                        {{ p.peso ? p.peso + ' kg' : '-' }}
                                    </td>
                                    <td
                                        class="px-3 py-2.5 whitespace-nowrap text-xs text-center text-slate-300 font-mono"
                                    >
                                        {{ p.grasa_corporal ? p.grasa_corporal + '%' : '-' }}
                                    </td>
                                    <td
                                        class="px-3 py-2.5 whitespace-nowrap text-xs text-center text-slate-300 font-mono"
                                    >
                                        {{ p.cintura ? p.cintura + ' cm' : '-' }}
                                    </td>
                                    <td
                                        class="px-3 py-2.5 whitespace-nowrap text-xs text-center text-slate-300 font-mono"
                                    >
                                        {{ p.pecho ? p.pecho + ' cm' : '-' }}
                                    </td>
                                    <td
                                        class="px-3 py-2.5 whitespace-nowrap text-xs text-center text-slate-300 font-mono"
                                    >
                                        {{ p.brazos ? p.brazos + ' cm' : '-' }}
                                    </td>
                                    <td class="px-3 py-2.5 whitespace-nowrap text-xs text-center">
                                        <button
                                            @click="$emit('ver-detalle', p.id)"
                                            class="text-accent-indigo hover:text-indigo-300 font-semibold transition-colors cursor-pointer"
                                        >
                                            Ver
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Mobile cards (md:hidden) -->
                    <div
                        v-if="progresos.length > 0"
                        class="md:hidden divide-y divide-obsidian-border/70"
                    >
                        <div
                            v-for="p in progresos"
                            :key="p.id"
                            class="py-3 space-y-2.5 hover:bg-obsidian-elevated/30 transition-colors"
                        >
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-semibold text-white">
                                    {{ formatFecha(p.fecha) }}
                                </span>
                                <button
                                    @click="$emit('ver-detalle', p.id)"
                                    class="text-xs text-accent-indigo hover:text-indigo-300 font-semibold cursor-pointer"
                                >
                                    Ver detalle →
                                </button>
                            </div>
                            <div class="grid grid-cols-3 gap-2">
                                <div
                                    class="bg-obsidian-surface/70 border border-obsidian-border/50 rounded-xl p-2"
                                >
                                    <p
                                        class="text-[9px] text-slate-400 uppercase tracking-wider font-semibold"
                                    >
                                        Peso
                                    </p>
                                    <p class="text-xs font-bold text-white font-mono">
                                        {{ p.peso ? p.peso + ' kg' : '—' }}
                                    </p>
                                </div>
                                <div
                                    v-if="p.grasa_corporal"
                                    class="bg-obsidian-surface/70 border border-obsidian-border/50 rounded-xl p-2"
                                >
                                    <p
                                        class="text-[9px] text-slate-400 uppercase tracking-wider font-semibold"
                                    >
                                        % Grasa
                                    </p>
                                    <p class="text-xs font-bold text-white font-mono">
                                        {{ p.grasa_corporal }}%
                                    </p>
                                </div>
                                <div
                                    class="bg-obsidian-surface/70 border border-obsidian-border/50 rounded-xl p-2"
                                >
                                    <p
                                        class="text-[9px] text-slate-400 uppercase tracking-wider font-semibold"
                                    >
                                        Cintura
                                    </p>
                                    <p class="text-xs font-bold text-white font-mono">
                                        {{ p.cintura ? p.cintura + ' cm' : '—' }}
                                    </p>
                                </div>
                                <div
                                    class="bg-obsidian-surface/70 border border-obsidian-border/50 rounded-xl p-2"
                                >
                                    <p
                                        class="text-[9px] text-slate-400 uppercase tracking-wider font-semibold"
                                    >
                                        Pecho
                                    </p>
                                    <p class="text-xs font-bold text-white font-mono">
                                        {{ p.pecho ? p.pecho + ' cm' : '—' }}
                                    </p>
                                </div>
                                <div
                                    class="bg-obsidian-surface/70 border border-obsidian-border/50 rounded-xl p-2"
                                >
                                    <p
                                        class="text-[9px] text-slate-400 uppercase tracking-wider font-semibold"
                                    >
                                        Brazos
                                    </p>
                                    <p class="text-xs font-bold text-white font-mono">
                                        {{ p.brazos ? p.brazos + ' cm' : '—' }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <!-- Tips para tomar tus medidas -->
                <section
                    class="bg-gradient-to-br from-obsidian-card via-obsidian-card to-indigo-950/25 border border-indigo-900/40 rounded-2xl p-5 shadow-card-border space-y-3.5"
                    data-purpose="tips-card"
                >
                    <div class="flex items-center space-x-2 text-indigo-400">
                        <svg
                            class="w-5 h-5"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                            viewBox="0 0 24 24"
                        >
                            <path
                                d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z"
                                stroke-linecap="round"
                                stroke-linejoin="round"
                            />
                        </svg>
                        <h3 class="text-sm font-bold font-display text-white tracking-wide">
                            Tips para tomar tus medidas
                        </h3>
                    </div>
                    <ol class="space-y-2.5 text-xs text-slate-300 leading-relaxed pl-1">
                        <li
                            v-for="(tip, idx) in tips"
                            :key="idx"
                            class="flex items-start space-x-2"
                        >
                            <span class="font-bold text-accent-indigo shrink-0">{{ idx + 1 }}.</span>
                            <span v-html="tip" />
                        </li>
                    </ol>
                </section>
            </div>
        </div>
    </div>
</template>

<script setup>
import MedidaInput from './MedidaInput.vue';

const props = defineProps({
    progresos: { type: Array, required: true },
    ultimoRegistro: { type: Object, default: null },
    puedeRegistrar: { type: Boolean, required: true },
    diasRestantesParaRegistrar: { type: Number, required: true },
    guardando: { type: Boolean, required: true },
    form: { type: Object, required: true },
    metricaGrafica: { type: String, required: true },
    formatFecha: { type: Function, required: true },
});

defineEmits(['save', 'ver-detalle', 'update:metricaGrafica']);

const medidasInputs = [
    {
        campo: 'cuello',
        label: 'Cuello',
        hint: 'Bajo la nuez',
        badgeClass: 'text-slate-400 bg-obsidian-elevated border-obsidian-border',
        placeholder: '38',
    },
    {
        campo: 'hombros',
        label: 'Hombros',
        hint: 'Contorno completo',
        badgeClass: 'text-slate-400 bg-obsidian-elevated border-obsidian-border',
        placeholder: '110',
    },
    {
        campo: 'pecho',
        label: 'Pecho',
        hint: 'Prominente',
        badgeClass: 'text-slate-400 bg-obsidian-elevated border-obsidian-border',
        placeholder: '100',
    },
    {
        campo: 'brazos',
        label: 'Brazos / Bíceps',
        hint: 'Parte más gruesa',
        badgeClass: 'text-indigo-300 bg-indigo-500/10 border-indigo-500/20',
        placeholder: '35',
    },
    {
        campo: 'cintura',
        label: 'Cintura',
        hint: 'Pérdida de grasa',
        badgeClass: 'text-emerald-400 bg-emerald-500/10 border-emerald-500/20',
        placeholder: '85',
    },
    {
        campo: 'cadera',
        label: 'Cadera / Glúteos',
        hint: 'Parte más ancha',
        badgeClass: 'text-slate-400 bg-obsidian-elevated border-obsidian-border',
        placeholder: '95',
    },
    {
        campo: 'muslos',
        label: 'Muslos',
        hint: 'Parte más gruesa',
        badgeClass: 'text-slate-400 bg-obsidian-elevated border-obsidian-border',
        placeholder: '55',
    },
    {
        campo: 'pantorrillas',
        label: 'Pantorrillas',
        hint: 'Parte más ancha',
        badgeClass: 'text-slate-400 bg-obsidian-elevated border-obsidian-border',
        placeholder: '38',
    },
];

const tips = [
    'Usa siempre una cinta métrica flexible y el <strong class="text-white font-semibold">lado derecho</strong> de tu cuerpo.',
    'Mide a <strong class="text-white font-semibold">primera hora de la mañana</strong>, después de ir al baño.',
    'No presiones la cinta, debe estar cómoda pero ajustada uniformemente.',
    'Mantén los brazos a los lados del cuerpo al medir los hombros.',
    'Respira normalmente al medir el pecho sin inflar en exceso los pulmones.',
    'Si puedes, pide ayuda para las medidas de la espalda y hombros.',
];
</script>
