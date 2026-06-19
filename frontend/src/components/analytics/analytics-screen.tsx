import { useFocusEffect } from 'expo-router';
import { useCallback, useMemo } from 'react';
import { ActivityIndicator, RefreshControl, ScrollView, StyleSheet, View } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';

import { ChartCard } from '@/components/analytics/chart-card';
import { hasCalorieData, hasWeightData } from '@/components/analytics/chart-utils';
import { WeeklyCalorieChart } from '@/components/analytics/weekly-calorie-chart';
import { WeeklyWeightChart } from '@/components/analytics/weekly-weight-chart';
import { ThemedText } from '@/components/themed-text';
import { ThemedView } from '@/components/themed-view';
import { BottomTabInset, MaxContentWidth, Spacing } from '@/constants/theme';
import { useAuth } from '@/contexts/auth-context';
import { useWeeklyAnalytics } from '@/hooks/use-weekly-analytics';
import { useTheme } from '@/hooks/use-theme';

const DEFAULT_CALORIE_GOAL = 2000;

export function AnalyticsScreen() {
  const theme = useTheme();
  const { user } = useAuth();
  const { days, isLoading, isRefreshing, error, refresh } = useWeeklyAnalytics();

  const calorieGoal = user?.nutrition_target?.calorie_target ?? DEFAULT_CALORIE_GOAL;

  const hasCalories = useMemo(() => (days ? hasCalorieData(days) : false), [days]);
  const hasWeight = useMemo(() => (days ? hasWeightData(days) : false), [days]);

  useFocusEffect(
    useCallback(() => {
      void refresh();
    }, [refresh]),
  );

  if (isLoading && !days) {
    return (
      <ThemedView style={styles.container}>
        <SafeAreaView style={styles.centeredState} edges={['top']}>
          <ActivityIndicator color={theme.success} size="large" />
          <ThemedText themeColor="textSecondary" style={styles.loadingText}>
            Loading your progress...
          </ThemedText>
        </SafeAreaView>
      </ThemedView>
    );
  }

  if (!days) {
    return (
      <ThemedView style={styles.container}>
        <SafeAreaView style={styles.centeredState} edges={['top']}>
          <ThemedText themeColor="textSecondary" style={styles.errorText}>
            {error ?? 'Could not load your weekly analytics.'}
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
            <RefreshControl refreshing={isRefreshing} onRefresh={refresh} tintColor={theme.success} />
          }>
          <View style={styles.header}>
            <ThemedText type="subtitle" style={styles.title}>
              Analytics
            </ThemedText>
            <ThemedText themeColor="textSecondary" style={styles.subtitle}>
              Your last 7 days at a glance
            </ThemedText>
          </View>

          <ChartCard
            title="Daily calorie intake"
            subtitle="Track how close you are to your daily goal"
            isEmpty={!hasCalories}
            emptyMessage="No calorie data this week. Start logging meals to see your progress.">
            <WeeklyCalorieChart days={days} calorieGoal={calorieGoal} />
          </ChartCard>

          <ChartCard
            title="Weight progress"
            subtitle="Monitor body weight changes over time"
            isEmpty={!hasWeight}
            emptyMessage="No weight entries this week. Update your profile weight to track changes.">
            {hasWeight ? <WeeklyWeightChart days={days} /> : null}
          </ChartCard>
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
  subtitle: {
    fontSize: 15,
    lineHeight: 22,
  },
  centeredState: {
    flex: 1,
    alignItems: 'center',
    justifyContent: 'center',
    paddingHorizontal: Spacing.four,
    gap: Spacing.two,
  },
  loadingText: {
    fontSize: 14,
    textAlign: 'center',
  },
  errorText: {
    fontSize: 15,
    lineHeight: 22,
    textAlign: 'center',
  },
});
