import { useQuery } from '@tanstack/react-query';

import { getWeeklyAnalytics } from '@/lib/api';
import { queryKeys } from '@/lib/query-keys';
import type { WeeklyDaySummary } from '@/types/analytics';

type UseWeeklyAnalyticsResult = {
  days: WeeklyDaySummary[] | undefined;
  isLoading: boolean;
  isRefreshing: boolean;
  error: string | null;
  refresh: () => Promise<void>;
};

export function useWeeklyAnalytics(): UseWeeklyAnalyticsResult {
  const query = useQuery({
    queryKey: queryKeys.weeklyAnalytics(),
    queryFn: getWeeklyAnalytics,
  });

  return {
    days: query.data,
    isLoading: query.isLoading,
    isRefreshing: query.isRefetching && !query.isLoading,
    error: query.error ? 'Could not load your weekly analytics.' : null,
    refresh: async () => {
      await query.refetch();
    },
  };
}
