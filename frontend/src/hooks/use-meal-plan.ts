import { useCallback, useState } from 'react';

import { ApiError, generateMealPlan } from '@/lib/api';
import { normalizeMealPlan } from '@/lib/meal-plan';
import type { MealPlan } from '@/types/meal-plan';

function formatGenerateError(error: unknown): string {
  if (error instanceof ApiError) {
    if (error.status === 422) {
      return error.message || 'Complete your profile to set nutrition targets.';
    }

    if (error.status === 502) {
      return 'Food search is temporarily unavailable. Please try again later.';
    }

    return error.message;
  }

  return 'Could not generate your meal plan. Please try again.';
}

export function useMealPlan() {
  const [plan, setPlan] = useState<MealPlan | null>(null);
  const [isGenerating, setIsGenerating] = useState(false);
  const [error, setError] = useState<string | null>(null);

  const generate = useCallback(async (mealsCount = 4) => {
    setIsGenerating(true);
    setError(null);

    try {
      const result = await generateMealPlan({ mealsCount });
      setPlan(normalizeMealPlan(result));
    } catch (caught) {
      setPlan(null);
      setError(formatGenerateError(caught));
    } finally {
      setIsGenerating(false);
    }
  }, []);

  const clearError = useCallback(() => {
    setError(null);
  }, []);

  return {
    plan,
    isGenerating,
    error,
    generate,
    clearError,
  };
}
