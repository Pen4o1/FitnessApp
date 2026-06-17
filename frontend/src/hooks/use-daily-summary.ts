import { useCallback, useState } from 'react';

import { getDailySummary } from '@/lib/api';
import type { DailySummary } from '@/types/nutrition';

type UseDailySummaryResult = {
  summary: DailySummary | null;
  isLoading: boolean;
  isRefreshing: boolean;
  error: string | null;
  refresh: () => Promise<void>;
};

function todayDateString(): string {
  return new Date().toISOString().slice(0, 10);
}

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

  return { summary, isLoading, isRefreshing, error, refresh };
}
