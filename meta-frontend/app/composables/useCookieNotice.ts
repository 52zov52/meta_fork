export function useCookieNotice() {
  const dismissed = ref(false);

  const initialize = () => {
    if (!import.meta.client) {
      return;
    }

    dismissed.value = localStorage.getItem('cookiesAccepted') === 'true';
  };

  const dismiss = () => {
    if (!import.meta.client) {
      return;
    }

    localStorage.setItem('cookiesAccepted', 'true');
    dismissed.value = true;
  };

  onMounted(() => {
    initialize();
  });

  return {
    dismissed,
    dismiss,
  };
}

