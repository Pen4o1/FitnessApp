import { useFocusEffect } from 'expo-router';
import { useCallback, useState } from 'react';
import { ActivityIndicator, Pressable, StyleSheet, View } from 'react-native';

import { MealPlanCard } from '@/components/meal-planner/meal-plan-card';
import { Collapsible } from '@/components/ui/collapsible';
import { ThemedText } from '@/components/themed-text';
import { ThemedView } from '@/components/themed-view';
import { Spacing } from '@/constants/theme';
import { useTheme } from '@/hooks/use-theme';
import { ApiError, getSavedMealPlan, getSavedMealPlans } from '@/lib/api';
import { normalizeMealPlan } from '@/lib/meal-plan';
import type { SavedMealPlan, SavedMealPlanSummary } from '@/types/meal-plan';

function formatPlanDate(dateString: string): string {
  const date = new Date(`${dateString}T12:00:00`);

  return new Intl.DateTimeFormat('en-US', {
    weekday: 'short',
    day: 'numeric',
    month: 'short',
    year: 'numeric',
  }).format(date);
}

function formatSavedAt(dateString: string): string {
  const date = new Date(dateString);

  return new Intl.DateTimeFormat('en-US', {
    day: 'numeric',
    month: 'short',
    hour: 'numeric',
    minute: '2-digit',
  }).format(date);
}

type SavedMealPlanItemProps = {
  summary: SavedMealPlanSummary;
};

function SavedMealPlanItem({ summary }: SavedMealPlanItemProps) {
  const theme = useTheme();
  const [detail, setDetail] = useState<SavedMealPlan | null>(null);
  const [isLoading, setIsLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);

  async function loadDetail() {
    if (detail !== null || isLoading) {
      return;
    }

    setIsLoading(true);
    setError(null);

    try {
      const plan = await getSavedMealPlan(summary.id);
      setDetail(normalizeMealPlan(plan) as SavedMealPlan);
    } catch (caught) {
      const message =
        caught instanceof ApiError ? caught.message : 'Could not load this meal plan.';

      setError(message);
    } finally {
      setIsLoading(false);
    }
  }

  const title = `${formatPlanDate(summary.plan_date)} · ${summary.meals_count} meals`;

  return (
    <ThemedView type="backgroundElement" style={styles.planCard}>
      <Collapsible title={title}>
        <View style={styles.planMeta}>
          <ThemedText type="small">
            {summary.totals.calories} kcal · P {summary.totals.protein_g}g · C{' '}
            {summary.totals.carbs_g}g · F {summary.totals.fat_g}g
          </ThemedText>
          <ThemedText themeColor="textSecondary" type="small">
            Saved {formatSavedAt(summary.created_at)}
          </ThemedText>
        </View>

        {isLoading ? (
          <ActivityIndicator color={theme.neonGreen} style={styles.loader} />
        ) : null}

        {error ? (
          <ThemedText type="small" style={styles.errorText}>
            {error}
          </ThemedText>
        ) : null}

        {detail ? (
          <View style={styles.mealsList}>
            {detail.meals.map((meal) => (
              <MealPlanCard key={`${summary.id}-${meal.meal_number}`} meal={meal} />
            ))}
          </View>
        ) : (
          <Pressable
            disabled={isLoading}
            onPress={loadDetail}
            style={({ pressed }) => [styles.loadButton, pressed && styles.pressed]}>
            <ThemedText type="smallBold" style={{ color: theme.neonGreen }}>
              View full plan
            </ThemedText>
          </Pressable>
        )}
      </Collapsible>
    </ThemedView>
  );
}

export function SavedMealPlansSection() {
  const theme = useTheme();
  const [plans, setPlans] = useState<SavedMealPlanSummary[]>([]);
  const [isLoading, setIsLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  const loadPlans = useCallback(async () => {
    setIsLoading(true);
    setError(null);

    try {
      const items = await getSavedMealPlans(10);
      setPlans(items);
    } catch {
      setError('Could not load your saved meal plans.');
    } finally {
      setIsLoading(false);
    }
  }, []);

  useFocusEffect(
    useCallback(() => {
      void loadPlans();
    }, [loadPlans]),
  );

  return (
    <ThemedView type="backgroundElement" style={styles.section}>
      <ThemedText type="smallBold" style={styles.sectionTitle}>
        Saved meal plans
      </ThemedText>
      <ThemedText themeColor="textSecondary" type="small" style={styles.sectionHint}>
        Plans you save from the AI meal planner appear here.
      </ThemedText>

      {isLoading ? (
        <ActivityIndicator color={theme.accent} style={styles.sectionLoader} />
      ) : error ? (
        <ThemedText type="small" style={styles.errorText}>
          {error}
        </ThemedText>
      ) : plans.length === 0 ? (
        <ThemedText themeColor="textSecondary" type="small" style={styles.emptyText}>
          No saved meal plans yet. Generate a plan and tap “Save to Profile”.
        </ThemedText>
      ) : (
        <View style={styles.plansList}>
          {plans.map((plan) => (
            <SavedMealPlanItem key={plan.id} summary={plan} />
          ))}
        </View>
      )}
    </ThemedView>
  );
}

const styles = StyleSheet.create({
  section: {
    borderRadius: Spacing.four,
    padding: Spacing.four,
    gap: Spacing.two,
  },
  sectionTitle: {
    fontSize: 16,
  },
  sectionHint: {
    lineHeight: 20,
  },
  sectionLoader: {
    marginTop: Spacing.two,
  },
  plansList: {
    gap: Spacing.two,
    marginTop: Spacing.one,
  },
  planCard: {
    borderRadius: Spacing.three,
    padding: Spacing.two,
  },
  planMeta: {
    gap: Spacing.half,
    marginBottom: Spacing.two,
  },
  mealsList: {
    gap: Spacing.three,
    marginTop: Spacing.two,
  },
  loadButton: {
    paddingVertical: Spacing.one,
  },
  loader: {
    marginVertical: Spacing.two,
  },
  emptyText: {
    lineHeight: 20,
    fontStyle: 'italic',
  },
  errorText: {
    color: '#d64545',
    lineHeight: 20,
  },
  pressed: {
    opacity: 0.85,
  },
});
