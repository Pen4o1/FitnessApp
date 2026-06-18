import { SymbolView } from 'expo-symbols';
import { Pressable, StyleSheet, View } from 'react-native';

import { ThemedText } from '@/components/themed-text';
import { Spacing } from '@/constants/theme';
import { useTheme } from '@/hooks/use-theme';

type PreferenceToggleCardProps = {
  label: string;
  description: string;
  value: boolean;
  icon: { ios: any; android: any; web: any };
  onChange: (value: boolean) => void;
};

export function PreferenceToggleCard({
  label,
  description,
  value,
  icon,
  onChange,
}: PreferenceToggleCardProps) {
  const theme = useTheme();

  return (
    <Pressable
      accessibilityRole="checkbox"
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
      <View style={[styles.iconContainer, { backgroundColor: theme.backgroundElement }]}>
        <SymbolView
          name={icon}
          size={20}
          tintColor={value ? theme.accent : theme.textSecondary}
        />
      </View>
      
      <View style={styles.textContent}>
        <ThemedText type="smallBold">{label}</ThemedText>
        <ThemedText themeColor="textSecondary" type="small">
          {description}
        </ThemedText>
      </View>

      <View
        style={[
          styles.checkbox,
          {
            borderColor: value ? theme.accent : theme.textSecondary,
            backgroundColor: value ? theme.accent : 'transparent',
          },
        ]}>
        {value ? (
          <SymbolView
            name={{ ios: 'checkmark', android: 'check', web: 'check' }}
            size={12}
            weight="bold"
            tintColor="#FFFFFF"
          />
        ) : null}
      </View>
    </Pressable>
  );
}

const styles = StyleSheet.create({
  card: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: Spacing.three,
    borderWidth: 1,
    borderRadius: Spacing.three,
    paddingHorizontal: Spacing.three,
    paddingVertical: Spacing.three,
  },
  iconContainer: {
    width: 40,
    height: 40,
    borderRadius: 20,
    alignItems: 'center',
    justifyContent: 'center',
  },
  textContent: {
    flex: 1,
    gap: 2,
  },
  checkbox: {
    width: 22,
    height: 22,
    borderRadius: 11,
    borderWidth: 2,
    alignItems: 'center',
    justifyContent: 'center',
  },
  pressed: {
    opacity: 0.85,
  },
});
