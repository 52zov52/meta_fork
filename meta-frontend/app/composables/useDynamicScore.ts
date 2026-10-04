export function useDynamicScore() {
  const { get, post } = useApi();
  return {
    getEducationLevels: () => get('/api/v1/education-levels'),
    getActivityScores: (id: number) => get(`/api/v1/activities/${id}/scores`),
    calculatePreview: (body: {
      category_id: number;
      parameters: Record<string, any>;
      target_education_level_ids?: number[] | null;
    }) => post('/api/v1/activities/calculate-preview', body),
  };
}
