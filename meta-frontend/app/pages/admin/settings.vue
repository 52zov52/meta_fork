<template>
    <div class="flex flex-col gap-4">
        <div :class="[cardClass, 'text-left max-w-3xl']">
            <div class="flex flex-col gap-1">
                <h2 class="font-medium text-base">Scoring blackout-периоды</h2>
                <p class="text-sm text-muted">
                    Настройка влияет на доступность подтверждения участия и привязки портфолио сразу после сохранения.
                </p>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                <div class="flex flex-col gap-1">
                    <label class="text-sm font-medium">Зима: начало</label>
                    <UInput v-model="blackoutForm.winter_start" placeholder="MM-DD" maxlength="5" :disabled="blackoutLoading || blackoutSaving" />
                </div>
                <div class="flex flex-col gap-1">
                    <label class="text-sm font-medium">Зима: конец</label>
                    <UInput v-model="blackoutForm.winter_end" placeholder="MM-DD" maxlength="5" :disabled="blackoutLoading || blackoutSaving" />
                </div>
                <div class="flex flex-col gap-1">
                    <label class="text-sm font-medium">Лето: начало</label>
                    <UInput v-model="blackoutForm.summer_start" placeholder="MM-DD" maxlength="5" :disabled="blackoutLoading || blackoutSaving" />
                </div>
                <div class="flex flex-col gap-1">
                    <label class="text-sm font-medium">Лето: конец</label>
                    <UInput v-model="blackoutForm.summer_end" placeholder="MM-DD" maxlength="5" :disabled="blackoutLoading || blackoutSaving" />
                </div>
            </div>
            <div class="flex flex-wrap gap-2 pt-1">
                <UButton color="neutral" variant="soft" :disabled="blackoutLoading || blackoutSaving" @click="resetBlackoutToDefaults">
                    Сбросить к defaults
                </UButton>
                <UButton color="primary" :loading="blackoutSaving" :disabled="blackoutLoading || blackoutSaving" @click="saveBlackoutSettings">
                    Сохранить
                </UButton>
            </div>
        </div>

        <div :class="[cardClass, 'text-left max-w-3xl']">
            <div class="flex flex-col gap-1">
                <h2 class="font-medium text-base">Маркетплейс</h2>
                <p class="text-sm text-muted">
                    Подсказка студентам о том, где и как забрать товары маркетплейса.
                </p>
            </div>
            <div class="flex flex-col gap-1">
                <label class="text-sm font-medium">Подсказка для выдачи</label>
                <UTextarea v-model="marketplaceForm.pickup_hint" :rows="4" autoresize :disabled="marketplaceLoading || marketplaceSaving" />
            </div>
            <div class="flex flex-wrap gap-2 pt-1">
                <UButton color="primary" :loading="marketplaceSaving" :disabled="marketplaceLoading || marketplaceSaving" @click="saveMarketplaceSettings">
                    Сохранить
                </UButton>
            </div>
        </div>
    </div>
</template>

<script setup>
definePageMeta({
    middleware: ['sanctum:auth', 'role'],
    roles: ['admin'],
    layout: 'admin',
    title: 'Настройки',
    breadcrumb: [{ label: 'Главная', to: '/admin' }, { label: 'Настройки' }],
});

const { get, put } = useApi();
const toast = useToast();

const cardClass = 'min-w-0 flex flex-col space-y-2 bg-elevated border border-default rounded-lg shadow-sm p-3 md:p-4 text-center';

const blackoutLoading = ref(false);
const blackoutSaving = ref(false);
const blackoutDefaults = ref({
    winter_start: '',
    winter_end: '',
    summer_start: '',
    summer_end: '',
});
const blackoutForm = reactive({
    winter_start: '',
    winter_end: '',
    summer_start: '',
    summer_end: '',
});
const marketplaceLoading = ref(false);
const marketplaceSaving = ref(false);
const marketplaceForm = reactive({
    pickup_hint: '',
});

function normalizeMmDd(value) {
    return String(value ?? '').trim();
}

function isMonthDayValid(value) {
    const normalized = normalizeMmDd(value);
    const match = normalized.match(/^(\d{2})-(\d{2})$/);
    if (!match) return false;
    const month = Number(match[1]);
    const day = Number(match[2]);
    return month >= 1 && month <= 12 && day >= 1 && day <= 31;
}

function isBlackoutFormValid() {
    return (
        isMonthDayValid(blackoutForm.winter_start)
        && isMonthDayValid(blackoutForm.winter_end)
        && isMonthDayValid(blackoutForm.summer_start)
        && isMonthDayValid(blackoutForm.summer_end)
    );
}

function applyBlackoutData(payload) {
    blackoutForm.winter_start = normalizeMmDd(payload?.winter_start);
    blackoutForm.winter_end = normalizeMmDd(payload?.winter_end);
    blackoutForm.summer_start = normalizeMmDd(payload?.summer_start);
    blackoutForm.summer_end = normalizeMmDd(payload?.summer_end);
}

function resetBlackoutToDefaults() {
    applyBlackoutData(blackoutDefaults.value);
}

async function loadBlackoutSettings() {
    blackoutLoading.value = true;
    try {
        const response = await get('/api/v1/scoring-blackout');
        blackoutDefaults.value = {
            winter_start: normalizeMmDd(response?.defaults?.winter_start),
            winter_end: normalizeMmDd(response?.defaults?.winter_end),
            summer_start: normalizeMmDd(response?.defaults?.summer_start),
            summer_end: normalizeMmDd(response?.defaults?.summer_end),
        };
        applyBlackoutData(response?.data ?? response?.defaults ?? {});
    } catch (error) {
        console.error('Blackout settings error:', error);
        toast.add({
            title: 'Не удалось загрузить blackout-периоды',
            color: 'error',
        });
    } finally {
        blackoutLoading.value = false;
    }
}

async function saveBlackoutSettings() {
    if (!isBlackoutFormValid()) {
        toast.add({
            title: 'Проверьте формат полей (MM-DD)',
            color: 'error',
        });
        return;
    }

    blackoutSaving.value = true;
    try {
        await put('/api/v1/admin/scoring-blackout', {
            winter_start: normalizeMmDd(blackoutForm.winter_start),
            winter_end: normalizeMmDd(blackoutForm.winter_end),
            summer_start: normalizeMmDd(blackoutForm.summer_start),
            summer_end: normalizeMmDd(blackoutForm.summer_end),
        });
        await loadBlackoutSettings();
        toast.add({
            title: 'Blackout-периоды сохранены',
            color: 'success',
        });
    } catch (error) {
        const message = error?.response?._data?.message ?? 'Не удалось сохранить blackout-периоды';
        toast.add({
            title: message,
            color: 'error',
        });
    } finally {
        blackoutSaving.value = false;
    }
}

async function loadMarketplaceSettings() {
    marketplaceLoading.value = true;
    try {
        const response = await get('/api/v1/admin/marketplace/settings');
        marketplaceForm.pickup_hint = String(response?.data?.pickup_hint ?? '');
    } catch (error) {
        console.error('Marketplace settings error:', error);
        toast.add({
            title: 'Не удалось загрузить настройки маркетплейса',
            color: 'error',
        });
    } finally {
        marketplaceLoading.value = false;
    }
}

async function saveMarketplaceSettings() {
    marketplaceSaving.value = true;
    try {
        await put('/api/v1/admin/marketplace/settings', {
            pickup_hint: marketplaceForm.pickup_hint.trim(),
        });
        await loadMarketplaceSettings();
        toast.add({
            title: 'Настройки маркетплейса сохранены',
            color: 'success',
        });
    } catch (error) {
        const message = error?.response?._data?.message ?? 'Не удалось сохранить настройки маркетплейса';
        toast.add({
            title: message,
            color: 'error',
        });
    } finally {
        marketplaceSaving.value = false;
    }
}

onMounted(() => {
    loadBlackoutSettings();
    loadMarketplaceSettings();
});
</script>
