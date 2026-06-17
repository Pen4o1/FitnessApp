import { useCallback, useState } from 'react';

import { getMockDailySummary } from '@/data/mock-daily-summary';
import type { DailySummary } from '@/types/nutrition';

type UseDailySummaryResult = {
  summary: DailySummary;
  isRefreshing: boolean;
  refresh: () => Promise<void>;
};

export function useDailySummary(date?: string): UseDailySummaryResult {
  const [summary, setSummary] = useState<DailySummary>(() => getMockDailySummary(date));
  const [isRefreshing, setIsRefreshing] = useState(false);

  const refresh = useCallback(async () => {
    setIsRefreshing(true);
    try {
      await new Promise((resolve) => setTimeout(resolve, 400));
      setSummary(getMockDailySummary(date));
    } finally {
      setIsRefreshing(false);
    }
  }, [date]);

  return { summary, isRefreshing, refresh };
}
