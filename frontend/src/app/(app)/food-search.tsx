import { Stack, useLocalSearchParams, useRouter } from 'expo-router';
import { Pressable, StyleSheet } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';

import { ThemedText } from '@/components/themed-text';
import { ThemedView } from '@/components/themed-view';
import { MaxContentWidth, Spacing } from '@/constants/theme';
import { useTheme } from '@/hooks/use-theme';
import { MEAL_TYPE_LABELS, type MealType } from '@/types/nutrition';

function isMealType(value: string | string[] | undefined): value is MealType {
  return (
    value === 'breakfast' ||
    value === 'lunch' ||
    value === 'dinner' ||
    value === 'snack'
  );
}

export default function FoodSearchScreen() {
  const router = useRouter();
  const theme = useTheme();
  const { mealType: rawMealType } = useLocalSearchParams<{ mealType?: string; date?: string }>();

  const mealType = isMealType(rawMealType) ? rawMealType : 'breakfast';
  const mealLabel = MEAL_TYPE_LABELS[mealType];

  return (
    <>
      <Stack.Screen options={{ title: `Search — ${mealLabel}` }} />
      <ThemedView style={styles.container}>
        <SafeAreaView style={styles.safeArea}>
          <ThemedText type="subtitle" style={styles.title}>
            Food search
          </ThemedText>
          <ThemedText themeColor="textSecondary" style={styles.subtitle}>
            {mealLabel}
          </ThemedText>

          <ThemedView type="backgroundElement" style={styles.placeholder}>
            <ThemedText themeColor="textSecondary" style={styles.placeholderText}>
              Search for foods and add them to your daily log here.
            </ThemedText>
          </ThemedView>

          <Pressable
            onPress={() => router.back()}
            style={({ pressed }) => [
              styles.backButton,
              { backgroundColor: theme.backgroundSelected },
              pressed && styles.pressed,
            ]}>
            <ThemedText type="smallBold">Back</ThemedText>
          </Pressable>
        </SafeAreaView>
      </ThemedView>
    </>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
  },
  safeArea: {
    flex: 1,
    paddingHorizontal: Spacing.four,
    paddingTop: Spacing.three,
    gap: Spacing.three,
    maxWidth: MaxContentWidth,
    alignSelf: 'center',
    width: '100%',
  },
  title: {
    fontSize: 28,
    lineHeight: 36,
  },
  subtitle: {
    fontSize: 16,
  },
  placeholder: {
    flex: 1,
    borderRadius: Spacing.three,
    padding: Spacing.four,
    justifyContent: 'center',
    alignItems: 'center',
    minHeight: 200,
  },
  placeholderText: {
    textAlign: 'center',
    lineHeight: 22,
  },
  backButton: {
    alignItems: 'center',
    justifyContent: 'center',
    borderRadius: Spacing.three,
    paddingVertical: Spacing.three,
    marginBottom: Spacing.three,
  },
  pressed: {
    opacity: 0.8,
  },
});
