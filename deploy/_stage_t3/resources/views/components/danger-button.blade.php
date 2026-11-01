<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center px-5 py-2.5 bg-gradient-to-br from-red-600 to-rose-600 border border-transparent rounded-xl font-bold text-xs text-white uppercase tracking-widest hover:brightness-110 active:brightness-95 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 transition ease-in-out duration-150 shadow-lg shadow-red-500/30']) }}>
    {{ $slot }}
</button>
