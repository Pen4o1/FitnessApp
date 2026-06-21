export const queryKeys = {
  dailySummary: (date: string) => ['daily-summary', date] as const,
  weeklyAnalytics: () => ['weekly-analytics'] as const,
};
