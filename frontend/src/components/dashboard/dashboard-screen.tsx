import { SymbolView } from 'expo-symbols';
import { useFocusEffect, useRouter } from 'expo-router';
import { useCallback, useState } from 'react';
import { ActivityIndicator, Pressable, RefreshControl, ScrollView, StyleSheet, View } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';

import { CalorieRing } from '@/components/dashboard/calorie-ring';
import { MacroProgressBar } from '@/components/dashboard/macro-progress-bar';
import { MealSection } from '@/components/dashboard/meal-section';
import { ThemedText } from '@/components/themed-text';
import { ThemedView } from '@/components/themed-view';
import { BottomTabInset, MaxContentWidth, Spacing } from '@/constants/theme';
import { useAuth } from '@/contexts/auth-context';
import { useDailySummary } from '@/hooks/use-daily-summary';
import { useTheme } from '@/hooks/use-theme';
import { addDays, isToday, todayDateString } from '@/lib/date';
import type { MealType } from '@/types/nutrition';

function formatHeaderDate(dateString: string): string {
  const date = new Date(`${dateString}T12:00:00`);
  return new Intl.DateTimeFormat('en-US', {
    weekday: 'long',
    day: 'numeric',
    month: 'long',
  }).format(date);
}

export function DashboardScreen() {
  const router = useRouter();
  const theme = useTheme();
  const { user } = useAuth();
  const [selectedDate, setSelectedDate] = useState(todayDateString);
  const { summary, isLoading, isRefreshing, error, refresh } = useDailySummary(selectedDate);
  const viewingToday = isToday(selectedDate);

  useFocusEffect(
    useCallback(() => {
      void refresh();
    }, [refresh]),
  );

  function handlePreviousDay() {
    setSelectedDate((currentDate) => addDays(currentDate, -1));
  }

  function handleNextDay() {
    if (viewingToday) {
      return;
    }

    setSelectedDate((currentDate) => addDays(currentDate, 1));
  }

  function handleGoToToday() {
    setSelectedDate(todayDateString());
  }

  function handleAddFood(mealType: MealType) {
    if (!summary) {
      return;
    }

    router.push({
      pathname: '/(app)/food-search',
      params: { mealType, date: summary.date },
    });
  }

  if (isLoading && !summary) {
    return (
      <ThemedView style={styles.container}>
        <SafeAreaView style={styles.loadingState} edges={['top']}>
          <ActivityIndicator color={theme.accent} size="large" />
        </SafeAreaView>
      </ThemedView>
    );
  }

  if (!summary) {
    return (
      <ThemedView style={styles.container}>
        <SafeAreaView style={styles.loadingState} edges={['top']}>
          <ThemedText themeColor="textSecondary" style={styles.errorText}>
            {error ?? 'Could not load your daily summary.'}
          </ThemedText>
        </SafeAreaView>
      </ThemedView>
    );
  }

  return (
    <ThemedView style={styles.container}>
      <SafeAreaView style={styles.safeArea} edges={['top']}>
        <ScrollView
          contentContainerStyle={styles.scrollContent}
          showsVerticalScrollIndicator={false}
          refreshControl={
            <RefreshControl refreshing={isRefreshing} onRefresh={refresh} tintColor={theme.accent} />
          }>
          <View style={styles.header}>
            <View style={styles.headerTopRow}>
              <View style={styles.headerText}>
                <ThemedText type="subtitle" style={styles.greeting}>
                  Hello{user ? `, ${user.first_name}` : ''}
                </ThemedText>
                <View style={styles.dateNavigator}>
                  <Pressable
                    accessibilityRole="button"
                    accessibilityLabel="Previous day"
                    onPress={handlePreviousDay}
                    style={({ pressed }) => [styles.dateNavButton, pressed && styles.dateNavButtonPressed]}>
                    <SymbolView
                      name={{ ios: 'chevron.left', android: 'chevron_left', web: 'chevron_left' }}
                      size={16}
                      weight="semibold"
                      tintColor={theme.text}
                    />
                  </Pressable>
                  <ThemedText themeColor="textSecondary" style={styles.date}>
                    {formatHeaderDate(selectedDate)}
                  </ThemedText>
                  <Pressable
                    accessibilityRole="button"
                    accessibilityLabel="Next day"
                    accessibilityState={{ disabled: viewingToday }}
                    disabled={viewingToday}
                    onPress={handleNextDay}
                    style={({ pressed }) => [
                      styles.dateNavButton,
                      viewingToday && styles.dateNavButtonDisabled,
                      pressed && !viewingToday && styles.dateNavButtonPressed,
                    ]}>
                    <SymbolView
                      name={{ ios: 'chevron.right', android: 'chevron_right', web: 'chevron_right' }}
                      size={16}
                      weight="semibold"
                      tintColor={viewingToday ? theme.textSecondary : theme.text}
                    />
                  </Pressable>
                </View>
                {!viewingToday ? (
                  <Pressable
                    accessibilityRole="button"
                    accessibilityLabel="Go to today"
                    onPress={handleGoToToday}
                    style={({ pressed }) => [styles.todayLink, pressed && styles.todayLinkPressed]}>
                    <ThemedText style={[styles.todayLinkText, { color: theme.accent }]}>Today</ThemedText>
                  </Pressable>
                ) : null}
              </View>
              <View style={styles.headerActions}>
                <Pressable
                  accessibilityRole="button"
                  accessibilityLabel="Open AI meal planner"
                  onPress={() =>
                    router.push({
                      pathname: '/(app)/meal-planner',
                      params: { date: selectedDate },
                    })
                  }
                  style={({ pressed }) => [styles.headerLink, pressed && styles.headerLinkPressed]}>
                  <ThemedText style={[styles.mealPlannerLinkText, { color: theme.neonGreen }]}>
                    Meal Plan
                  </ThemedText>
                </Pressable>
                <Pressable
                  accessibilityRole="button"
                  accessibilityLabel="View weekly analytics"
                  onPress={() => router.push('/(app)/analytics')}
                  style={({ pressed }) => [styles.headerLink, pressed && styles.headerLinkPressed]}>
                  <ThemedText style={[styles.analyticsLinkText, { color: theme.success }]}>
                    Analytics
                  </ThemedText>
                </Pressable>
              </View>
            </View>
          </View>

          <ThemedView type="backgroundElement" style={styles.summaryCard}>
            <CalorieRing
              consumed={summary.consumed.calories}
              target={summary.targets.calories}
              remaining={summary.remaining.calories}
            />

            <View style={styles.macrosSection}>
              <MacroProgressBar
                label="Protein"
                consumed={summary.consumed.protein_g}
                target={summary.targets.protein_g}
                color={theme.protein}
              />
              <MacroProgressBar
                label="Carbs"
                consumed={summary.consumed.carbs_g}
                target={summary.targets.carbs_g}
                color={theme.carbs}
              />
              <MacroProgressBar
                label="Fat"
                consumed={summary.consumed.fat_g}
                target={summary.targets.fat_g}
                color={theme.fat}
              />
            </View>
          </ThemedView>

          <View style={styles.mealsSection}>
            <ThemedText type="smallBold" style={styles.mealsTitle}>
              {viewingToday ? "Today's meals" : 'Meals'}
            </ThemedText>
            {summary.meals.map((meal) => (
              <MealSection
                key={meal.meal_type}
                meal={meal}
                onAddPress={() => handleAddFood(meal.meal_type)}
              />
            ))}
          </View>
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
  headerTopRow: {
    flexDirection: 'row',
    alignItems: 'flex-start',
    justifyContent: 'space-between',
    gap: Spacing.two,
  },
  headerText: {
    flex: 1,
    gap: Spacing.one,
  },
  headerActions: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: Spacing.one,
  },
  headerLink: {
    paddingVertical: Spacing.one,
    paddingHorizontal: Spacing.two,
    borderRadius: Spacing.two,
  },
  headerLinkPressed: {
    opacity: 0.7,
  },
  mealPlannerLinkText: {
    fontSize: 14,
    fontWeight: '600',
  },
  analyticsLinkText: {
    fontSize: 14,
    fontWeight: '600',
  },
  greeting: {
    fontSize: 28,
    lineHeight: 36,
  },
  dateNavigator: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: Spacing.two,
  },
  dateNavButton: {
    width: 32,
    height: 32,
    borderRadius: Spacing.two,
    alignItems: 'center',
    justifyContent: 'center',
  },
  dateNavButtonPressed: {
    opacity: 0.7,
  },
  dateNavButtonDisabled: {
    opacity: 0.35,
  },
  date: {
    flex: 1,
    fontSize: 15,
    textTransform: 'capitalize',
  },
  todayLink: {
    alignSelf: 'flex-start',
    paddingVertical: Spacing.half,
  },
  todayLinkPressed: {
    opacity: 0.7,
  },
  todayLinkText: {
    fontSize: 14,
    fontWeight: '600',
  },
  summaryCard: {
    borderRadius: Spacing.four,
    padding: Spacing.four,
    gap: Spacing.four,
  },
  macrosSection: {
    gap: Spacing.three,
  },
  mealsSection: {
    gap: Spacing.three,
  },
  mealsTitle: {
    fontSize: 16,
  },
  loadingState: {
    flex: 1,
    alignItems: 'center',
    justifyContent: 'center',
    paddingHorizontal: Spacing.four,
  },
  errorText: {
    textAlign: 'center',
    lineHeight: 22,
  },
});
