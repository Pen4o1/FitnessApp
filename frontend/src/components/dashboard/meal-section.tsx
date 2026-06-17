import { Pressable, StyleSheet, View } from 'react-native';

import { ThemedText } from '@/components/themed-text';
import { ThemedView } from '@/components/themed-view';
import { Spacing } from '@/constants/theme';
import { useTheme } from '@/hooks/use-theme';
import { MEAL_TYPE_LABELS, type MealEntry } from '@/types/nutrition';

type MealSectionProps = {
  meal: MealEntry;
  onAddPress: () => void;
};

export function MealSection({ meal, onAddPress }: MealSectionProps) {
  const theme = useTheme();
  const label = MEAL_TYPE_LABELS[meal.meal_type];
  const hasItems = meal.items.length > 0;

  return (
    <ThemedView type="backgroundElement" style={styles.container}>
      <View style={styles.header}>
        <View style={styles.titleGroup}>
          <ThemedText type="smallBold">{label}</ThemedText>
          {hasItems ? (
            <ThemedText themeColor="textSecondary" type="small">
              {meal.totals.calories} kcal
            </ThemedText>
          ) : null}
        </View>

        <Pressable
          onPress={onAddPress}
          accessibilityLabel={`Add food to ${label}`}
          style={({ pressed }) => [
            styles.addButton,
            { backgroundColor: theme.accent },
            pressed && styles.pressed,
          ]}>
          <ThemedText style={styles.addButtonText}>+</ThemedText>
        </Pressable>
      </View>

      {hasItems ? (
        <View style={styles.itemsList}>
          {meal.items.map((item) => (
            <View key={item.id} style={styles.itemRow}>
              <ThemedText style={styles.itemName} numberOfLines={1}>
                {item.food_name}
                {item.brand_name ? ` · ${item.brand_name}` : ''}
              </ThemedText>
              <ThemedText themeColor="textSecondary" type="small">
                {item.calories} kcal
              </ThemedText>
            </View>
          ))}
        </View>
      ) : (
        <ThemedText themeColor="textSecondary" type="small" style={styles.emptyText}>
          No food logged yet
        </ThemedText>
      )}
    </ThemedView>
  );
}

const styles = StyleSheet.create({
  container: {
    borderRadius: Spacing.three,
    padding: Spacing.three,
    gap: Spacing.two,
  },
  header: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
  },
  titleGroup: {
    gap: Spacing.half,
  },
  addButton: {
    width: 36,
    height: 36,
    borderRadius: 18,
    alignItems: 'center',
    justifyContent: 'center',
  },
  addButtonText: {
    color: '#FFFFFF',
    fontSize: 22,
    fontWeight: '600',
    lineHeight: 24,
    marginTop: -1,
  },
  pressed: {
    opacity: 0.85,
  },
  itemsList: {
    gap: Spacing.two,
  },
  itemRow: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    gap: Spacing.two,
  },
  itemName: {
    flex: 1,
    fontSize: 14,
  },
  emptyText: {
    fontStyle: 'italic',
  },
});
