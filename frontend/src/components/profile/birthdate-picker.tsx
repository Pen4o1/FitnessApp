import { DateTimePicker } from '@expo/ui/community/datetime-picker';
import { useMemo } from 'react';
import { Platform, StyleSheet, View } from 'react-native';

import { Spacing } from '@/constants/theme';
import { useTheme } from '@/hooks/use-theme';
import { birthdateToDate, formatBirthdate, getBirthdateLimits } from '@/lib/birthdate';

type BirthdatePickerProps = {
  value: string;
  onChange: (value: string) => void;
};

export function BirthdatePicker({ value, onChange }: BirthdatePickerProps) {
  const theme = useTheme();
  const { minimumDate, maximumDate } = useMemo(() => getBirthdateLimits(), []);
  const selectedDate = useMemo(
    () => birthdateToDate(value) ?? maximumDate,
    [maximumDate, value],
  );

  return (
    <View style={styles.container}>
      <DateTimePicker
        value={selectedDate}
        mode="date"
        display={Platform.OS === 'ios' ? 'inline' : 'default'}
        presentation="inline"
        minimumDate={minimumDate}
        maximumDate={maximumDate}
        accentColor={theme.accent}
        onValueChange={(_, date) => onChange(formatBirthdate(date))}
        style={styles.picker}
      />
    </View>
  );
}

const styles = StyleSheet.create({
  container: {
    overflow: 'hidden',
  },
  picker: {
    alignSelf: 'stretch',
    minHeight: Platform.OS === 'ios' ? 320 : undefined,
    marginHorizontal: -Spacing.two,
  },
});
