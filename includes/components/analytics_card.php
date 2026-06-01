<?php
/**
 * Reusable analytics stat card component.
 *
 * Required variables:
 *   string $id          HTML element id  (e.g. 'total-card')
 *   string $label       Card label text  (e.g. 'Total Tasks')
 *   int    $value       Numeric stat value
 *   string $color       Tailwind color name without prefix (e.g. 'blue', 'green', 'yellow', 'red')
 */
?>
<div id="<?= htmlspecialchars($id) ?>"
     class="analytics-card relative group overflow-hidden bg-white/[0.07] backdrop-blur-xl border border-white/10 rounded-2xl flex min-h-[172px] h-full flex-col justify-between items-start p-7 lg:p-8 shadow-[0_20px_60px_rgba(15,23,42,0.35)] transition-all duration-300 hover:-translate-y-1.5 hover:border-<?= $color ?>-400/30 hover:shadow-[0_24px_70px_rgba(var(--tw-shadow-color),0.22)]">
    <div class="absolute inset-x-0 top-0 h-px bg-gradient-to-r from-transparent via-<?= $color ?>-400/40 to-transparent"></div>
    <h3 class="stat-label text-slate-300 text-sm font-medium uppercase tracking-[0.18em] mb-4 transition-colors duration-300">
        <?= htmlspecialchars($label) ?>
    </h3>
    <span data-stat-value
          class="text-5xl lg:text-6xl font-black text-<?= $color ?>-400 tracking-tight leading-none drop-shadow-sm select-none">
        <?= (int) $value ?>
    </span>
</div>
