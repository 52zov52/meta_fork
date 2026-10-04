export default defineNuxtPlugin(() => {
    const toast = useToast();
    const router = useRouter();

    const showToast = () => {
        const message = sessionStorage.getItem('redirectToast');
        if (message) {
            try {
                toast.add(JSON.parse(message));
            } catch {
                // Игнорируем если JSON сломан
            }
            sessionStorage.removeItem('redirectToast');
        }
    };

    // Показываем при старте приложения
    showToast();

    // Показываем после каждой навигации (SPA-редиректы)
    router.afterEach(() => {
        showToast();
    });
});
