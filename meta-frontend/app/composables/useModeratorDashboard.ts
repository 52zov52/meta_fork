import type { paths } from '~/types/api';

// Moderator statistics endpoint returns the same structure as admin,
// but the OpenAPI spec currently only declares a 401 response.
// We reuse the admin dashboard type since the backend shape is identical.
type ModeratorDashboardResponse =
  paths['/api/v1/admin/statistics']['get']['responses']['200']['content']['application/json'];

export function useModeratorDashboard() {
  const { get } = useApi();

  const data = ref<ModeratorDashboardResponse | null>(null);
  const loading = ref(false);
  const error = ref<string | null>(null);

  async function load() {
    loading.value = true;
    error.value = null;
    try {
      const response = await get<ModeratorDashboardResponse>('/api/v1/moderator/statistics');
      data.value = response;
    } catch (e) {
      console.error('Moderator dashboard load error:', e);
      error.value = e instanceof Error ? e.message : 'Failed to load dashboard data';
    } finally {
      loading.value = false;
    }
  }

  return { data, loading, error, load };
}
