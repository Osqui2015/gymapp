<section>
    <header>
        <h2 class="text-lg font-medium text-gray-900">
            Gestion de usuarios (Administrador)
        </h2>
        <p class="mt-1 text-sm text-gray-600">
            Crea usuarios con rol comun, alumno, trainer o administrador.
        </p>
    </header>

    <form method="POST" action="{{ route('admin.users.store') }}" class="mt-6 grid grid-cols-1 md:grid-cols-2 gap-4">
        @csrf

        <div>
            <x-input-label for="admin_nick" :value="__('Nick')" />
            <x-text-input id="admin_nick" name="nick" type="text" class="mt-1 block w-full" :value="old('nick')" required />
            <x-input-error class="mt-2" :messages="$errors->adminUserCreation->get('nick')" />
        </div>

        <div>
            <x-input-label for="admin_name" :value="__('Name')" />
            <x-text-input id="admin_name" name="name" type="text" class="mt-1 block w-full" :value="old('name')" required />
            <x-input-error class="mt-2" :messages="$errors->adminUserCreation->get('name')" />
        </div>

        <div>
            <x-input-label for="admin_email" :value="__('Email')" />
            <x-text-input id="admin_email" name="email" type="email" class="mt-1 block w-full" :value="old('email')" required />
            <x-input-error class="mt-2" :messages="$errors->adminUserCreation->get('email')" />
        </div>

        <div>
            <x-input-label for="admin_password" :value="__('Password')" />
            <x-text-input id="admin_password" name="password" type="password" class="mt-1 block w-full" required />
            <x-input-error class="mt-2" :messages="$errors->adminUserCreation->get('password')" />
        </div>

        <div>
            <x-input-label for="admin_role" value="Rol" />
            <select id="admin_role" name="role" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" required>
                <option value="comun" @selected(old('role') === 'comun')>comun</option>
                <option value="alumno" @selected(old('role') === 'alumno')>alumno</option>
                <option value="trainer" @selected(old('role') === 'trainer')>trainer</option>
                <option value="administrador" @selected(old('role') === 'administrador')>administrador</option>
            </select>
            <x-input-error class="mt-2" :messages="$errors->adminUserCreation->get('role')" />
        </div>

        <div>
            <x-input-label for="admin_trainer_id" value="Trainer (solo alumno)" />
            <select id="admin_trainer_id" name="trainer_id" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                <option value="">Sin trainer</option>
                @foreach ($trainers as $trainer)
                    <option value="{{ $trainer->id }}" @selected((string) old('trainer_id') === (string) $trainer->id)>{{ $trainer->name }}</option>
                @endforeach
            </select>
            <x-input-error class="mt-2" :messages="$errors->adminUserCreation->get('trainer_id')" />
        </div>

        <div class="md:col-span-2 flex items-center gap-4">
            <x-primary-button>Crear usuario</x-primary-button>

            @if (session('status') === 'admin-user-created')
                <p
                    x-data="{ show: true }"
                    x-show="show"
                    x-transition
                    x-init="setTimeout(() => show = false, 2500)"
                    class="text-sm text-gray-600"
                >Usuario creado.</p>
            @endif
        </div>
    </form>

    <div class="mt-8 overflow-x-auto">
        <h3 class="text-sm font-semibold text-gray-800 mb-3">Usuarios actuales</h3>
        <table class="min-w-full text-sm text-left border border-gray-200 rounded-lg overflow-hidden">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-3 py-2 border-b">Nick</th>
                    <th class="px-3 py-2 border-b">Nombre</th>
                    <th class="px-3 py-2 border-b">Email</th>
                    <th class="px-3 py-2 border-b">Rol</th>
                    <th class="px-3 py-2 border-b">Trainer</th>
                    <th class="px-3 py-2 border-b">Estado</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($managedUsers as $managedUser)
                    <tr class="odd:bg-white even:bg-gray-50">
                        <td class="px-3 py-2 border-b">{{ $managedUser->nick }}</td>
                        <td class="px-3 py-2 border-b">{{ $managedUser->name }}</td>
                        <td class="px-3 py-2 border-b">{{ $managedUser->email }}</td>
                        <td class="px-3 py-2 border-b">{{ $managedUser->normalizedRole() }}</td>
                        <td class="px-3 py-2 border-b">{{ optional($managedUser->trainer)->name ?? '-' }}</td>
                        <td class="px-3 py-2 border-b">{{ $managedUser->suspended ? 'Suspendido' : 'Activo' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</section>
