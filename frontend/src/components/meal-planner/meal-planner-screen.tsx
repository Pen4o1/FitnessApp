import { SymbolView } from 'expo-symbols';
import { useRouter } from 'expo-router';
import { useState } from 'react';
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
import { ApiError, logFood } from '@/lib/api';
import { logMealPlanToDiary } from '@/lib/meal-plan';
import type { MealPlan } from '@/types/meal-plan';

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
  const [isLogging, setIsLogging] = useState(false);
  const [logError, setLogError] = useState<string | null>(null);

  async function handleGenerate() {
    await generate(mealsCount);
  }

  async function handleLogEntirePlan() {
    if (!plan || !hasLoggableItems(plan)) {
      return;
    }

    setIsLogging(true);
    setLogError(null);

    try {
      await logMealPlanToDiary(plan, date, logFood);

      Alert.alert('Success', 'Your meal plan was logged to your diary.', [
        {
          text: 'OK',
          onPress: () => router.replace('/(app)/(tabs)'),
        },
      ]);
    } catch (caught) {
      const message =
        caught instanceof ApiError
          ? caught.message
          : 'Could not log your meal plan. Please try again.';

      setLogError(message);
      Alert.alert('Could not log plan', message);
    } finally {
      setIsLogging(false);
    }
  }

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
                Building your plan...
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
                disabled={isLogging || !hasLoggableItems(plan)}
                onPress={handleLogEntirePlan}
                style={({ pressed }) => [
                  styles.logButton,
                  { borderColor: theme.neonGreen },
                  (isLogging || !hasLoggableItems(plan)) && styles.disabledButton,
                  pressed && !isLogging && hasLoggableItems(plan) && styles.pressed,
                ]}>
                {isLogging ? (
                  <ActivityIndicator color={theme.neonGreen} />
                ) : (
                  <ThemedText
                    type="smallBold"
                    style={[styles.logButtonText, { color: theme.neonGreen }]}>
                    Log Entire Plan to My Diary
                  </ThemedText>
                )}
              </Pressable>

              {logError ? (
                <ThemedText style={[styles.errorText, { color: theme.warning }]}>
                  {logError}
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
    gap: Spacing.four,
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
