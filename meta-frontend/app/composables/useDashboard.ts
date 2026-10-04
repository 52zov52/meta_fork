import type { paths } from '~/types/api';

type DashboardResponse =
  paths['/api/v1/admin/statistics']['get']['responses']['200']['content']['application/json'];

export function useDashboard() {
  const { get } = useApi();

  const data = ref<DashboardResponse | null>(null);
  const loading = ref(false);
  const error = ref<string | null>(null);

  async function load() {
    loading.value = true;
    error.value = null;
    try {
      const response = await get<DashboardResponse>('/api/v1/admin/statistics');
      data.value = response;
    } catch (e) {
      console.error('Dashboard load error:', e);
      error.value = e instanceof Error ? e.message : 'Failed to load dashboard data';
    } finally {
      loading.value = false;
    }
  }

  return { data, loading, error, load };
}
