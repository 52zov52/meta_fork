<template>
    <div
        :class="[
            cardClass,
            'w-full flex flex-col overflow-hidden rounded border border-default shadow-sm hover:shadow-md transition-shadow duration-200 bg-white'
        ]"
    >
        <div
            :class="[
                'relative bg-cover bg-center cursor-pointer',
                'aspect-4/3 w-full shrink-0'
            ]"
            :style="{ backgroundImage: activity.photo_path
                ? `url('${apiPathStorage + activity.photo_path}')`
                : `url('${staticImg.bgCard}')` }"
            @click="$emit('open', activity)"
        >
            <button
                v-if="showWishlist && variant !== 'wishlist' && activity.is_wishlist == 0"
                class="absolute top-2 right-2 flex items-center bg-white rounded-full px-1 py-1.5 hover:text-red-500"
                @click.stop="$emit('wishlist-add', activity.id)"
            >
                <UTooltip text="Добавить в избранное">
                    <UIcon name="i-heroicons-heart" class="w-5" />
                </UTooltip>
            </button>

            <button
                v-if="showWishlist && (variant === 'wishlist' || activity.is_wishlist == 1)"
                class="absolute top-2 right-2 flex items-center bg-white rounded-full px-1 py-1.5 text-red-500"
                @click.stop="$emit('wishlist-remove', activity.id)"
            >
                <UTooltip text="Удалить из избранного">
                    <UIcon name="i-heroicons-heart" class="w-5" />
                </UTooltip>
            </button>

            <UBadge class="absolute bottom-2 left-2 px-2.5 py-1 rounded-full bg-white/90 text-gray-800 ring-1 ring-black/10 backdrop-blur-sm shadow-sm text-[11px] font-medium tracking-wide" color="neutral" variant="soft">
                {{ activity.status }}
            </UBadge>
        </div>

        <div class="flex flex-col space-y-1.5 p-1.5 flex-1">
            <div class="min-h-8 cursor-pointer" @click="$emit('open', activity)">
                <h2 class="text-xs font-semibold leading-4">{{ truncateText(activity.title, 80) }}</h2>
            </div>

            <div
                v-if="activity.description"
                class="min-h-9 max-h-12 overflow-hidden cursor-pointer"
                @click="$emit('open', activity)"
            >
                <p class="text-xs text-gray-600 line-clamp-2">{{ truncateText(activity.description, 120) }}</p>
            </div>

            <div class="mt-auto pt-2">
                <div class="flex items-center space-x-1 text-xs text-gray-500">
                    <UIcon name="i-heroicons-calendar-days" class="w-4 h-4 shrink-0" />
                    <span>{{ formatDisplayDate(activity.date_start) }} - {{ formatDisplayDate(activity.date_end) }}</span>
                </div>
                <div class="flex items-center space-x-1 text-xs text-gray-500 mt-1">
                    <UIcon name="i-heroicons-star" class="w-4 h-4 shrink-0 text-yellow-500" />
                    <span v-if="activity.is_dynamic_score && activity.user_personal_score != null">{{ activity.user_personal_score }} балл(ов) за участие</span>
                    <span v-else-if="!!activity.is_dynamic_score" class="text-info">Автоматический расчёт</span>
                    <span v-else>{{ activity.score }} балл(ов) за участие</span>
                </div>
                <div v-if="activity.planet_name" class="flex items-center space-x-1 text-xs text-gray-500 mt-1">
                    <UIcon name="i-heroicons-tag" class="w-4 h-4 shrink-0 text-primary" />
                    <span>{{ activity.planet_name }}</span>
                </div>
                <div v-if="activity.category_name" class="flex items-center space-x-1 text-xs text-gray-500 mt-1">
                    <UIcon name="i-heroicons-squares-2x2" class="w-4 h-4 shrink-0" />
                    <span>{{ activity.category_name }}</span>
                </div>
            </div>
        </div>

        <div class="p-1.5 flex flex-col gap-1.5">
            <UButton
                color="gray"
                variant="outline"
                block
                size="xs"
                class="border border-default transition-colors hover:bg-muted"
                @click.stop="$emit('open', activity)"
            >
                Подробнее
            </UButton>
            <UButton
                v-if="(variant === 'catalog' || variant === 'wishlist') && activity.is_registered == 0"
                color="primary"
                variant="solid"
                block
                size="xs"
                @click.stop="$emit('register', activity.id)"
            >
                Зарегистрироваться
            </UButton>
            <UButton
                v-if="(variant === 'catalog' || variant === 'wishlist') && activity.is_registered == 1"
                color="primary"
                variant="solid"
                block
                size="xs"
                @click.stop="$emit('undo-register', activity.id)"
            >
                Отменить регистрацию
            </UButton>
            <UButton
                v-if="variant === 'my' && activity.status_eng !== 'completed'"
                color="primary"
                variant="solid"
                block
                size="xs"
                @click.stop="$emit('undo-register', activity.id)"
            >
                Отменить регистрацию
            </UButton>
        </div>
    </div>
</template>

<script setup>
import { staticImg } from '~/assets/img'

defineProps({
    activity: { type: Object, required: true },
    apiPathStorage: { type: String, default: '' },
    /** 'catalog' = все мероприятия, 'my' = мои (только отмена), 'wishlist' = избранное (удалить из избранного + регистрация/отмена) */
    variant: { type: String, default: 'catalog', validator: (v) => ['catalog', 'my', 'wishlist'].includes(v) },
    cardClass: { type: String, default: '' },
});

defineEmits(['open', 'wishlist-add', 'wishlist-remove', 'register', 'undo-register']);

const showWishlist = true;

function formatDisplayDate(dateString) {
    if (!dateString) return 'Не указано';
    const date = new Date(dateString);
    return isNaN(date.getTime()) ? dateString : date.toLocaleDateString('ru-RU');
}

function truncateText(text, length) {
    if (text && text.length > length) return text.substring(0, length) + '...';
    return text || '';
}
</script>
