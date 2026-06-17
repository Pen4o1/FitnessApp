import { Pressable, ScrollView, StyleSheet, View } from 'react-native';

import { ThemedText } from '@/components/themed-text';
import { Spacing } from '@/constants/theme';
import { useTheme } from '@/hooks/use-theme';

type OptionChipGroupProps<T extends string> = {
  label: string;
  value: T;
  options: { value: T; label: string; description?: string }[];
  onChange: (value: T) => void;
};

export function OptionChipGroup<T extends string>({
  label,
  value,
  options,
  onChange,
}: OptionChipGroupProps<T>) {
  const theme = useTheme();

  return (
    <View style={styles.container}>
      <ThemedText type="smallBold">{label}</ThemedText>
      <ScrollView
        horizontal
        showsHorizontalScrollIndicator={false}
        contentContainerStyle={styles.chipRow}>
        {options.map((option) => {
          const isSelected = option.value === value;

          return (
            <Pressable
              key={option.value}
              onPress={() => onChange(option.value)}
              style={({ pressed }) => [
                styles.chip,
                {
                  backgroundColor: isSelected ? theme.accent : theme.backgroundSelected,
                  borderColor: isSelected ? theme.accent : theme.backgroundSelected,
                },
                pressed && styles.pressed,
              ]}>
              <ThemedText
                type="smallBold"
                style={{ color: isSelected ? '#FFFFFF' : theme.text }}>
                {option.label}
              </ThemedText>
              {option.description ? (
                <ThemedText
                  type="small"
                  style={{ color: isSelected ? '#FFFFFF' : theme.textSecondary }}>
                  {option.description}
                </ThemedText>
              ) : null}
            </Pressable>
          );
        })}
      </ScrollView>
    </View>
  );
}

const styles = StyleSheet.create({
  container: {
    gap: Spacing.two,
  },
  chipRow: {
    gap: Spacing.two,
    paddingRight: Spacing.two,
  },
  chip: {
    borderWidth: 1,
    borderRadius: Spacing.three,
    paddingHorizontal: Spacing.three,
    paddingVertical: Spacing.two,
    gap: Spacing.half,
    maxWidth: 180,
  },
  pressed: {
    opacity: 0.85,
  },
});
