import { useMemo } from 'react';
import { StyleSheet, View } from 'react-native';

import { Spacing } from '@/constants/theme';
import { useTheme } from '@/hooks/use-theme';
import { formatBirthdate, getBirthdateLimits } from '@/lib/birthdate';

type BirthdatePickerProps = {
  value: string;
  onChange: (value: string) => void;
};

export function BirthdatePicker({ value, onChange }: BirthdatePickerProps) {
  const theme = useTheme();
  const { minimumDate, maximumDate } = useMemo(() => getBirthdateLimits(), []);

  return (
    <View style={styles.container}>
      <input
        type="date"
        value={value}
        min={formatBirthdate(minimumDate)}
        max={formatBirthdate(maximumDate)}
        onChange={(event) => onChange(event.target.value)}
        style={{
          width: '100%',
          boxSizing: 'border-box',
          fontSize: 16,
          lineHeight: '24px',
          paddingTop: Spacing.two,
          paddingBottom: Spacing.two,
          paddingLeft: Spacing.three,
          paddingRight: Spacing.three,
          borderWidth: 1,
          borderStyle: 'solid',
          borderColor: theme.backgroundSelected,
          borderRadius: Spacing.two,
          color: theme.text,
          backgroundColor: 'transparent',
          fontFamily: 'inherit',
          outline: 'none',
        }}
      />
    </View>
  );
}

const styles = StyleSheet.create({
  container: {
    width: '100%',
  },
});
