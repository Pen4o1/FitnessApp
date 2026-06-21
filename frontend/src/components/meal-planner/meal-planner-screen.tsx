import { SymbolView } from 'expo-symbols';
import { useRouter } from 'expo-router';
import { useEffect, useState } from 'react';
import {
  ActivityIndicator,
  Alert,
  Pressable,
  ScrollView,
  StyleSheet,
  View,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';

import { MealCountSelector } from '@/components/meal-planner/meal-count-selector';
import { MealPlanCard } from '@/components/meal-planner/meal-plan-card';
import { ThemedText } from '@/components/themed-text';
import { ThemedView } from '@/components/themed-view';
import { BottomTabInset, MaxContentWidth, Spacing } from '@/constants/theme';
import { useMealPlan } from '@/hooks/use-meal-plan';
import { useTheme } from '@/hooks/use-theme';
import { ApiError, logMealPlanToDiary, saveMealPlan } from '@/lib/api';
import { queryClient } from '@/lib/query-client';
import { queryKeys } from '@/lib/query-keys';
import type { MealPlan } from '@/types/meal-plan';

const GENERATION_MESSAGES = [
  'Finding breakfast ideas...',
  'Building lunch options...',
  'Planning dinner...',
  'Adding snacks and sides...',
  'Finalizing your plan...',
];

type MealPlannerScreenProps = {
  date: string;
};

function formatHeaderDate(dateString: string): string {
  const date = new Date(`${dateString}T12:00:00`);
  return new Intl.DateTimeFormat('en-US', {
    weekday: 'long',
    day: 'numeric',
    month: 'long',
  }).format(date);
}

function hasLoggableItems(plan: MealPlan): boolean {
  return plan.meals.some((meal) => meal.dishes.length > 0);
}

export function MealPlannerScreen({ date }: MealPlannerScreenProps) {
  const router = useRouter();
  const theme = useTheme();
  const { plan, isGenerating, error, generate } = useMealPlan();
  const [mealsCount, setMealsCount] = useState(4);
  const [isSaving, setIsSaving] = useState(false);
  const [isLoggingToDiary, setIsLoggingToDiary] = useState(false);
  const [actionError, setActionError] = useState<string | null>(null);
  const [generationMessageIndex, setGenerationMessageIndex] = useState(0);

  useEffect(() => {
    if (!isGenerating) {
      return;
    }

    const interval = setInterval(() => {
      setGenerationMessageIndex((current) => (current + 1) % GENERATION_MESSAGES.length);
    }, 2200);

    return () => clearInterval(interval);
  }, [isGenerating]);

  async function handleGenerate() {
    setGenerationMessageIndex(0);
    await generate(mealsCount);
  }

  async function handleSaveToProfile() {
    if (!plan || !hasLoggableItems(plan)) {
      return;
    }

    setIsSaving(true);
    setActionError(null);

    try {
      await saveMealPlan(date, plan);

      Alert.alert('Saved', 'Your meal plan was saved to your profile.', [
        {
          text: 'View Profile',
          onPress: () => router.replace('/(app)/(tabs)/profile'),
        },
        { text: 'OK' },
      ]);
    } catch (caught) {
      const message =
        caught instanceof ApiError
          ? caught.message
          : 'Could not save your meal plan. Please try again.';

      setActionError(message);
      Alert.alert('Could not save plan', message);
    } finally {
      setIsSaving(false);
    }
  }

  async function handleLogToDiary() {
    if (!plan || !hasLoggableItems(plan)) {
      return;
    }

    setIsLoggingToDiary(true);
    setActionError(null);

    try {
      await logMealPlanToDiary(date, plan);
      await queryClient.invalidateQueries({ queryKey: queryKeys.dailySummary(date) });

      Alert.alert('Logged', 'Calories were added to your diary for this day.', [
        {
          text: 'OK',
          onPress: () => router.replace('/(app)/(tabs)'),
        },
      ]);
    } catch (caught) {
      const message =
        caught instanceof ApiError
          ? caught.message
          : 'Could not log your meal plan to the diary. Please try again.';

      setActionError(message);
      Alert.alert('Could not log to diary', message);
    } finally {
      setIsLoggingToDiary(false);
    }
  }

  const isActionPending = isSaving || isLoggingToDiary;
  const canActOnPlan = plan !== null && hasLoggableItems(plan);

  return (
    <ThemedView style={styles.container}>
      <SafeAreaView style={styles.safeArea} edges={['top']}>
        <ScrollView
          contentContainerStyle={styles.scrollContent}
          showsVerticalScrollIndicator={false}>
          <View style={styles.header}>
            <ThemedText type="subtitle" style={styles.title}>
              AI Meal Planner
            </ThemedText>
            <ThemedText themeColor="textSecondary" style={styles.dateLabel}>
              {formatHeaderDate(date)}
            </ThemedText>
          </View>

          <MealCountSelector
            disabled={isGenerating}
            value={mealsCount}
            onChange={setMealsCount}
          />

          <Pressable
            disabled={isGenerating}
            onPress={handleGenerate}
            style={({ pressed }) => [
              styles.generateButton,
              { backgroundColor: theme.neonGreen },
              isGenerating && styles.disabledButton,
              pressed && !isGenerating && styles.pressed,
            ]}>
            {isGenerating ? (
              <ActivityIndicator color="#FFFFFF" />
            ) : (
              <ThemedText type="smallBold" style={styles.generateButtonText}>
                Generate Daily AI Meal Plan
              </ThemedText>
            )}
          </Pressable>

          {error ? (
            <ThemedText style={[styles.errorText, { color: theme.warning }]}>
              {error}
            </ThemedText>
          ) : null}

          {isGenerating ? (
            <View style={styles.loadingState}>
              <ActivityIndicator color={theme.neonGreen} size="large" />
              <ThemedText themeColor="textSecondary" style={styles.loadingText}>
                {GENERATION_MESSAGES[generationMessageIndex]}
              </ThemedText>
            </View>
          ) : plan ? (
            <View style={styles.planContent}>
              <ThemedView type="backgroundElement" style={styles.summaryCard}>
                <View style={styles.summaryHeader}>
                  <ThemedText type="smallBold">Daily totals</ThemedText>
                  <View
                    style={[
                      styles.targetBadge,
                      {
                        backgroundColor: plan.within_target
                          ? theme.neonGreen + '22'
                          : theme.warning + '22',
                      },
                    ]}>
                    <ThemedText
                      type="small"
                      style={{
                        color: plan.within_target ? theme.neonGreen : theme.warning,
                      }}>
                      {plan.within_target ? 'On target' : 'Approximate'}
                    </ThemedText>
                  </View>
                </View>
                <ThemedText style={styles.summaryLine}>
                  {plan.totals.calories} / {plan.targets.calories} kcal
                </ThemedText>
                <ThemedText themeColor="textSecondary" type="small">
                  P {plan.totals.protein_g}g · C {plan.totals.carbs_g}g · F {plan.totals.fat_g}g
                </ThemedText>
              </ThemedView>

              <View style={styles.mealsList}>
                {plan.meals.map((meal) => (
                  <MealPlanCard key={meal.meal_number} meal={meal} />
                ))}
              </View>

              <Pressable
                disabled={isActionPending || !canActOnPlan}
                onPress={handleSaveToProfile}
                style={({ pressed }) => [
                  styles.saveButton,
                  { backgroundColor: theme.neonGreen },
                  (isActionPending || !canActOnPlan) && styles.disabledButton,
                  pressed && !isActionPending && canActOnPlan && styles.pressed,
                ]}>
                {isSaving ? (
                  <ActivityIndicator color="#FFFFFF" />
                ) : (
                  <ThemedText type="smallBold" style={styles.saveButtonText}>
                    Save to Profile
                  </ThemedText>
                )}
              </Pressable>

              <Pressable
                disabled={isActionPending || !canActOnPlan}
                onPress={handleLogToDiary}
                style={({ pressed }) => [
                  styles.logButton,
                  { borderColor: theme.neonGreen },
                  (isActionPending || !canActOnPlan) && styles.disabledButton,
                  pressed && !isActionPending && canActOnPlan && styles.pressed,
                ]}>
                {isLoggingToDiary ? (
                  <ActivityIndicator color={theme.neonGreen} />
                ) : (
                  <ThemedText
                    type="smallBold"
                    style={[styles.logButtonText, { color: theme.neonGreen }]}>
                    Add Calories to Diary
                  </ThemedText>
                )}
              </Pressable>

              <ThemedText themeColor="textSecondary" type="small" style={styles.actionHint}>
                Save keeps the plan in your profile only. Add to diary counts these meals toward
                your daily calories.
              </ThemedText>

              {actionError ? (
                <ThemedText style={[styles.errorText, { color: theme.warning }]}>
                  {actionError}
                </ThemedText>
              ) : null}
            </View>
          ) : (
            <ThemedView type="backgroundElement" style={styles.emptyState}>
              <SymbolView
                name={{ ios: 'fork.knife', android: 'restaurant', web: 'restaurant' }}
                size={40}
                tintColor={theme.textSecondary}
              />
              <ThemedText themeColor="textSecondary" style={styles.emptyText}>
                No plan yet — tap Generate to build your day
              </ThemedText>
            </ThemedView>
          )}
        </ScrollView>
      </SafeAreaView>
    </ThemedView>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
  },
  safeArea: {
    flex: 1,
  },
  scrollContent: {
    paddingHorizontal: Spacing.four,
    paddingTop: Spacing.three,
    paddingBottom: BottomTabInset + Spacing.five,
    gap: Spacing.four,
    maxWidth: MaxContentWidth,
    width: '100%',
    alignSelf: 'center',
  },
  header: {
    gap: Spacing.one,
  },
  title: {
    fontSize: 28,
    lineHeight: 36,
  },
  dateLabel: {
    fontSize: 15,
    textTransform: 'capitalize',
  },
  generateButton: {
    borderRadius: Spacing.three,
    paddingVertical: Spacing.three,
    alignItems: 'center',
    justifyContent: 'center',
    minHeight: 52,
  },
  generateButtonText: {
    color: '#FFFFFF',
    fontSize: 16,
  },
  disabledButton: {
    opacity: 0.55,
  },
  pressed: {
    opacity: 0.85,
  },
  errorText: {
    textAlign: 'center',
    lineHeight: 20,
  },
  loadingState: {
    alignItems: 'center',
    justifyContent: 'center',
    gap: Spacing.two,
    paddingVertical: Spacing.five,
  },
  loadingText: {
    fontSize: 15,
  },
  planContent: {
    gap: Spacing.three,
  },
  summaryCard: {
    borderRadius: Spacing.four,
    padding: Spacing.three,
    gap: Spacing.one,
  },
  summaryHeader: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    gap: Spacing.two,
  },
  targetBadge: {
    paddingHorizontal: Spacing.two,
    paddingVertical: Spacing.half,
    borderRadius: Spacing.two,
  },
  summaryLine: {
    fontSize: 18,
    fontWeight: '600',
  },
  mealsList: {
    gap: Spacing.three,
  },
  saveButton: {
    borderRadius: Spacing.three,
    paddingVertical: Spacing.three,
    alignItems: 'center',
    justifyContent: 'center',
    minHeight: 52,
  },
  saveButtonText: {
    color: '#FFFFFF',
    fontSize: 16,
  },
  logButton: {
    borderRadius: Spacing.three,
    paddingVertical: Spacing.three,
    alignItems: 'center',
    justifyContent: 'center',
    minHeight: 52,
    borderWidth: 2,
  },
  logButtonText: {
    fontSize: 16,
  },
  actionHint: {
    textAlign: 'center',
    lineHeight: 20,
    fontSize: 13,
  },
  emptyState: {
    borderRadius: Spacing.four,
    padding: Spacing.five,
    alignItems: 'center',
    gap: Spacing.three,
  },
  emptyText: {
    textAlign: 'center',
    lineHeight: 22,
    fontSize: 15,
  },
});
