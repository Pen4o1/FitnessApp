import { useQuery } from '@tanstack/react-query';

import { getDailySummary } from '@/lib/api';
import { queryKeys } from '@/lib/query-keys';
import { todayDateString } from '@/lib/date';
import type { DailySummary } from '@/types/nutrition';

type UseDailySummaryResult = {
  summary: DailySummary | undefined;
  isLoading: boolean;
  isFetching: boolean;
  isRefreshing: boolean;
  error: string | null;
  refresh: () => Promise<void>;
};

export function useDailySummary(date?: string): UseDailySummaryResult {
  const targetDate = date ?? todayDateString();

  const query = useQuery({
    queryKey: queryKeys.dailySummary(targetDate),
    queryFn: () => getDailySummary(targetDate),
    placeholderData: (previousData) => previousData,
  });

  return {
    summary: query.data,
    isLoading: query.isLoading,
    isFetching: query.isFetching,
    isRefreshing: query.isRefetching && !query.isLoading,
    error: query.error ? 'Could not load your daily summary.' : null,
    refresh: async () => {
      await query.refetch();
    },
  };
}
