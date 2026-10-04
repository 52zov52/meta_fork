<template>
    <NuxtLayout name="student">
        <template #header>
            <StudentHeader />
        </template>

        <div class="flex items-center justify-between mt-2 mb-2">
            <UButton
                icon="i-heroicons-arrow-left"
                size="sm"
                color="neutral"
                variant="ghost"
                class="shrink-0"
                @click="goBackToProfile"
            >
                <span>Назад</span>
            </UButton>
        </div>

        <h2 class="text-base md:text-xl font-semibold">Мой профиль</h2>

        <div v-if="status === 'pending' || !user" class="mt-4">
            ...loading
        </div>

        <div v-if="status === 'success' && user" class="mt-4 grid grid-cols-1 lg:grid-cols-2 gap-4">
            <UForm :state="personalData" class="flex flex-col space-y-4 rounded-md shadow-md p-4" @submit="updatePersonalData">
                <h2 class="text-lg font-semibold">Персональная информация</h2>
                <hr class="w-full border-t border-muted">

                <UFormField label="Фамилия" name="last_name" required>
                    <UInput v-model="personalData.last_name" placeholder="Фамилия" class="w-full" />
                </UFormField>

                <UFormField label="Имя" name="first_name" required>
                    <UInput v-model="personalData.first_name" placeholder="Имя" class="w-full" />
                </UFormField>

                <UFormField label="Отчество" name="middle_name">
                    <UInput v-model="personalData.middle_name" placeholder="Отчество (при наличии)" class="w-full" />
                </UFormField>

                <UFormField label="Пол" name="gender" required>
                    <URadioGroup v-model="personalData.gender" :items="genderOptions" />
                </UFormField>

                <UFormField label="Дата рождения" name="birthday" required>
                    <UInput v-model="personalData.birthday" type="date" class="w-full" />
                </UFormField>

                <UFormField label="Регион" name="region_id" required>
                    <USelect
                        v-model="personalData.region_id"
                        :items="regionOptions"
                        placeholder="Выберите ваш регион"
                        class="w-full"
                    />
                </UFormField>

                <div class="flex justify-end pt-2">
                    <UButton type="submit" color="success" :loading="personalLoading">
                        Сохранить изменения
                    </UButton>
                </div>
            </UForm>

            <UForm :state="institutionData" class="flex flex-col space-y-4 rounded-md shadow-md p-4" @submit="updateInstitutionData">
                <h2 class="text-lg font-semibold">Информация об учреждении</h2>
                <hr class="w-full border-t border-muted">

                <UFormField label="Тип учреждения" name="institution_id" required>
                    <USelect
                        v-model="institutionData.institution_id"
                        :items="institutionOptions"
                        placeholder="Выберите тип учреждения"
                        class="w-full"
                    />
                </UFormField>

                <UFormField v-if="institutionData.institution_id === 4" label="Наименование учреждения" name="institution" required>
                    <UInput v-model="institutionData.institution" placeholder="Наименование учреждения" class="w-full" />
                </UFormField>

                <UFormField label="Класс или курс" name="course">
                    <UInput v-model="institutionData.course" type="number" placeholder="Класс или курс" class="w-full" />
                </UFormField>

                <UFormField label="Группа" name="student_group">
                    <UInput v-model="institutionData.student_group" placeholder="Группа" class="w-full" />
                </UFormField>

                <UFormField label="Направление" name="planet_id" required>
                    <USelect
                        v-model="institutionData.planet_id"
                        :items="planetOptions"
                        placeholder="Выберите ваше направление"
                        class="w-full"
                    />
                </UFormField>

                <div class="flex justify-end pt-2">
                    <UButton type="submit" color="success" :loading="institutionLoading">
                        Сохранить изменения
                    </UButton>
                </div>
            </UForm>

            <UForm :state="accountData" class="flex flex-col space-y-4 rounded-md shadow-md p-4" @submit="updateAccountData">
                <h2 class="text-lg font-semibold">Информация об аккаунте</h2>
                <hr class="w-full border-t border-muted">

                <UFormField label="Роль">
                    <p class="text-sm">{{ user?.role }}</p>
                </UFormField>

                <UFormField label="Email" name="email" required>
                    <UInput v-model="accountData.email" type="email" placeholder="email@example.ru" class="w-full" />
                </UFormField>

                <UFormField label="Фото на профиль" name="file">
                    <input
                        type="file"
                        class="bg-gray-100 border border-default rounded p-2 text-primary text-sm w-full"
                        @change="imageHandler"
                    >
                </UFormField>

                <div class="flex justify-end pt-2">
                    <UButton type="submit" color="success" :loading="accountLoading">
                        Сохранить изменения
                    </UButton>
                </div>
            </UForm>

            <UForm :state="userForm" class="flex flex-col space-y-4 rounded-md shadow-md p-4" @submit="updatePassword">
                <h2 class="text-lg font-semibold">Пароль</h2>
                <hr class="w-full border-t border-muted">

                <UFormField label="Старый пароль" name="old_password" required>
                    <UInput v-model="userForm.old_password" type="password" placeholder="Старый пароль" class="w-full" />
                </UFormField>

                <UFormField label="Пароль" name="password" required>
                    <UInput v-model="userForm.password" type="password" placeholder="Новый пароль" class="w-full" />
                </UFormField>

                <UFormField label="Подтверждение пароля" name="password_confirmation" required>
                    <UInput
                        v-model="userForm.password_confirmation"
                        type="password"
                        placeholder="Подтверждение пароля"
                        class="w-full"
                    />
                </UFormField>

                <div class="flex justify-end pt-2">
                    <UButton type="submit" color="success" :loading="passwordLoading">
                        Сохранить изменения
                    </UButton>
                </div>
            </UForm>
        </div>
    </NuxtLayout>
</template>

<script setup>
definePageMeta({
    middleware: ['sanctum:auth', 'role'],
    roles: ['student'],
    layout: false
});

onMounted(() => {
    fetchRegions();
    fetchPlanets();
    loadData();
});

const { user } = useSanctumAuth();
const router = useRouter();
const toast = useToast();
const { show: showApiError } = useApiError();
const status = ref('idle');
const { getRegions, getPlanets, getProfileEdit, updatePersonal, updateInstitution, updateAccount, updatePassword: updatePasswordApi, pingGetMy } = useStudentProfileEditApi()

const regions = ref([]);
const planets = ref([]);

const personalData = ref({
    last_name: "",
    first_name: "",
    middle_name: "",
    gender: "",
    birthday: "",
    region_id: 0,
});

const institutionData = ref({
    planet_id: 0,
    institution_id: 0,
    institution: "",
    course: "",
    student_group: "",
});

const accountData = ref({
    email: "",
    file: null,
});

const userForm = ref({
    old_password: "",
    password: "",
    password_confirmation: "",
});

const personalLoading = ref(false);
const institutionLoading = ref(false);
const accountLoading = ref(false);
const passwordLoading = ref(false);

const genderOptions = [
    { label: 'Мужской', value: '0' },
    { label: 'Женский', value: '1' }
];

const institutionOptions = [
    { label: 'ЧОУ ВО ЮУ (ИУБиП)', value: 1 },
    { label: 'ЧПОУ СИЮУ', value: 2 },
    { label: 'ЧПОУ ДИЮУ', value: 3 },
    { label: 'Другое', value: 4 }
];

const regionOptions = computed(() => [
    { label: 'Выберите ваш регион', value: 0 },
    ...regions.value.map(r => ({ label: r.name, value: r.id }))
]);

const planetOptions = computed(() => [
    { label: 'Выберите ваше направление', value: 0 },
    ...planets.value.map(p => ({ label: p.name, value: p.id }))
]);

function fetchRegions() {
    getRegions()
        .then((data) => {
            regions.value = data
    }).catch((error) => {
        showApiError(error, 'Ошибка получения регионов');
    });
}

function fetchPlanets() {
    getPlanets()
        .then((data) => {
            planets.value = data
    }).catch((error) => {
        showApiError(error, 'Ошибка получения планет');
    });
}

function loadData() {
    status.value = 'pending';

    const userId = user.value?.user_id
    if (userId == null) return
    getProfileEdit(userId).then((data) => {
        personalData.value.last_name = data.last_name ?? '';
        personalData.value.first_name = data.first_name ?? '';
        personalData.value.middle_name = data.middle_name ?? '';
        personalData.value.gender = String(data.gender ?? '');
        personalData.value.birthday = data.birthday ?? '';
        personalData.value.region_id = data.region_id ?? 0;

        institutionData.value.planet_id = data.planet_id ?? 0;
        institutionData.value.institution_id = data.institution_id ?? 0;
        institutionData.value.institution = data.institution ?? '';
        institutionData.value.course = data.course ?? '';
        institutionData.value.student_group = data.student_group ?? '';

        accountData.value.email = data.email ?? '';

        status.value = 'success';
    }).catch((error) => {
        status.value = 'error';
        showApiError(error, 'Произошла ошибка в процессе получения данных. Пожалуйста, попробуйте позже.');
    });
}

function updatePersonalData() {
    personalLoading.value = true;
    const userId = user.value?.user_id
    if (userId == null) return
    updatePersonal(userId, { ...personalData.value }).then((data) => {
        toast.add({ title: data.message ?? '' });
        refreshHeaderData();
    }).catch((error) => {
        showApiError(error, 'Ошибка');
    }).finally(() => {
        personalLoading.value = false;
    });
}

function updateInstitutionData() {
    institutionLoading.value = true;
    const userId = user.value?.user_id
    if (userId == null) return
    updateInstitution(userId, { ...institutionData.value }).then((data) => {
        toast.add({ title: data.message ?? '' });
        refreshHeaderData();
    }).catch((error) => {
        showApiError(error, 'Ошибка');
    }).finally(() => {
        institutionLoading.value = false;
    });
}

function updateAccountData() {
    accountLoading.value = true;
    const userId = user.value?.user_id
    if (userId == null) return

    const payload = new FormData();
    payload.append('email', accountData.value.email);
    if (accountData.value.file) {
        payload.append('file', accountData.value.file);
    }

    updateAccount(userId, payload).then((data) => {
        toast.add({ title: data.message ?? '' });
        refreshHeaderData();
    }).catch((error) => {
        showApiError(error, 'Ошибка');
    }).finally(() => {
        accountLoading.value = false;
    });
}

function updatePassword() {
    passwordLoading.value = true;
    const userId = user.value?.user_id
    if (userId == null) return
    updatePasswordApi(userId, { ...userForm.value }).then((data) => {
        toast.add({ title: data.message ?? '' });
        userForm.value = {
            old_password: "",
            password: "",
            password_confirmation: "",
        };
    }).catch((error) => {
        showApiError(error, 'Ошибка');
    }).finally(() => {
        passwordLoading.value = false;
    });
}

function imageHandler(e) {
    const target = e.target
    const file = target instanceof HTMLInputElement ? target.files?.[0] : undefined
    accountData.value.file = file || null;
}

function refreshHeaderData() {
    const userId = user.value?.user_id
    if (userId == null) return
    pingGetMy(userId).then(() => {
    }).catch(() => {
        console.error('Ошибка обновления данных header');
    });
}

function goBackToProfile() {
    router.push('/student/my-activities');
}
</script>
