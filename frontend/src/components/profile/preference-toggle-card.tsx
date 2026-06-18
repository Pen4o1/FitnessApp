import { Pressable, StyleSheet, Switch, View } from 'react-native';

import { ThemedText } from '@/components/themed-text';
import { Spacing } from '@/constants/theme';
import { useTheme } from '@/hooks/use-theme';

type PreferenceToggleCardProps = {
  label: string;
  description: string;
  value: boolean;
  onChange: (value: boolean) => void;
};

export function PreferenceToggleCard({
  label,
  description,
  value,
  onChange,
}: PreferenceToggleCardProps) {
  const theme = useTheme();

  return (
    <Pressable
      accessibilityRole="switch"
      accessibilityState={{ checked: value }}
      onPress={() => onChange(!value)}
      style={({ pressed }) => [
        styles.card,
        {
          backgroundColor: value ? theme.backgroundSelected : theme.background,
          borderColor: value ? theme.accent : theme.backgroundSelected,
        },
        pressed && styles.pressed,
      ]}>
      <View style={styles.textContent}>
        <ThemedText type="smallBold">{label}</ThemedText>
        <ThemedText themeColor="textSecondary" type="small">
          {description}
        </ThemedText>
      </View>
      <Switch
        accessibilityLabel={label}
        ios_backgroundColor={theme.backgroundSelected}
        thumbColor="#FFFFFF"
        trackColor={{ false: theme.backgroundSelected, true: theme.accent }}
        value={value}
        onValueChange={onChange}
      />
    </Pressable>
  );
}

const styles = StyleSheet.create({
  card: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
    gap: Spacing.three,
    borderWidth: 1,
    borderRadius: Spacing.three,
    paddingHorizontal: Spacing.three,
    paddingVertical: Spacing.three,
  },
  textContent: {
    flex: 1,
    gap: Spacing.half,
  },
  pressed: {
    opacity: 0.85,
  },
});
