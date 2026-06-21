import { useFocusEffect } from 'expo-router';
import { SymbolView } from 'expo-symbols';
import { useCallback, useState } from 'react';
import { Modal, Pressable, ScrollView, StyleSheet, View } from 'react-native';
import Animated, { FadeIn, LinearTransition } from 'react-native-reanimated';
import { useSafeAreaInsets } from 'react-native-safe-area-context';

import { MealPlanCard } from '@/components/meal-planner/meal-plan-card';
import { ThemedText } from '@/components/themed-text';
import { ThemedView } from '@/components/themed-view';
import { Spacing } from '@/constants/theme';
import { useTheme } from '@/hooks/use-theme';
import { ApiError, getSavedMealPlan, getSavedMealPlans } from '@/lib/api';
import { normalizeMealPlan } from '@/lib/meal-plan';
import { SavedMealPlansSkeleton } from '@/components/ui/saved-meal-plans-skeleton';
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
  const [isExpanded, setIsExpanded] = useState(false);
  const [detail, setDetail] = useState<SavedMealPlan | null>(null);
  const [isLoading, setIsLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);

  async function toggleExpanded() {
    if (isExpanded) {
      setIsExpanded(false);
      return;
    }

    setIsExpanded(true);

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

  return (
    <Animated.View 
      layout={LinearTransition.duration(200)}
      style={[styles.planCard, { backgroundColor: theme.backgroundElement }]}>
      <Pressable
        onPress={toggleExpanded}
        style={({ pressed }) => [styles.planHeader, pressed && styles.pressed]}>
        <View style={styles.planHeaderContent}>
          <View style={styles.planTitleRow}>
            <SymbolView 
              name={{ ios: 'calendar', android: 'calendar_today', web: 'calendar_today' }} 
              size={18} 
              tintColor={theme.text} 
            />
            <ThemedText type="default" style={styles.planTitle}>
              {formatPlanDate(summary.plan_date)}
            </ThemedText>
          </View>
          <ThemedText themeColor="textSecondary" type="small">
            {summary.meals_count} meals
          </ThemedText>
        </View>

        <View style={[styles.iconButton, { backgroundColor: theme.backgroundSelected }]}>
          <SymbolView
            name={{ ios: 'chevron.right', android: 'chevron_right', web: 'chevron_right' }}
            size={16}
            weight="bold"
            tintColor={theme.textSecondary}
            style={{ transform: [{ rotate: isExpanded ? '-90deg' : '90deg' }] }}
          />
        </View>
      </Pressable>

      <View style={styles.planMeta}>
        <View style={styles.macrosRow}>
          <ThemedText type="smallBold" style={{ color: theme.neonGreen }}>
            {summary.totals.calories} kcal
          </ThemedText>
          <ThemedText type="small" style={{ color: theme.protein }}>
            P {summary.totals.protein_g}g
          </ThemedText>
          <ThemedText type="small" style={{ color: theme.carbs }}>
            C {summary.totals.carbs_g}g
          </ThemedText>
          <ThemedText type="small" style={{ color: theme.fat }}>
            F {summary.totals.fat_g}g
          </ThemedText>
        </View>
        <ThemedText themeColor="textSecondary" type="small" style={styles.savedAt}>
          Saved {formatSavedAt(summary.created_at)}
        </ThemedText>
      </View>

      {isExpanded && (
        <Animated.View entering={FadeIn.duration(200)}>
          <View style={[styles.divider, { backgroundColor: theme.backgroundSelected }]} />
          
          {isLoading ? (
            <SavedMealPlansSkeleton cards={1} />
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
          ) : null}
        </Animated.View>
      )}
    </Animated.View>
  );
}

export function SavedMealPlansSection() {
  const theme = useTheme();
  const insets = useSafeAreaInsets();
  const [plans, setPlans] = useState<SavedMealPlanSummary[]>([]);
  const [isLoading, setIsLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [isSheetVisible, setIsSheetVisible] = useState(false);

  const loadPlans = useCallback(async () => {
    setIsLoading(true);
    setError(null);

    try {
      const items = await getSavedMealPlans(50);
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
      <View style={styles.sectionHeader}>
        <ThemedText type="smallBold" style={styles.sectionTitle}>
          Saved meal plans
        </ThemedText>
        <ThemedText themeColor="textSecondary" type="small" style={styles.sectionHint}>
          Plans you save from the AI meal planner appear here.
        </ThemedText>
      </View>

      {isLoading ? (
        <SavedMealPlansSkeleton cards={2} />
      ) : error ? (
        <ThemedText type="small" style={styles.errorText}>
          {error}
        </ThemedText>
      ) : plans.length === 0 ? (
        <ThemedText themeColor="textSecondary" type="small" style={styles.emptyText}>
          No saved meal plans yet. Generate a plan and tap “Save to Profile”.
        </ThemedText>
      ) : (
        <>
          <Pressable
            onPress={() => setIsSheetVisible(true)}
            style={({ pressed }) => [
              styles.viewAllButton,
              { backgroundColor: theme.backgroundSelected },
              pressed && styles.pressed,
            ]}>
            <SymbolView 
              name={{ ios: 'list.bullet', android: 'list', web: 'list' }} 
              size={18} 
              tintColor={theme.text} 
            />
            <ThemedText type="smallBold">
              View {plans.length} saved {plans.length === 1 ? 'plan' : 'plans'}
            </ThemedText>
          </Pressable>

          <Modal animationType="slide" transparent visible={isSheetVisible} onRequestClose={() => setIsSheetVisible(false)}>
            <View style={styles.sheetBackdrop}>
              <View style={[styles.sheetContent, { backgroundColor: theme.background, paddingBottom: insets.bottom }]}>
                <View style={styles.sheetHeader}>
                  <View style={styles.sheetHeaderTitles}>
                    <ThemedText type="subtitle" style={styles.sheetTitle}>
                      Saved Plans
                    </ThemedText>
                    <ThemedText themeColor="textSecondary" type="small">
                      Your meal plan history
                    </ThemedText>
                  </View>
                  <Pressable onPress={() => setIsSheetVisible(false)} style={styles.closeButton}>
                    <SymbolView name={{ ios: 'xmark', android: 'close', web: 'close' }} size={24} tintColor={theme.text} />
                  </Pressable>
                </View>
                <ScrollView contentContainerStyle={styles.sheetScroll} showsVerticalScrollIndicator={false}>
                  <View style={styles.plansList}>
                    {plans.map((plan) => (
                      <SavedMealPlanItem key={plan.id} summary={plan} />
                    ))}
                  </View>
                </ScrollView>
              </View>
            </View>
          </Modal>
        </>
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
  sectionHeader: {
    gap: Spacing.one,
  },
  viewAllButton: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    gap: Spacing.two,
    paddingVertical: Spacing.three,
    borderRadius: Spacing.three,
    marginTop: Spacing.one,
  },
  plansList: {
    gap: Spacing.three,
    marginTop: Spacing.one,
  },
  planCard: {
    borderRadius: Spacing.three,
    padding: Spacing.three,
    borderWidth: 1,
    borderColor: 'rgba(128, 128, 128, 0.15)',
  },
  planHeader: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    marginBottom: Spacing.two,
  },
  planHeaderContent: {
    gap: 2,
  },
  planTitleRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: Spacing.one,
  },
  planTitle: {
    fontSize: 16,
    fontWeight: '600',
  },
  iconButton: {
    width: 32,
    height: 32,
    borderRadius: 16,
    justifyContent: 'center',
    alignItems: 'center',
  },
  planMeta: {
    gap: Spacing.one,
  },
  macrosRow: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: Spacing.three,
  },
  savedAt: {
    fontSize: 12,
    marginTop: 2,
  },
  divider: {
    height: 1,
    marginVertical: Spacing.three,
  },
  mealsList: {
    gap: Spacing.three,
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
    opacity: 0.7,
  },
  sheetBackdrop: {
    flex: 1,
    backgroundColor: 'rgba(0, 0, 0, 0.5)',
    justifyContent: 'flex-end',
  },
  sheetContent: {
    borderTopLeftRadius: Spacing.four,
    borderTopRightRadius: Spacing.four,
    height: '85%',
    paddingTop: Spacing.four,
  },
  sheetHeader: {
    flexDirection: 'row',
    alignItems: 'flex-start',
    justifyContent: 'space-between',
    paddingHorizontal: Spacing.four,
    marginBottom: Spacing.three,
  },
  sheetHeaderTitles: {
    gap: Spacing.half,
  },
  sheetTitle: {
    fontSize: 22,
    lineHeight: 28,
  },
  closeButton: {
    padding: Spacing.one,
    marginRight: -Spacing.one,
    marginTop: -Spacing.one,
  },
  sheetScroll: {
    paddingHorizontal: Spacing.four,
    paddingBottom: Spacing.five,
  },
});
