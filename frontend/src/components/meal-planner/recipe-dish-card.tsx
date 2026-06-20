import { Image } from 'expo-image';
import { StyleSheet, View } from 'react-native';

import { Collapsible } from '@/components/ui/collapsible';
import { ThemedText } from '@/components/themed-text';
import { Spacing } from '@/constants/theme';
import { useTheme } from '@/hooks/use-theme';
import type { PlannedDish, PlannedFoodItem, PlannedRecipeItem } from '@/types/meal-plan';
import { isPlannedFoodItem, isPlannedRecipeItem } from '@/types/meal-plan';

type RecipeDishCardProps = {
  dish: PlannedDish;
};

function formatPortions(portions: number): string {
  const label = portions === 1 ? 'serving' : 'servings';

  return `${portions} ${label}`;
}

function formatMinutes(minutes: number | null): string | null {
  if (minutes === null || minutes <= 0) {
    return null;
  }

  return `${minutes} min`;
}

function FoodDishContent({ dish }: { dish: PlannedFoodItem }) {
  return (
    <View style={styles.foodBlock}>
      <ThemedText style={styles.itemName}>
        {dish.food_name}
        {dish.brand_name ? ` · ${dish.brand_name}` : ''}
      </ThemedText>
      <ThemedText themeColor="textSecondary" type="small" style={styles.quantityLine}>
        {dish.quantity} {dish.serving_unit} ({dish.serving_description})
      </ThemedText>
      <ThemedText themeColor="textSecondary" type="small">
        {dish.calories} kcal · P {dish.protein_g}g · C {dish.carbs_g}g · F {dish.fat_g}g
      </ThemedText>
      <ThemedText themeColor="textSecondary" type="small" style={styles.fallbackNote}>
        Fallback food item — no recipe directions available.
      </ThemedText>
    </View>
  );
}

function RecipeDishContent({ dish }: { dish: PlannedRecipeItem }) {
  const theme = useTheme();
  const prepTime = formatMinutes(dish.prep_time_min);
  const cookTime = formatMinutes(dish.cooking_time_min);
  const hasMeta = prepTime !== null || cookTime !== null;
  const hasIngredients = dish.ingredients.length > 0;
  const hasDirections = dish.directions.length > 0;

  return (
    <View style={styles.recipeBlock}>
      <View style={styles.heroRow}>
        {dish.image_url ? (
          <Image source={{ uri: dish.image_url }} style={styles.thumbnail} contentFit="cover" />
        ) : (
          <View style={[styles.thumbnailPlaceholder, { borderColor: theme.neonGreen + '44' }]}>
            <ThemedText themeColor="textSecondary" type="small">No image</ThemedText>
          </View>
        )}

        <View style={styles.heroContent}>
          <ThemedText style={styles.recipeName}>{dish.recipe_name}</ThemedText>
          <View style={[styles.portionsBadge, { backgroundColor: theme.neonGreen + '22' }]}>
            <ThemedText type="smallBold" style={{ color: theme.neonGreen }}>
              {formatPortions(dish.portions)}
            </ThemedText>
          </View>
          <ThemedText themeColor="textSecondary" type="small">
            {dish.calories} kcal · P {dish.protein_g}g · C {dish.carbs_g}g · F {dish.fat_g}g
          </ThemedText>
        </View>
      </View>

      {dish.description ? (
        <ThemedText themeColor="textSecondary" type="small" style={styles.description}>
          {dish.description}
        </ThemedText>
      ) : null}

      {hasMeta ? (
        <View style={styles.metaRow}>
          {prepTime ? (
            <ThemedText themeColor="textSecondary" type="small" style={styles.metaChip}>
              Prep {prepTime}
            </ThemedText>
          ) : null}
          {cookTime ? (
            <ThemedText themeColor="textSecondary" type="small" style={styles.metaChip}>
              Cook {cookTime}
            </ThemedText>
          ) : null}
        </View>
      ) : null}

      {hasIngredients ? (
        <Collapsible title="Ingredients">
          <View style={styles.listBlock}>
            {dish.ingredients.map((ingredient, index) => (
              <ThemedText key={`${dish.recipe_id}-ingredient-${index}`} type="small" style={styles.listItem}>
                • {ingredient}
              </ThemedText>
            ))}
          </View>
        </Collapsible>
      ) : null}

      {hasDirections ? (
        <Collapsible title="Directions">
          <View style={styles.listBlock}>
            {dish.directions.map((direction) => (
              <ThemedText key={`${dish.recipe_id}-step-${direction.number}`} type="small" style={styles.listItem}>
                {direction.number}. {direction.text}
              </ThemedText>
            ))}
          </View>
        </Collapsible>
      ) : null}
    </View>
  );
}

export function RecipeDishCard({ dish }: RecipeDishCardProps) {
  if (isPlannedRecipeItem(dish)) {
    return <RecipeDishContent dish={dish} />;
  }

  if (isPlannedFoodItem(dish)) {
    return <FoodDishContent dish={dish} />;
  }

  return null;
}

const styles = StyleSheet.create({
  recipeBlock: {
    gap: Spacing.two,
  },
  foodBlock: {
    gap: Spacing.half,
  },
  heroRow: {
    flexDirection: 'row',
    gap: Spacing.two,
    alignItems: 'flex-start',
  },
  thumbnail: {
    width: 72,
    height: 72,
    borderRadius: Spacing.two,
  },
  thumbnailPlaceholder: {
    width: 72,
    height: 72,
    borderRadius: Spacing.two,
    borderWidth: 1,
    alignItems: 'center',
    justifyContent: 'center',
  },
  heroContent: {
    flex: 1,
    gap: Spacing.half,
  },
  recipeName: {
    fontSize: 16,
    fontWeight: '600',
    lineHeight: 22,
  },
  portionsBadge: {
    alignSelf: 'flex-start',
    borderRadius: Spacing.two,
    paddingHorizontal: Spacing.two,
    paddingVertical: Spacing.half,
  },
  description: {
    lineHeight: 20,
  },
  metaRow: {
    flexDirection: 'row',
    gap: Spacing.two,
  },
  metaChip: {
    fontStyle: 'italic',
  },
  listBlock: {
    gap: Spacing.one,
  },
  listItem: {
    lineHeight: 20,
  },
  itemName: {
    fontSize: 15,
    fontWeight: '600',
  },
  quantityLine: {
    lineHeight: 18,
  },
  fallbackNote: {
    fontStyle: 'italic',
    lineHeight: 18,
    marginTop: Spacing.half,
  },
});
