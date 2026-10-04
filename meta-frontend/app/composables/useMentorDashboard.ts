import type { paths } from '~/types/api';

type MentorDashboardResponse =
  paths['/api/v1/mentor/statistics']['get']['responses']['200']['content']['application/json'];

export function useMentorDashboard() {
  const { get } = useApi();

  const data = ref<MentorDashboardResponse | null>(null);
  const loading = ref(false);
  const error = ref<string | null>(null);

  async function load() {
    loading.value = true;
    error.value = null;
    try {
      const response = await get<MentorDashboardResponse>('/api/v1/mentor/statistics');
      data.value = response;
    } catch (e) {
      console.error('Mentor dashboard load error:', e);
      error.value = e instanceof Error ? e.message : 'Failed to load dashboard data';
    } finally {
      loading.value = false;
    }
  }

  return { data, loading, error, load };
}
