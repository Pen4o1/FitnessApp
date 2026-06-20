import { useCallback, useEffect, useState } from 'react';

import { getDailySummary } from '@/lib/api';
import { todayDateString } from '@/lib/date';
import type { DailySummary } from '@/types/nutrition';

type UseDailySummaryResult = {
  summary: DailySummary | null;
  isLoading: boolean;
  isRefreshing: boolean;
  error: string | null;
  refresh: () => Promise<void>;
};

export function useDailySummary(date?: string): UseDailySummaryResult {
  const [summary, setSummary] = useState<DailySummary | null>(null);
  const [isLoading, setIsLoading] = useState(true);
  const [isRefreshing, setIsRefreshing] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const targetDate = date ?? todayDateString();

  const refresh = useCallback(async () => {
    setError(null);
    setIsRefreshing(true);

    try {
      const data = await getDailySummary(targetDate);
      setSummary(data);
    } catch {
      setError('Could not load your daily summary.');
    } finally {
      setIsLoading(false);
      setIsRefreshing(false);
    }
  }, [targetDate]);

  useEffect(() => {
    void refresh();
  }, [refresh]);

  return { summary, isLoading, isRefreshing, error, refresh };
}
