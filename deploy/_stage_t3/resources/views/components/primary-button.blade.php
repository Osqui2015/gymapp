<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center justify-center px-5 py-2.5 bg-gradient-to-br from-[var(--color-violet-deep)] via-[var(--color-violet-primary)] to-[var(--color-violet-light)] border border-white/10 rounded-xl font-bold text-sm text-white uppercase tracking-wider hover:brightness-110 focus:outline-none focus:ring-2 focus:ring-violet-500 focus:ring-offset-2 transition ease-in-out duration-150 shadow-[0_8px_24px_var(--color-violet-glow)]']) }}>
    {{ $slot }}
</button>
