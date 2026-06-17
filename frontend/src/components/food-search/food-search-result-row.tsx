import { StyleSheet, View } from 'react-native';

import { ThemedText } from '@/components/themed-text';
import { ThemedView } from '@/components/themed-view';
import { Spacing } from '@/constants/theme';
import type { FoodSearchResult } from '@/types/nutrition';

type FoodSearchResultRowProps = {
  item: FoodSearchResult;
};

export function FoodSearchResultRow({ item }: FoodSearchResultRowProps) {
  return (
    <ThemedView type="backgroundElement" style={styles.container}>
      <View style={styles.header}>
        <ThemedText type="smallBold" style={styles.name} numberOfLines={2}>
          {item.food_name}
        </ThemedText>
        <ThemedText type="smallBold">{item.calories} kcal</ThemedText>
      </View>

      {item.brand_name ? (
        <ThemedText themeColor="textSecondary" type="small" numberOfLines={1}>
          {item.brand_name}
        </ThemedText>
      ) : null}

      <ThemedText themeColor="textSecondary" type="small">
        Per {item.serving_description}: P {item.protein_g}g · C {item.carbs_g}g · F {item.fat_g}g
      </ThemedText>
    </ThemedView>
  );
}

const styles = StyleSheet.create({
  container: {
    borderRadius: Spacing.three,
    padding: Spacing.three,
    gap: Spacing.one,
  },
  header: {
    flexDirection: 'row',
    alignItems: 'flex-start',
    justifyContent: 'space-between',
    gap: Spacing.two,
  },
  name: {
    flex: 1,
    fontSize: 15,
  },
});
