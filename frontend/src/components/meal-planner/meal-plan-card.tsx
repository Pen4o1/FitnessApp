import { StyleSheet, View } from 'react-native';

import { RecipeDishCard } from '@/components/meal-planner/recipe-dish-card';
import { ThemedText } from '@/components/themed-text';
import { ThemedView } from '@/components/themed-view';
import { Spacing } from '@/constants/theme';
import { useTheme } from '@/hooks/use-theme';
import type { PlannedMeal } from '@/types/meal-plan';

type MealPlanCardProps = {
  meal: PlannedMeal;
};

export function MealPlanCard({ meal }: MealPlanCardProps) {
  const theme = useTheme();
  const mealLabel = `Meal ${meal.meal_number}`;
  const hasDishes = meal.dishes.length > 0;

  return (
    <ThemedView
      type="backgroundElement"
      style={[styles.card, { borderColor: theme.neonGreen + '33' }]}>
      <View style={styles.header}>
        <View style={styles.titleGroup}>
          <ThemedText themeColor="textSecondary" type="small" style={styles.mealLabel}>
            {mealLabel}
          </ThemedText>
          <ThemedText type="smallBold" style={styles.title}>
            {meal.title}
          </ThemedText>
        </View>
        <ThemedText type="smallBold" style={[styles.calories, { color: theme.neonGreen }]}>
          {meal.totals.calories} / {meal.target.calories} kcal
        </ThemedText>
      </View>

      <View style={styles.macrosRow}>
        <ThemedText type="small" style={{ color: theme.protein }}>
          P {meal.totals.protein_g}g
        </ThemedText>
        <ThemedText type="small" style={{ color: theme.carbs }}>
          C {meal.totals.carbs_g}g
        </ThemedText>
        <ThemedText type="small" style={{ color: theme.fat }}>
          F {meal.totals.fat_g}g
        </ThemedText>
      </View>

      {hasDishes ? (
        <View style={styles.itemsList}>
          {meal.dishes.map((dish, index) => (
            <View
              key={`${meal.meal_number}-${dish.kind}-${'recipe_id' in dish ? dish.recipe_id : dish.external_food_id}-${index}`}
              style={[styles.itemBlock, index > 0 && styles.itemBlockBorder]}>
              <RecipeDishCard dish={dish} />
            </View>
          ))}
        </View>
      ) : (
        <ThemedText themeColor="textSecondary" type="small" style={styles.emptyText}>
          No matching recipes found for this meal.
        </ThemedText>
      )}
    </ThemedView>
  );
}

const styles = StyleSheet.create({
  card: {
    borderRadius: Spacing.four,
    padding: Spacing.three,
    gap: Spacing.two,
    borderWidth: 1,
  },
  header: {
    flexDirection: 'row',
    alignItems: 'flex-start',
    justifyContent: 'space-between',
    gap: Spacing.two,
  },
  titleGroup: {
    flex: 1,
    gap: Spacing.half,
  },
  mealLabel: {
    fontSize: 12,
    textTransform: 'uppercase',
    letterSpacing: 0.5,
  },
  title: {
    fontSize: 17,
    lineHeight: 22,
  },
  calories: {
    fontSize: 15,
  },
  macrosRow: {
    flexDirection: 'row',
    gap: Spacing.three,
  },
  itemsList: {
    gap: Spacing.two,
  },
  itemBlock: {
    gap: Spacing.half,
    paddingTop: Spacing.one,
  },
  itemBlockBorder: {
    borderTopWidth: StyleSheet.hairlineWidth,
    borderTopColor: 'rgba(128, 128, 128, 0.35)',
    marginTop: Spacing.one,
    paddingTop: Spacing.two,
  },
  emptyText: {
    fontStyle: 'italic',
  },
});
