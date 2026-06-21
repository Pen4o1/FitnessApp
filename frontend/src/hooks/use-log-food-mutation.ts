import { useMutation, useQueryClient } from '@tanstack/react-query';

import { logFood, type LogFoodPayload } from '@/lib/api';
import {
  applyOptimisticFoodLog,
  createOptimisticFoodLogItem,
  patchDailySummaryFromLogResponse,
} from '@/lib/daily-summary-optimistic';
import { queryKeys } from '@/lib/query-keys';
import type { DailySummary } from '@/types/nutrition';

type LogFoodMutationContext = {
  previousSummary: DailySummary | undefined;
  tempId: number;
};

let optimisticIdCounter = -1;

function nextOptimisticId(): number {
  optimisticIdCounter -= 1;

  return optimisticIdCounter;
}

export function useLogFoodMutation() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: (payload: LogFoodPayload) => logFood(payload),
    onMutate: async (payload) => {
      const queryKey = queryKeys.dailySummary(payload.date);
      await queryClient.cancelQueries({ queryKey });

      const previousSummary = queryClient.getQueryData<DailySummary>(queryKey);
      const tempId = nextOptimisticId();
      const optimisticItem = createOptimisticFoodLogItem(payload, tempId);

      if (previousSummary) {
        queryClient.setQueryData<DailySummary>(
          queryKey,
          applyOptimisticFoodLog(previousSummary, optimisticItem, payload.meal_type),
        );
      }

      return { previousSummary, tempId } satisfies LogFoodMutationContext;
    },
    onSuccess: (response, payload, context) => {
      if (!context) {
        return;
      }

      const queryKey = queryKeys.dailySummary(payload.date);
      const currentSummary = queryClient.getQueryData<DailySummary>(queryKey);

      if (currentSummary) {
        queryClient.setQueryData<DailySummary>(
          queryKey,
          patchDailySummaryFromLogResponse(
            currentSummary,
            context.tempId,
            response.item,
            payload.meal_type,
            response.consumed,
            response.remaining,
          ),
        );
      }
    },
    onError: (_error, payload, context) => {
      if (!context) {
        return;
      }

      queryClient.setQueryData(queryKeys.dailySummary(payload.date), context.previousSummary);
    },
    onSettled: (_data, _error, payload) => {
      void queryClient.invalidateQueries({ queryKey: queryKeys.dailySummary(payload.date) });
    },
  });
}
