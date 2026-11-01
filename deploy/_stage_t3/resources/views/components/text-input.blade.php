@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'border-gray-300 focus:border-violet-500 focus:ring-violet-500 rounded-xl shadow-sm dark:bg-[var(--color-obsidian-elevated)] dark:border-[var(--color-obsidian-border)] dark:text-white dark:placeholder:text-gray-500']) }}>
