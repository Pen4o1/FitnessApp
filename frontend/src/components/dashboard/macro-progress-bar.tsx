import { StyleSheet, View } from 'react-native';

import { ThemedText } from '@/components/themed-text';
import { Spacing } from '@/constants/theme';
import { useTheme } from '@/hooks/use-theme';

type MacroProgressBarProps = {
  label: string;
  consumed: number;
  target: number;
  color: string;
};

function formatGrams(value: number): string {
  return Number.isInteger(value) ? `${value}` : value.toFixed(1);
}

export function MacroProgressBar({ label, consumed, target, color }: MacroProgressBarProps) {
  const theme = useTheme();
  const ratio = target > 0 ? Math.min(consumed / target, 1) : 0;
  const percentage = Math.round(ratio * 100);

  return (
    <View style={styles.container}>
      <View style={styles.header}>
        <ThemedText type="smallBold">{label}</ThemedText>
        <ThemedText themeColor="textSecondary" type="small">
          {formatGrams(consumed)} / {formatGrams(target)} g
        </ThemedText>
      </View>

      <View style={[styles.track, { backgroundColor: theme.ringTrack }]}>
        <View
          style={[
            styles.fill,
            {
              backgroundColor: color,
              width: `${percentage}%`,
            },
          ]}
        />
      </View>
    </View>
  );
}

const styles = StyleSheet.create({
  container: {
    gap: Spacing.two,
  },
  header: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
  },
  track: {
    height: 8,
    borderRadius: Spacing.one,
    overflow: 'hidden',
  },
  fill: {
    height: '100%',
    borderRadius: Spacing.one,
    minWidth: 4,
  },
});
