import { useMemo, useState } from 'react';
import {
  ActivityIndicator,
  Modal,
  Pressable,
  StyleSheet,
  TextInput,
  View,
} from 'react-native';

import { ThemedText } from '@/components/themed-text';
import { ThemedView } from '@/components/themed-view';
import { Spacing } from '@/constants/theme';
import { useTheme } from '@/hooks/use-theme';
import { ApiError, logFood, type LogFoodPayload } from '@/lib/api';
import {
  MEAL_TYPE_LABELS,
  scaleMacrosFrom100g,
  type FoodSearchResult,
  type MealType,
} from '@/types/nutrition';

type AddFoodSheetProps = {
  visible: boolean;
  food: FoodSearchResult | null;
  mealType: MealType;
  date: string;
  onClose: () => void;
  onAdded: () => void;
};

export function AddFoodSheet({
  visible,
  food,
  mealType,
  date,
  onClose,
  onAdded,
}: AddFoodSheetProps) {
  const theme = useTheme();
  const [quantity, setQuantity] = useState('100');
  const [error, setError] = useState<string | null>(null);
  const [isSubmitting, setIsSubmitting] = useState(false);

  const quantityGrams = parseFloat(quantity);
  const isValidQuantity = Number.isFinite(quantityGrams) && quantityGrams > 0;

  const preview = useMemo(() => {
    if (!food || !isValidQuantity) {
      return null;
    }

    return scaleMacrosFrom100g(
      food.calories,
      food.protein_g,
      food.carbs_g,
      food.fat_g,
      quantityGrams,
    );
  }, [food, isValidQuantity, quantityGrams]);

  function handleClose() {
    if (isSubmitting) {
      return;
    }

    setQuantity('100');
    setError(null);
    onClose();
  }

  async function handleSubmit() {
    if (!food || !isValidQuantity) {
      setError('Enter a valid amount in grams.');
      return;
    }

    setError(null);
    setIsSubmitting(true);

    const payload: LogFoodPayload = {
      date,
      meal_type: mealType,
      quantity: quantityGrams,
      external_food_id: food.external_food_id,
      external_source: food.external_source,
      food_name: food.food_name,
      brand_name: food.brand_name,
      calories_per_100g: food.calories,
      protein_g_per_100g: food.protein_g,
      carbs_g_per_100g: food.carbs_g,
      fat_g_per_100g: food.fat_g,
    };

    try {
      await logFood(payload);
      setQuantity('100');
      onAdded();
    } catch (err) {
      if (err instanceof ApiError) {
        setError(err.message);
      } else {
        setError('Could not add food. Please try again.');
      }
    } finally {
      setIsSubmitting(false);
    }
  }

  if (!food) {
    return null;
  }

  const mealLabel = MEAL_TYPE_LABELS[mealType];

  return (
    <Modal animationType="slide" transparent visible={visible} onRequestClose={handleClose}>
      <Pressable style={styles.backdrop} onPress={handleClose}>
        <Pressable style={styles.sheetWrapper} onPress={(event) => event.stopPropagation()}>
          <ThemedView type="backgroundElement" style={styles.sheet}>
            <ThemedText type="subtitle" style={styles.title}>
              Add food
            </ThemedText>
            <ThemedText type="smallBold" numberOfLines={2}>
              {food.food_name}
            </ThemedText>
            {food.brand_name ? (
              <ThemedText themeColor="textSecondary" type="small" numberOfLines={1}>
                {food.brand_name}
              </ThemedText>
            ) : null}
            <ThemedText themeColor="textSecondary" type="small">
              Adding to {mealLabel}
            </ThemedText>

            <ThemedView type="backgroundSelected" style={styles.quantityField}>
              <ThemedText type="smallBold">Amount (grams)</ThemedText>
              <TextInput
                keyboardType="decimal-pad"
                placeholder="100"
                placeholderTextColor={theme.textSecondary}
                style={[styles.input, { color: theme.text, borderColor: theme.backgroundElement }]}
                value={quantity}
                onChangeText={setQuantity}
              />
            </ThemedView>

            {preview ? (
              <View style={styles.previewRow}>
                <ThemedText type="smallBold">{preview.calories} kcal</ThemedText>
                <ThemedText themeColor="textSecondary" type="small">
                  P {preview.protein_g}g · C {preview.carbs_g}g · F {preview.fat_g}g
                </ThemedText>
              </View>
            ) : null}

            {error ? (
              <ThemedText type="small" style={styles.errorText}>
                {error}
              </ThemedText>
            ) : null}

            <View style={styles.actions}>
              <Pressable
                disabled={isSubmitting}
                onPress={handleClose}
                style={({ pressed }) => [
                  styles.secondaryButton,
                  { backgroundColor: theme.backgroundSelected },
                  pressed && styles.pressed,
                  isSubmitting && styles.disabled,
                ]}>
                <ThemedText type="smallBold">Cancel</ThemedText>
              </Pressable>

              <Pressable
                disabled={isSubmitting || !isValidQuantity}
                onPress={handleSubmit}
                style={({ pressed }) => [
                  styles.primaryButton,
                  { backgroundColor: theme.accent },
                  pressed && styles.pressed,
                  (isSubmitting || !isValidQuantity) && styles.disabled,
                ]}>
                {isSubmitting ? (
                  <ActivityIndicator color="#FFFFFF" />
                ) : (
                  <ThemedText style={styles.primaryButtonText} type="smallBold">
                    Add to {mealLabel}
                  </ThemedText>
                )}
              </Pressable>
            </View>
          </ThemedView>
        </Pressable>
      </Pressable>
    </Modal>
  );
}

const styles = StyleSheet.create({
  backdrop: {
    flex: 1,
    justifyContent: 'flex-end',
    backgroundColor: 'rgba(0, 0, 0, 0.45)',
  },
  sheetWrapper: {
    width: '100%',
  },
  sheet: {
    borderTopLeftRadius: Spacing.four,
    borderTopRightRadius: Spacing.four,
    padding: Spacing.four,
    gap: Spacing.two,
  },
  title: {
    fontSize: 22,
    lineHeight: 28,
  },
  quantityField: {
    borderRadius: Spacing.three,
    padding: Spacing.three,
    gap: Spacing.two,
    marginTop: Spacing.one,
  },
  input: {
    borderWidth: 1,
    borderRadius: Spacing.two,
    paddingHorizontal: Spacing.three,
    paddingVertical: Spacing.two,
    fontSize: 16,
  },
  previewRow: {
    gap: Spacing.one,
    paddingTop: Spacing.one,
  },
  errorText: {
    color: '#d64545',
  },
  actions: {
    flexDirection: 'row',
    gap: Spacing.two,
    marginTop: Spacing.two,
  },
  secondaryButton: {
    flex: 1,
    alignItems: 'center',
    justifyContent: 'center',
    borderRadius: Spacing.three,
    paddingVertical: Spacing.three,
  },
  primaryButton: {
    flex: 1.4,
    alignItems: 'center',
    justifyContent: 'center',
    borderRadius: Spacing.three,
    paddingVertical: Spacing.three,
  },
  primaryButtonText: {
    color: '#FFFFFF',
  },
  pressed: {
    opacity: 0.85,
  },
  disabled: {
    opacity: 0.6,
  },
});
