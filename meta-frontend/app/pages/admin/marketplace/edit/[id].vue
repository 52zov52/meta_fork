<template>
    <form @submit.prevent="update">
            <hr role="presentation" class="my-10 w-full border-t border-muted">
            <section class="grid gap-x-8 gap-y-6 sm:grid-cols-2">
                <div class="space-y-1">
                    <h2 class="text-base/7 font-semibold sm:text-sm/6">Наименование<span class="ml-1 text-red-500">*</span></h2>
                    <p data-slot="text" class="text-base/6 sm:text-sm/6">Название приза или акции.</p>
                </div>
                <div class="flex flex-col space-y-1 justify-center">
                    <input v-model="marketplace.name" type="text" class="block w-full bg-gray-100 border border-default rounded p-2 text-primary text-sm" placeholder="Наименование приза или акции">
                    <ul v-for="(error, index) in errors.name" :key="index">
                        <li class="text-xs text-red-500">{{ error }}</li>
                    </ul>
                </div>
            </section>
            <hr role="presentation" class="my-10 w-full border-t border-muted">
            <section class="grid gap-x-8 gap-y-6 sm:grid-cols-2">
                <div class="space-y-1">
                    <h2 class="text-base/7 font-semibold sm:text-sm/6">Стоимость (баллы)<span class="ml-1 text-red-500">*</span></h2>
                    <p data-slot="text" class="text-base/6 sm:text-sm/6">Количество баллов, необходимое для обмена на приз.</p>
                </div>
                <div class="flex flex-col space-y-1 justify-center">
                    <input v-model="marketplace.score" type="text" class="block w-full bg-gray-100 border border-default rounded p-2 text-primary text-sm" placeholder="Стоимость (баллы)">
                    <ul v-for="(error, index) in errors.score" :key="index">
                        <li class="text-xs text-red-500">{{ error }}</li>
                    </ul>
                </div>
            </section>
            <hr role="presentation" class="my-10 w-full border-t border-muted">
            <section class="grid gap-x-8 gap-y-6 sm:grid-cols-2">
                <div class="space-y-1">
                    <h2 class="text-base/7 font-semibold sm:text-sm/6">В архиве</h2>
                    <p data-slot="text" class="text-base/6 sm:text-sm/6">Указывает активен ли приз или перемещен в архив.</p>
                </div>
                <div class="flex flex-col space-y-1 justify-center">
                    <USwitch v-model="marketplace.is_archive" />
                </div>
            </section>
            <hr role="presentation" class="my-10 w-full border-t border-muted">
            <section class="grid gap-x-8 gap-y-6 sm:grid-cols-2">
                <div class="space-y-1">
                    <h2 class="text-base/7 font-semibold sm:text-sm/6">Фото товара</h2>
                    <p data-slot="text" class="text-base/6 sm:text-sm/6">Загрузите новое изображение, если нужно заменить текущее.</p>
                </div>
                <div class="flex flex-col space-y-2 justify-center">
                    <img v-if="marketplace.photo_path" :src="config.public.API_PATH_STORAGE + marketplace.photo_path" class="w-24 h-24 object-cover rounded border border-default" >
                    <input type="file" accept=".jpg,.jpeg,.png,image/jpeg,image/png" class="block w-full bg-gray-100 border border-default rounded p-2 text-primary text-sm" @change="photoHandler">
                    <ul v-for="(error, index) in errors.photo" :key="index">
                        <li class="text-xs text-red-500">{{ error }}</li>
                    </ul>
                </div>
            </section>
            <hr role="presentation" class="my-10 w-full border-t border-muted">

            <div class="flex justify-end">
                <button type="submit" class="flex items-center space-x-2 bg-green-600 hover:bg-green-700 rounded-md px-2 py-1 text-sm text-white" :disabled="status === 'pending'">
                    <UIcon v-if="status === 'pending'" name="i-heroicons-arrow-path" class="animate-spin w-4 h-4" />
                    <span>Сохранить изменения</span>
                </button>
            </div>
        </form>
</template>

<script setup>
definePageMeta({
  middleware: ['sanctum:auth', 'role'],
  roles: ['admin'],
  layout: 'admin',
  title: 'Маркетплейс',
  description: 'Управляйте бонусной программой и настройками акций',
  breadcrumb: [{ label: 'Главная', to: '/admin' }, { label: 'Маркетплейс', to: '/admin/marketplace' }, { label: 'Редактирование' }],
});

onMounted(() => {
    loadData();
});

const route = useRoute();
const { get, post } = useApi();
const { show: showApiError } = useApiError();
const toast = useToast();
const config = useRuntimeConfig();

const apiUrl = ref('/api/v1/admin/marketplace/products/' + route.params.id);
const status = ref('idle');

const marketplace = ref({
    name: "",
    score: 0,
    photo_path: null,
    is_archive: false,
});

const photoFile = ref(null);

const errors = ref({
    name: [],
    score: [],
    photo: [],
});

function loadData() {
    get(apiUrl.value).then((data) => {
        marketplace.value.name = data.name;
        marketplace.value.score = data.score;
        marketplace.value.photo_path = data.photo_path;
        marketplace.value.is_archive = (data.is_archive == 0) ? false : true;
    }).catch(error => {
        showApiError(error, 'Произошла ошибка в процессе получения данных');
    });
}

function photoHandler(event) {
    photoFile.value = event.target.files?.[0] ?? null;
}

function buildFormData() {
    const formData = new FormData();
    formData.append('_method', 'PATCH');
    formData.append('name', marketplace.value.name);
    formData.append('score', String(marketplace.value.score));
    formData.append('is_archive', marketplace.value.is_archive ? '1' : '0');

    if (photoFile.value) {
        formData.append('photo', photoFile.value);
    }

    return formData;
}

function update() {
    status.value = 'pending';

    post('/api/v1/admin/marketplace/products/' + route.params.id, buildFormData()).then((data) => {
        status.value = 'success';
        toast.add({ title: data.message });
    }).catch(error => {
        status.value = 'error';

        if(error.statusCode == 422) {
            errors.value.name = error.response._data?.errors?.name ?? [];
            errors.value.score = error.response._data?.errors?.score ?? [];
            errors.value.photo = error.response._data?.errors?.photo ?? [];
        }
        
        showApiError(error, 'Ошибка');
    }).finally(() => {
        status.value = 'idle';
    })
}
</script>
