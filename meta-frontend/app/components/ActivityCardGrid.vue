<template>
    <!-- min(100%,20rem) вместо 320px: иначе на узком экране колонка шире контейнера → горизонтальный скролл -->
    <!-- «Как 1/4 ряда при 4 карточках» только при широком контейнере — иначе на телефоне карточки на всю колонку, без сжатия до четверти экрана -->
    <div class="activity-card-grid grid gap-3 grid-cols-[repeat(auto-fit,minmax(min(100%,20rem),1fr))]">
        <slot />
    </div>
</template>

<style scoped>
.activity-card-grid {
    container-type: inline-size;
    container-name: activity-card-grid;
}

/* ≥40rem: ограничение ширины как у слота при 4 карточках в ряд; две карточки — две колонки + плотнее gap */
@container activity-card-grid (min-width: 40rem) {
    .activity-card-grid :deep(> :is(:only-child, :nth-child(1):nth-last-child(2), :nth-child(2):nth-last-child(1))) {
        max-width: min(100%, calc((100cqw - 2.25rem) / 4));
        justify-self: start;
    }

    .activity-card-grid:has(> :nth-child(2):last-child) {
        grid-template-columns: repeat(2, minmax(0, calc((100cqw - 2.25rem) / 4)));
        gap: 0.5rem;
    }
}
</style>
