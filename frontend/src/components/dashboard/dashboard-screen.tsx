import { useRouter } from 'expo-router';
import { useCallback } from 'react';
import { ActivityIndicator, RefreshControl, ScrollView, StyleSheet, View } from 'react-native';
import { useFocusEffect } from '@react-navigation/native';
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
  const { summary, isLoading, isRefreshing, error, refresh } = useDailySummary();

  useFocusEffect(
    useCallback(() => {
      void refresh();
    }, [refresh]),
  );

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
            <ThemedText type="subtitle" style={styles.greeting}>
              Hello{user ? `, ${user.first_name}` : ''}
            </ThemedText>
            <ThemedText themeColor="textSecondary" style={styles.date}>
              {formatHeaderDate(summary.date)}
            </ThemedText>
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
              Today's meals
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
  greeting: {
    fontSize: 28,
    lineHeight: 36,
  },
  date: {
    fontSize: 15,
    textTransform: 'capitalize',
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
