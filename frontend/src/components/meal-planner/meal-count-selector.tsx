import { Pressable, StyleSheet, View } from 'react-native';

import { ThemedText } from '@/components/themed-text';
import { ThemedView } from '@/components/themed-view';
import { Spacing } from '@/constants/theme';
import { useTheme } from '@/hooks/use-theme';

const MIN_MEALS = 2;
const MAX_MEALS = 6;

type MealCountSelectorProps = {
  value: number;
  onChange: (count: number) => void;
  disabled?: boolean;
};

export function MealCountSelector({ value, onChange, disabled = false }: MealCountSelectorProps) {
  const theme = useTheme();

  function decrement() {
    onChange(Math.max(MIN_MEALS, value - 1));
  }

  function increment() {
    onChange(Math.min(MAX_MEALS, value + 1));
  }

  return (
    <ThemedView type="backgroundElement" style={styles.container}>
      <ThemedText type="smallBold" style={styles.label}>
        Number of Meals
      </ThemedText>

      <View style={styles.controls}>
        <Pressable
          accessibilityLabel="Decrease number of meals"
          disabled={disabled || value <= MIN_MEALS}
          onPress={decrement}
          style={({ pressed }) => [
            styles.stepButton,
            { borderColor: theme.backgroundSelected },
            (disabled || value <= MIN_MEALS) && styles.stepButtonDisabled,
            pressed && !disabled && value > MIN_MEALS && styles.pressed,
          ]}>
          <ThemedText type="smallBold">−</ThemedText>
        </Pressable>

        <View style={[styles.valueBadge, { backgroundColor: theme.neonGreen + '22' }]}>
          <ThemedText type="smallBold" style={[styles.valueText, { color: theme.neonGreen }]}>
            {value}
          </ThemedText>
        </View>

        <Pressable
          accessibilityLabel="Increase number of meals"
          disabled={disabled || value >= MAX_MEALS}
          onPress={increment}
          style={({ pressed }) => [
            styles.stepButton,
            { borderColor: theme.backgroundSelected },
            (disabled || value >= MAX_MEALS) && styles.stepButtonDisabled,
            pressed && !disabled && value < MAX_MEALS && styles.pressed,
          ]}>
          <ThemedText type="smallBold">+</ThemedText>
        </Pressable>
      </View>

      <View style={styles.segmentRow}>
        {Array.from({ length: MAX_MEALS - MIN_MEALS + 1 }, (_, index) => {
          const count = MIN_MEALS + index;
          const selected = count === value;

          return (
            <Pressable
              key={count}
              accessibilityRole="button"
              accessibilityState={{ selected }}
              disabled={disabled}
              onPress={() => onChange(count)}
              style={({ pressed }) => [
                styles.segment,
                {
                  backgroundColor: selected ? theme.neonGreen : theme.background,
                  borderColor: selected ? theme.neonGreen : theme.backgroundSelected,
                },
                disabled && styles.segmentDisabled,
                pressed && !disabled && !selected && styles.pressed,
              ]}>
              <ThemedText
                type="small"
                style={[
                  styles.segmentText,
                  { color: selected ? '#FFFFFF' : theme.textSecondary },
                ]}>
                {count}
              </ThemedText>
            </Pressable>
          );
        })}
      </View>
    </ThemedView>
  );
}

const styles = StyleSheet.create({
  container: {
    borderRadius: Spacing.four,
    padding: Spacing.three,
    gap: Spacing.three,
  },
  label: {
    fontSize: 15,
  },
  controls: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'center',
    gap: Spacing.three,
  },
  stepButton: {
    width: 44,
    height: 44,
    borderRadius: Spacing.two,
    borderWidth: 1,
    alignItems: 'center',
    justifyContent: 'center',
  },
  stepButtonDisabled: {
    opacity: 0.4,
  },
  valueBadge: {
    minWidth: 72,
    paddingVertical: Spacing.two,
    paddingHorizontal: Spacing.three,
    borderRadius: Spacing.two,
    alignItems: 'center',
  },
  valueText: {
    fontSize: 24,
    lineHeight: 28,
  },
  segmentRow: {
    flexDirection: 'row',
    gap: Spacing.one,
  },
  segment: {
    flex: 1,
    paddingVertical: Spacing.two,
    borderRadius: Spacing.two,
    borderWidth: 1,
    alignItems: 'center',
  },
  segmentDisabled: {
    opacity: 0.55,
  },
  segmentText: {
    fontSize: 13,
    fontWeight: '600',
  },
  pressed: {
    opacity: 0.85,
  },
});
