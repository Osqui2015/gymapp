<x-app-layout>
<div class="min-h-screen bg-gradient-to-br from-gray-50 to-indigo-50 dark:from-gray-900 dark:to-indigo-900 py-8">
    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">📊 Progreso Corporal</h1>
            <p class="mt-2 text-gray-600 dark:text-gray-400">Registra y controla tu evolución física</p>
        </div>

        <!-- Mensaje de recordatorio de 15 días -->
        <div id="mensaje-recordatorio" class="hidden mb-6 p-4 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-700 rounded-xl">
            <div class="flex items-center gap-3">
                <div class="flex-shrink-0">
                    <svg class="w-6 h-6 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div>
                    <p class="font-medium text-amber-800 dark:text-amber-200">¡Es hora de tu medición!</p>
                    <p class="text-sm text-amber-700 dark:text-amber-300">Han pasado más de 15 días desde tu último registro. Ingresa tus nuevas medidas para ver tu progreso.</p>
                </div>
            </div>
        </div>

        <!-- Información del último registro -->
        <div id="info-ultimo" class="hidden mb-6 p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-700 rounded-xl">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="flex-shrink-0">
                        <svg class="w-6 h-6 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                    <div>
                        <p class="font-medium text-green-800 dark:text-green-200">Último registro</p>
                        <p class="text-sm text-green-700 dark:text-green-300" id="fecha-ultimo"></p>
                    </div>
                </div>
                <div class="text-sm text-green-600 dark:text-green-400" id="dias-restantes"></div>
            </div>
        </div>

        <div class="grid lg:grid-cols-2 gap-8">
            <!-- Formulario de Registro -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6">
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-6 flex items-center gap-2">
                    <svg class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                    </svg>
                    Registrar Medidas
                </h2>

                <form id="form-progreso" class="space-y-6">
                    <!-- Datos Personales -->
                    <div class="border-b border-gray-200 dark:border-gray-700 pb-6">
                        <h3 class="text-lg font-medium text-gray-700 dark:text-gray-300 mb-4">Datos Personales</h3>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Peso (kg)</label>
                                <input type="number" step="0.01" name="peso" id="peso" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent" placeholder="Ej: 75.5">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Altura (cm)</label>
                                <input type="number" step="0.01" name="altura" id="altura" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent" placeholder="Ej: 175">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Edad</label>
                                <input type="number" name="edad" id="edad" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent" placeholder="Ej: 25">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Sexo</label>
                                <select name="sexo" id="sexo" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                                    <option value="">Seleccionar</option>
                                    <option value="masculino">Masculino</option>
                                    <option value="femenino">Femenino</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Medidas Corporales -->
                    <div>
                        <h3 class="text-lg font-medium text-gray-700 dark:text-gray-300 mb-4">Medidas Corporales (Lado Derecho)</h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mb-4 italic">Mide siempre del mismo lado para mantener la consistencia</p>
                        
                        <div class="grid grid-cols-2 gap-4">
                            <!-- Cuello -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Cuello (cm)
                                    <span class="text-xs text-gray-500">(Parte más ancha, bajo la nuez)</span>
                                </label>
                                <input type="number" step="0.1" name="cuello" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent" placeholder="Ej: 38">
                            </div>

                            <!-- Hombros -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Hombros (cm)
                                    <span class="text-xs text-gray-500">(Contorno completo)</span>
                                </label>
                                <input type="number" step="0.1" name="hombros" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent" placeholder="Ej: 110">
                            </div>

                            <!-- Pecho -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Pecho (cm)
                                    <span class="text-xs text-gray-500">(Parte más prominente)</span>
                                </label>
                                <input type="number" step="0.1" name="pecho" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent" placeholder="Ej: 100">
                            </div>

                            <!-- Brazos -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Brazos/Bíceps (cm)
                                    <span class="text-xs text-gray-500">(Parte más gruesa)</span>
                                </label>
                                <input type="number" step="0.1" name="brazos" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent" placeholder="Ej: 35">
                            </div>

                            <!-- Cintura -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Cintura (cm)
                                    <span class="text-xs text-gray-500">(Indicador de pérdida de grasa)</span>
                                </label>
                                <input type="number" step="0.1" name="cintura" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent" placeholder="Ej: 85">
                            </div>

                            <!-- Cadera -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Cadera/Glúteos (cm)
                                    <span class="text-xs text-gray-500">(Parte más ancha)</span>
                                </label>
                                <input type="number" step="0.1" name="cadera" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent" placeholder="Ej: 95">
                            </div>

                            <!-- Muslos -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Muslos (cm)
                                    <span class="text-xs text-gray-500">(Parte más gruesa)</span>
                                </label>
                                <input type="number" step="0.1" name="muslos" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent" placeholder="Ej: 55">
                            </div>

                            <!-- Pantorrillas -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Pantorrillas (cm)
                                    <span class="text-xs text-gray-500">(Parte más ancha)</span>
                                </label>
                                <input type="number" step="0.1" name="pantorrillas" class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:border-transparent" placeholder="Ej: 38">
                            </div>
                        </div>
                    </div>

                    <button type="submit" id="btn-guardar" class="w-full py-3 px-4 bg-gradient-to-r from-indigo-600 to-purple-600 text-white font-semibold rounded-xl hover:from-indigo-700 hover:to-purple-700 transition-all shadow-lg hover:shadow-xl transform hover:-translate-y-0.5">
                        <span id="texto-boton">Guardar Progreso</span>
                        <span id="loading-boton" class="hidden">
                            <svg class="animate-spin inline w-5 h-5 mr-2" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Guardando...
                        </span>
                    </button>
                </form>
            </div>

            <!-- Historial de Progreso -->
            <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6">
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-6 flex items-center gap-2">
                    <svg class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                    </svg>
                    Historial de Progreso
                </h2>

                <div id="tabla-progreso" class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Fecha</th>
                                <th class="px-3 py-2 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Peso</th>
                                <th class="px-3 py-2 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Cintura</th>
                                <th class="px-3 py-2 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Pecho</th>
                                <th class="px-3 py-2 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Brazos</th>
                                <th class="px-3 py-2 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Acción</th>
                            </tr>
                        </thead>
                        <tbody id="body-progreso" class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                            <tr>
                                <td colspan="6" class="px-3 py-8 text-center text-gray-500 dark:text-gray-400">
                                    <svg class="mx-auto w-12 h-12 text-gray-300 dark:text-gray-600 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                                    </svg>
                                    <p>No hay registros aún</p>
                                    <p class="text-sm mt-1">Ingresa tus medidas para comenzar</p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Modal de Detalle -->
                <div id="modal-detalle" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4">
                    <div class="bg-white dark:bg-gray-800 rounded-2xl shadow-2xl max-w-lg w-full max-h-[90vh] overflow-y-auto">
                        <div class="p-6">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white" id="modal-titulo">Detalle del Progreso</h3>
                                <button onclick="cerrarModal()" class="p-2 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors">
                                    <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>
                            <div id="modal-contenido">
                                <!-- Contenido dinámico -->
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tips de Medición -->
        <div class="mt-8 bg-gradient-to-r from-indigo-50 to-purple-50 dark:from-indigo-900/20 dark:to-purple-900/20 rounded-2xl p-6 border border-indigo-100 dark:border-indigo-800">
            <h3 class="text-lg font-semibold text-indigo-900 dark:text-indigo-100 mb-4 flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                Tips para tomar tus medidas
            </h3>
            <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-4 text-sm text-indigo-800 dark:text-indigo-200">
                <div class="flex items-start gap-2">
                    <span class="text-indigo-500 font-bold">1.</span>
                    <p>Usa siempre una cinta métrica flexible y el <strong>lado derecho</strong> de tu cuerpo.</p>
                </div>
                <div class="flex items-start gap-2">
                    <span class="text-indigo-500 font-bold">2.</span>
                    <p>Mide a primera hora de la mañana, después de ir al baño.</p>
                </div>
                <div class="flex items-start gap-2">
                    <span class="text-indigo-500 font-bold">3.</span>
                    <p>No puxes la cinta, debe estar cómoda pero ajustada.</p>
                </div>
                <div class="flex items-start gap-2">
                    <span class="text-indigo-500 font-bold">4.</span>
                    <p>Mantén los brazos a los lados del cuerpo al medir hombros.</p>
                </div>
                <div class="flex items-start gap-2">
                    <span class="text-indigo-500 font-bold">5.</span>
                    <p>Respirando normalmente al medir el pecho.</p>
                </div>
                <div class="flex items-start gap-2">
                    <span class="text-indigo-500 font-bold">6.</span>
                    <p>Si puedes, pide ayuda para las medidas de hombros.</p>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function() {
    cargarProgresos();

    document.getElementById('form-progreso').addEventListener('submit', function(e) {
        e.preventDefault();
        guardarProgreso();
    });
});

let progresosGlobal = [];

function cargarProgresos() {
    fetch('/api/progreso', {
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        progresosGlobal = data.progresos;
        actualizarUI(data);
    })
    .catch(error => {
        console.error('Error al cargar progresos:', error);
    });
}

function actualizarUI(data) {
    const progresos = data.progresos;
    const ultimo = data.ultimo;
    const puedeRegistrar = data.puede_registrar;

    // Mostrar/ocultar mensajes
    const mensajeRecordatorio = document.getElementById('mensaje-recordatorio');
    const infoUltimo = document.getElementById('info-ultimo');

    if (puedeRegistrar) {
        mensajeRecordatorio.classList.remove('hidden');
        infoUltimo.classList.add('hidden');
    } else if (ultimo) {
        mensajeRecordatorio.classList.add('hidden');
        infoUltimo.classList.remove('hidden');
        document.getElementById('fecha-ultimo').textContent = formatDate(ultimo.fecha);
        
        const diasPasados = Math.floor((new Date() - new Date(ultimo.fecha)) / (1000 * 60 * 60 * 24));
        const diasRestantes = 15 - diasPasados;
        document.getElementById('dias-restantes').textContent = `Podrás registrar en ${diasRestantes} días`;
    }

    // Llenar formulario con datos del último registro si existe
    if (ultimo) {
        if (ultimo.peso) document.getElementById('peso').value = ultimo.peso;
        if (ultimo.altura) document.getElementById('altura').value = ultimo.altura;
        if (ultimo.edad) document.getElementById('edad').value = ultimo.edad;
        if (ultimo.sexo) document.getElementById('sexo').value = ultimo.sexo;
    }

    // Renderizar tabla
    const tbody = document.getElementById('body-progreso');
    
    if (progresos.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="6" class="px-3 py-8 text-center text-gray-500 dark:text-gray-400">
                    <svg class="mx-auto w-12 h-12 text-gray-300 dark:text-gray-600 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                    </svg>
                    <p>No hay registros aún</p>
                    <p class="text-sm mt-1">Ingresa tus medidas para comenzar</p>
                </td>
            </tr>
        `;
        return;
    }

    tbody.innerHTML = progresos.map((p, index) => `
        <tr class="${index % 2 === 0 ? 'bg-white dark:bg-gray-800' : 'bg-gray-50 dark:bg-gray-700/50'}">
            <td class="px-3 py-2 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                ${formatDate(p.fecha)}
            </td>
            <td class="px-3 py-2 whitespace-nowrap text-sm text-center text-gray-600 dark:text-gray-300">
                ${p.peso ? p.peso + ' kg' : '-'}
            </td>
            <td class="px-3 py-2 whitespace-nowrap text-sm text-center text-gray-600 dark:text-gray-300">
                ${p.cintura ? p.cintura + ' cm' : '-'}
            </td>
            <td class="px-3 py-2 whitespace-nowrap text-sm text-center text-gray-600 dark:text-gray-300">
                ${p.pecho ? p.pecho + ' cm' : '-'}
            </td>
            <td class="px-3 py-2 whitespace-nowrap text-sm text-center text-gray-600 dark:text-gray-300">
                ${p.brazos ? p.brazos + ' cm' : '-'}
            </td>
            <td class="px-3 py-2 whitespace-nowrap text-sm text-center">
                <button onclick="verDetalle(${p.id})" class="text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-300 font-medium">
                    Ver
                </button>
            </td>
        </tr>
    `).join('');
}

function formatDate(dateStr) {
    const date = new Date(dateStr);
    return date.toLocaleDateString('es-ES', {
        day: '2-digit',
        month: 'short',
        year: 'numeric'
    });
}

function guardarProgreso() {
    const form = document.getElementById('form-progreso');
    const formData = new FormData(form);
    const data = Object.fromEntries(formData.entries());

    // Validar que al menos un campo tenga valor
    const tieneDatos = Object.values(data).some(v => v !== '' && v !== null);
    if (!tieneDatos) {
        alert('Por favor ingresa al menos una medida');
        return;
    }

    const btn = document.getElementById('btn-guardar');
    const textoBtn = document.getElementById('texto-boton');
    const loadingBtn = document.getElementById('loading-boton');

    btn.disabled = true;
    textoBtn.classList.add('hidden');
    loadingBtn.classList.remove('hidden');

    fetch('/api/progreso', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Content-Type': 'application/json',
            'Accept': 'application/json'
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(data => {
        if (data.message) {
            form.reset();
            cargarProgresos();
            
            // Mostrar notificación de éxito
            showNotification('Progreso guardado correctamente', 'success');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('Error al guardar', 'error');
    })
    .finally(() => {
        btn.disabled = false;
        textoBtn.classList.remove('hidden');
        loadingBtn.classList.add('hidden');
    });
}

function verDetalle(id) {
    fetch(`/api/progreso/detalle?id=${id}`, {
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            'Accept': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        mostrarModal(data);
    })
    .catch(error => {
        console.error('Error:', error);
    });
}

function mostrarModal(data) {
    const { progreso, comparacion } = data;
    const modal = document.getElementById('modal-detalle');
    const titulo = document.getElementById('modal-titulo');
    const contenido = document.getElementById('modal-contenido');

    titulo.textContent = `Progreso del ${formatDate(progreso.fecha)}`;

    const labels = {
        peso: 'Peso',
        altura: 'Altura',
        cuello: 'Cuello',
        hombros: 'Hombros',
        pecho: 'Pecho',
        brazos: 'Brazos',
        cintura: 'Cintura',
        cadera: 'Cadera',
        muslos: 'Muslos',
        pantorrillas: 'Pantorrillas'
    };

    const iconos = {
        peso: '⚖️',
        altura: '📏',
        cuello: '👤',
        hombros: '💪',
        pecho: '🫁',
        brazos: '💪',
        cintura: '🎯',
        cadera: '🍑',
        muslos: '🦵',
        pantorrillas: '🦵'
    };

    let html = '<div class="space-y-4">';

    // Datos personales
    if (progreso.peso || progreso.altura || progreso.edad || progreso.sexo) {
        html += '<div class="border-b border-gray-200 dark:border-gray-700 pb-4">';
        html += '<h4 class="font-medium text-gray-700 dark:text-gray-300 mb-3">📋 Datos Personales</h4>';
        html += '<div class="grid grid-cols-2 gap-3">';
        if (progreso.peso) html += crearItemDato('peso', labels.peso, progreso.peso + ' kg', comparacion.peso);
        if (progreso.altura) html += crearItemDato('altura', labels.altura, progreso.altura + ' cm', comparacion.altura);
        if (progreso.edad) html += crearItemDato('edad', 'Edad', progreso.edad + ' años', null);
        if (progreso.sexo) html += crearItemDato('sexo', 'Sexo', progreso.sexo.charAt(0).toUpperCase() + progreso.sexo.slice(1), null);
        html += '</div></div>';
    }

    // Medidas corporales
    html += '<div>';
    html += '<h4 class="font-medium text-gray-700 dark:text-gray-300 mb-3">📏 Medidas Corporales</h4>';
    html += '<div class="grid grid-cols-2 gap-3">';

    ['cuello', 'hombros', 'pecho', 'brazos', 'cintura', 'cadera', 'muslos', 'pantorrillas'].forEach(campo => {
        if (comparacion[campo]) {
            html += crearItemDato(campo, labels[campo], comparacion[campo].actual + ' cm', comparacion[campo]);
        }
    });

    html += '</div></div></div>';

    // Tips según progreso
    if (comparacion.cintura && comparacion.cintura.diferencia < 0) {
        html += `
            <div class="mt-4 p-3 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-700 rounded-lg">
                <p class="text-sm text-green-800 dark:text-green-200">
                    <strong>🎉 ¡Excelente!</strong> Tu cintura ha disminuido ${Math.abs(comparacion.cintura.diferencia)} cm. 
                    Esto indica que estás perdiendo grasa corporal.
                </p>
            </div>
        `;
    }

    if (comparacion.brazos && comparacion.brazos.diferencia > 0) {
        html += `
            <div class="mt-4 p-3 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-700 rounded-lg">
                <p class="text-sm text-blue-800 dark:text-blue-200">
                    <strong>💪 ¡Buen trabajo!</strong> Tus brazos han aumentado ${comparacion.brazos.diferencia} cm.
                    Estás ganando masa muscular.
                </p>
            </div>
        `;
    }

    contenido.innerHTML = html;
    modal.classList.remove('hidden');
}

function crearItemDato(campo, label, valor, comparacion) {
    let badge = '';
    let bgColor = 'bg-gray-100 dark:bg-gray-700';
    let textColor = 'text-gray-700 dark:text-gray-300';

    if (comparacion && comparacion.diferencia !== null) {
        if (comparacion.diferencia > 0) {
            badge = `<span class="text-xs font-medium px-2 py-0.5 rounded-full bg-green-100 text-green-700 dark:bg-green-900/50 dark:text-green-300">+${comparacion.diferencia}</span>`;
        } else if (comparacion.diferencia < 0) {
            badge = `<span class="text-xs font-medium px-2 py-0.5 rounded-full bg-red-100 text-red-700 dark:bg-red-900/50 dark:text-red-300">${comparacion.diferencia}</span>`;
        } else {
            badge = `<span class="text-xs font-medium px-2 py-0.5 rounded-full bg-gray-100 text-gray-600 dark:bg-gray-600 dark:text-gray-300">0</span>`;
        }
    }

    return `
        <div class="flex items-center justify-between p-2 rounded-lg ${bgColor}">
            <div>
                <p class="text-xs text-gray-500 dark:text-gray-400">${label}</p>
                <p class="font-medium ${textColor}">${valor}</p>
            </div>
            ${badge}
        </div>
    `;
}

function cerrarModal() {
    document.getElementById('modal-detalle').classList.add('hidden');
}

function showNotification(message, type) {
    const notification = document.createElement('div');
    notification.className = `fixed bottom-4 right-4 px-6 py-3 rounded-xl shadow-lg z-50 ${
        type === 'success' ? 'bg-green-500 text-white' : 'bg-red-500 text-white'
    }`;
    notification.innerHTML = message;
    document.body.appendChild(notification);

    setTimeout(() => {
        notification.remove();
    }, 3000);
}

// Cerrar modal al hacer clic fuera
document.getElementById('modal-detalle').addEventListener('click', function(e) {
    if (e.target === this) {
        cerrarModal();
    }
});
</script>
</x-app-layout>