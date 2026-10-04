<template>
    <div
        class="group relative flex flex-col border border-default rounded-xl overflow-hidden hover:shadow-lg transition-all duration-300 bg-white h-full"
    >
        <div
            class="h-48 relative overflow-hidden cursor-pointer group/preview shrink-0"
            role="button"
            :aria-label="item.file_url ? `Предпросмотр файла ${item.title}` : `Открыть ссылку ${item.title}`"
            @click="$emit('click', item)"
        >
            <div v-if="item.file_url && isImageFile(item.extension)" class="h-full w-full">
                <img
                    :src="item.file_url"
                    :alt="item.title || item.original_name"
                    class="h-full w-full object-cover group-hover:scale-105 transition-transform duration-500"
                    loading="lazy"
                    @error="$emit('imageError', $event)"
                >
                <div class="absolute inset-0"/>
            </div>
            <div v-else class="h-full flex items-center justify-center bg-gray-100">
                <div class="text-center">
                    <UIcon
                        :name="getFileIcon(item.extension)"
                        :class="[
                            'w-16 h-16 mx-auto mb-3',
                            getFileIconColor(item.extension)
                        ]"
                    />
                    <span
                        v-if="item.extension"
                        class="text-sm font-medium text-gray-700 bg-white/80 px-3 py-1 rounded-full"
                    >
                        .{{ item.extension }}
                    </span>
                    <span
                        v-else
                        class="text-sm font-medium text-gray-700 bg-white/80 px-3 py-1 rounded-full"
                    >
                        Ссылка
                    </span>
                </div>
            </div>

            <button
                v-if="showDelete"
                title="Удалить"
                class="absolute top-2 right-2 flex items-center bg-white rounded-full px-2 py-2 hover:text-red-500 hover:bg-red-50 shadow-md transition-colors z-10"
                @click.stop="$emit('delete', item.id)"
            >
                <UIcon name="i-heroicons-trash" class="w-5 h-5" />
            </button>

            <div class="pointer-events-none absolute inset-0 bg-linear-to-t from-black/30 via-black/0 to-black/0 opacity-0 group-hover/preview:opacity-100 transition duration-200"/>
            <div class="pointer-events-none absolute inset-0 flex items-end justify-between p-3 opacity-0 group-hover/preview:opacity-100 transition duration-200">
                <span class="inline-flex items-center gap-1 rounded-full bg-white/90 px-3 py-1 text-xs font-semibold text-gray-800 shadow-sm">
                    <UIcon name="i-heroicons-eye" class="w-4 h-4" />
                    Открыть
                </span>
            </div>
        </div>

        <div class="p-5 grow flex flex-col">
            <h3
                class="text-lg font-semibold text-gray-900 mb-2 line-clamp-2 cursor-pointer hover:text-primary transition-colors"
                @click="$emit('click', item)"
            >
                {{ item.title || (item.original_name ? truncateText(item.original_name, 50) : 'Без названия') }}
            </h3>

            <div class="text-gray-600 text-sm mb-3 space-y-2">
                <a
                    v-if="item.file_url && item.original_name"
                    :href="item.file_url"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="inline-flex items-center text-primary hover:underline group/link"
                    @click.stop
                >
                    <UIcon name="i-heroicons-document" class="w-4 h-4 mr-1 shrink-0" />
                    <span class="truncate">{{ truncateText(item.original_name, 30) }}</span>
                    <UIcon name="i-heroicons-arrow-up-right" class="w-3 h-3 opacity-0 group-hover/link:opacity-100 ml-1 transition" />
                </a>

                <a
                    v-if="getLink(item)"
                    :href="getLink(item)"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="inline-flex items-center text-primary hover:underline group/link"
                    @click.stop
                >
                    <UIcon name="i-heroicons-link" class="w-4 h-4 mr-1 shrink-0" />
                    <span class="truncate">{{ getLink(item) }}</span>
                    <UIcon name="i-heroicons-arrow-up-right" class="w-3 h-3 opacity-0 group-hover/link:opacity-100 ml-1 transition" />
                </a>

                <p v-if="getDescriptionText(item)" class="line-clamp-3">
                    {{ getDescriptionText(item) }}
                </p>
            </div>

            <div class="flex flex-wrap gap-2 mt-auto">
                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                    <UIcon name="i-heroicons-calendar" class="w-3 h-3 mr-1" />
                    {{ formatDate(item.created_at) }}
                </span>

                <span
                    v-if="item.activities && item.activities.length > 0"
                    class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-primary/10 text-primary/80"
                >
                    <UIcon name="i-heroicons-academic-cap" class="w-3 h-3 mr-1" />
                    {{ item.activities[0].title }}
                </span>
            </div>
        </div>
    </div>
</template>

<script setup>
defineProps({
    item: {
        type: Object,
        required: true
    },
    showDelete: {
        type: Boolean,
        default: false
    }
});

defineEmits(['click', 'delete', 'imageError']);

function isImageFile(extension) {
    const imageExtensions = ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'svg', 'webp'];
    return imageExtensions.includes(extension?.toLowerCase());
}

function getFileIcon(extension) {
    const ext = extension?.toLowerCase();
    const icons = {
        'pdf': 'i-heroicons-document-text',
        'doc': 'i-heroicons-document-text',
        'docx': 'i-heroicons-document-text',
        'txt': 'i-heroicons-document-text',
        'jpg': 'i-heroicons-photo',
        'jpeg': 'i-heroicons-photo',
        'png': 'i-heroicons-photo',
        'gif': 'i-heroicons-photo',
        'zip': 'i-heroicons-archive-box',
        'rar': 'i-heroicons-archive-box',
        '7z': 'i-heroicons-archive-box',
        'mp4': 'i-heroicons-video-camera',
        'avi': 'i-heroicons-video-camera',
        'mov': 'i-heroicons-video-camera',
    };
    return icons[ext] || 'i-heroicons-document';
}

function getFileIconColor(extension) {
    const ext = extension?.toLowerCase();
    const colors = {
        'pdf': 'text-red-500',
        'doc': 'text-primary',
        'docx': 'text-primary',
        'jpg': 'text-green-500',
        'jpeg': 'text-green-500',
        'png': 'text-green-500',
        'zip': 'text-yellow-500',
        'rar': 'text-yellow-500',
    };
    return colors[ext] || 'text-gray-500';
}

function getLink(item) {
    if (!item.description) return null;
    const match = item.description.match(/^LINK:(.+?)(\r\n\r\n|\n\n|$)/);
    return match ? match[1].trim() : null;
}

function getDescriptionText(item) {
    if (!item.description) return null;
    const match = item.description.match(/^LINK:(.+?)(\r\n\r\n|\n\n)(.+)$/s);
    return match ? match[3].trim() :
           (item.description.startsWith('LINK:') ? null : item.description);
}

function truncateText(text, length) {
    if (!text) return '';
    if (text.length > length) {
        return text.substring(0, length) + " ...";
    }
    return text;
}

function formatDate(dateString) {
    if (!dateString) return 'Не указано';
    try {
        return new Date(dateString).toLocaleDateString('ru-RU');
    } catch {
        return dateString;
    }
}
</script>

<style scoped>
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.line-clamp-3 {
    display: -webkit-box;
    -webkit-line-clamp: 3;
    line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>
