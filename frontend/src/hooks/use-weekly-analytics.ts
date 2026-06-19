import { useCallback, useState } from 'react';

import { getWeeklyAnalytics } from '@/lib/api';
import type { WeeklyDaySummary } from '@/types/analytics';

type UseWeeklyAnalyticsResult = {
  days: WeeklyDaySummary[] | null;
  isLoading: boolean;
  isRefreshing: boolean;
  error: string | null;
  refresh: () => Promise<void>;
};

export function useWeeklyAnalytics(): UseWeeklyAnalyticsResult {
  const [days, setDays] = useState<WeeklyDaySummary[] | null>(null);
  const [isLoading, setIsLoading] = useState(true);
  const [isRefreshing, setIsRefreshing] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const refresh = useCallback(async () => {
    setError(null);
    setIsRefreshing(true);

    try {
      const data = await getWeeklyAnalytics();
      setDays(data);
    } catch {
      setError('Could not load your weekly analytics.');
    } finally {
      setIsLoading(false);
      setIsRefreshing(false);
    }
  }, []);

  return { days, isLoading, isRefreshing, error, refresh };
}
