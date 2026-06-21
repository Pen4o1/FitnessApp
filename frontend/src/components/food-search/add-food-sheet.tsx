import { useEffect, useMemo, useState } from 'react';
import {
  ActivityIndicator,
  Alert,
  Keyboard,
  Modal,
  Platform,
  Pressable,
  ScrollView,
  StyleSheet,
  TextInput,
  View,
} from 'react-native';
import { useSafeAreaInsets } from 'react-native-safe-area-context';

import { ThemedText } from '@/components/themed-text';
import { ThemedView } from '@/components/themed-view';
import { Spacing } from '@/constants/theme';
import { useLogFoodMutation } from '@/hooks/use-log-food-mutation';
import { useTheme } from '@/hooks/use-theme';
import { ApiError, type LogFoodPayload } from '@/lib/api';
import {
  MEAL_TYPE_LABELS,
  scaleMacrosFromBase,
  type FoodSearchResult,
  type FoodServingOption,
  type MealType,
} from '@/types/nutrition';

type AddFoodSheetProps = {
  visible: boolean;
  food: FoodSearchResult | null;
  mealType: MealType;
  date: string;
  hasAllergen?: boolean;
  hasDietaryConflict?: boolean;
  onClose: () => void;
  onAdded: () => void;
};

function pickDefaultServing(food: FoodSearchResult): FoodServingOption {
  return (
    food.servings.find((serving) => serving.is_default) ??
    food.servings.find((serving) => serving.unit === 'serving') ??
    food.servings[0]
  );
}

function formatQuantity(value: number): string {
  return Number.isInteger(value) ? String(value) : String(value);
}

export function AddFoodSheet({
  visible,
  food,
  mealType,
  date,
  hasAllergen = false,
  hasDietaryConflict = false,
  onClose,
  onAdded,
}: AddFoodSheetProps) {
  const theme = useTheme();
  const insets = useSafeAreaInsets();
  const logFoodMutation = useLogFoodMutation();
  const [selectedServingId, setSelectedServingId] = useState<string | null>(null);
  const [quantity, setQuantity] = useState('100');
  const [error, setError] = useState<string | null>(null);
  const [keyboardHeight, setKeyboardHeight] = useState(0);
  const isSubmitting = logFoodMutation.isPending;

  const selectedServing = useMemo(() => {
    if (!food) {
      return null;
    }

    if (selectedServingId) {
      return food.servings.find((serving) => serving.id === selectedServingId) ?? pickDefaultServing(food);
    }

    return pickDefaultServing(food);
  }, [food, selectedServingId]);

  useEffect(() => {
    if (!food) {
      return;
    }

    const defaultServing = pickDefaultServing(food);
    setSelectedServingId(defaultServing.id);
    setQuantity(formatQuantity(defaultServing.default_quantity));
    setError(null);
  }, [food]);

  useEffect(() => {
    const showEvent = Platform.OS === 'ios' ? 'keyboardWillShow' : 'keyboardDidShow';
    const hideEvent = Platform.OS === 'ios' ? 'keyboardWillHide' : 'keyboardDidHide';

    const showSubscription = Keyboard.addListener(showEvent, (event) => {
      setKeyboardHeight(event.endCoordinates.height);
    });
    const hideSubscription = Keyboard.addListener(hideEvent, () => {
      setKeyboardHeight(0);
    });

    return () => {
      showSubscription.remove();
      hideSubscription.remove();
    };
  }, []);

  const parsedQuantity = parseFloat(quantity);
  const isValidQuantity = Number.isFinite(parsedQuantity) && parsedQuantity > 0;

  const preview = useMemo(() => {
    if (!selectedServing || !isValidQuantity) {
      return null;
    }

    return scaleMacrosFromBase(
      selectedServing.calories,
      selectedServing.protein_g,
      selectedServing.carbs_g,
      selectedServing.fat_g,
      selectedServing.base_quantity,
      parsedQuantity,
    );
  }, [selectedServing, isValidQuantity, parsedQuantity]);

  const quantityLabel =
    selectedServing?.unit === 'g'
      ? 'Amount (grams)'
      : `Quantity (${selectedServing?.description ?? 'serving'})`;

  const sheetBottomInset = keyboardHeight > 0 ? keyboardHeight : insets.bottom;

  function handleServingSelect(serving: FoodServingOption) {
    setSelectedServingId(serving.id);
    setQuantity(formatQuantity(serving.default_quantity));
    setError(null);
  }

  function handleClose() {
    if (isSubmitting) {
      return;
    }

    Keyboard.dismiss();
    setKeyboardHeight(0);
    setSelectedServingId(null);
    setQuantity('100');
    setError(null);
    onClose();
  }

  function handleSubmit() {
    if (!food || !selectedServing || !isValidQuantity) {
      setError('Enter a valid amount.');
      return;
    }

    setError(null);

    const payload: LogFoodPayload = {
      date,
      meal_type: mealType,
      quantity: parsedQuantity,
      serving_unit: selectedServing.unit_label,
      serving_description: selectedServing.description,
      base_quantity: selectedServing.base_quantity,
      external_food_id: food.external_food_id,
      external_source: food.external_source,
      food_name: food.food_name,
      brand_name: food.brand_name,
      calories_per_base: selectedServing.calories,
      protein_g_per_base: selectedServing.protein_g,
      carbs_g_per_base: selectedServing.carbs_g,
      fat_g_per_base: selectedServing.fat_g,
    };

    logFoodMutation.mutate(payload, {
      onError: (err) => {
        const message =
          err instanceof ApiError ? err.message : 'Could not add food. Please try again.';

        Alert.alert('Could not add food', message);
      },
    });

    setSelectedServingId(null);
    setQuantity('100');
    onAdded();
  }

  if (!food || !selectedServing) {
    return null;
  }

  const mealLabel = MEAL_TYPE_LABELS[mealType];
  const hasMultipleServings = food.servings.length > 1;
  const showAllergenWarning = hasAllergen;
  const showDietaryWarning = hasDietaryConflict;

  return (
    <Modal animationType="slide" transparent visible={visible} onRequestClose={handleClose}>
      <Pressable style={styles.backdrop} onPress={handleClose}>
        <Pressable
          style={[styles.sheetWrapper, { paddingBottom: sheetBottomInset }]}
          onPress={(event) => event.stopPropagation()}>
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

              {showAllergenWarning ? (
                <View style={[styles.warningBanner, { backgroundColor: theme.warning + '22', borderColor: theme.warning }]}>
                  <ThemedText style={[styles.warningText, { color: theme.warning }]} type="smallBold">
                    Allergen warning — this product may contain an ingredient you are allergic to.
                  </ThemedText>
                </View>
              ) : null}

              {showDietaryWarning ? (
                <View style={[styles.warningBanner, { backgroundColor: theme.warning + '22', borderColor: theme.warning }]}>
                  <ThemedText style={[styles.warningText, { color: theme.warning }]} type="smallBold">
                    Dietary conflict — this product may not match your dietary preferences.
                  </ThemedText>
                </View>
              ) : null}

              {hasMultipleServings ? (
                <View style={styles.servingSection}>
                  <ThemedText type="smallBold">Serving size</ThemedText>
                  <ScrollView
                    horizontal
                    keyboardShouldPersistTaps="handled"
                    showsHorizontalScrollIndicator={false}
                    contentContainerStyle={styles.servingOptions}>
                    {food.servings.map((serving) => {
                      const isSelected = serving.id === selectedServing.id;

                      return (
                        <Pressable
                          key={serving.id}
                          onPress={() => handleServingSelect(serving)}
                          style={({ pressed }) => [
                            styles.servingChip,
                            {
                              backgroundColor: isSelected
                                ? theme.accent
                                : theme.backgroundSelected,
                              borderColor: isSelected ? theme.accent : theme.backgroundElement,
                            },
                            pressed && styles.pressed,
                          ]}>
                          <ThemedText
                            type="smallBold"
                            numberOfLines={2}
                            style={isSelected ? styles.servingChipTextSelected : undefined}>
                            {serving.description}
                          </ThemedText>
                          <ThemedText
                            themeColor={isSelected ? undefined : 'textSecondary'}
                            type="small"
                            style={isSelected ? styles.servingChipTextSelected : undefined}>
                            {serving.calories} kcal
                          </ThemedText>
                        </Pressable>
                      );
                    })}
                  </ScrollView>
                </View>
              ) : null}

              <ThemedView type="backgroundSelected" style={styles.quantityField}>
                <ThemedText type="smallBold">{quantityLabel}</ThemedText>
                <TextInput
                  keyboardType={selectedServing.unit === 'g' ? 'decimal-pad' : 'decimal-pad'}
                  placeholder={formatQuantity(selectedServing.default_quantity)}
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
  servingSection: {
    gap: Spacing.two,
    marginTop: Spacing.one,
  },
  servingOptions: {
    gap: Spacing.two,
    paddingVertical: Spacing.one,
  },
  servingChip: {
    minWidth: 112,
    maxWidth: 160,
    borderRadius: Spacing.three,
    borderWidth: 1,
    paddingHorizontal: Spacing.three,
    paddingVertical: Spacing.two,
    gap: Spacing.half,
  },
  servingChipTextSelected: {
    color: '#FFFFFF',
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
  warningBanner: {
    borderWidth: 1,
    borderRadius: Spacing.two,
    paddingHorizontal: Spacing.three,
    paddingVertical: Spacing.two,
    marginTop: Spacing.one,
  },
  warningText: {
    lineHeight: 20,
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
