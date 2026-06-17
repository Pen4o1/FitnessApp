import { Stack, useLocalSearchParams, useRouter } from 'expo-router';
import { useState } from 'react';
import {
  ActivityIndicator,
  FlatList,
  KeyboardAvoidingView,
  Platform,
  Pressable,
  StyleSheet,
  TextInput,
  View,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';

import { FoodSearchResultRow } from '@/components/food-search/food-search-result-row';
import { ThemedText } from '@/components/themed-text';
import { ThemedView } from '@/components/themed-view';
import { MaxContentWidth, Spacing } from '@/constants/theme';
import { useFoodSearch } from '@/hooks/use-food-search';
import { useTheme } from '@/hooks/use-theme';
import { MEAL_TYPE_LABELS, type FoodSearchResult, type MealType } from '@/types/nutrition';

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
  const [query, setQuery] = useState('');
  const { results, isLoading, error, hasQuery } = useFoodSearch(query);

  const mealType = isMealType(rawMealType) ? rawMealType : 'breakfast';
  const mealLabel = MEAL_TYPE_LABELS[mealType];

  function renderListEmpty() {
    if (!hasQuery) {
      return (
        <ThemedText themeColor="textSecondary" style={styles.emptyText}>
          Type at least 2 characters to search for foods.
        </ThemedText>
      );
    }

    if (isLoading) {
      return (
        <View style={styles.centeredState}>
          <ActivityIndicator color={theme.accent} />
        </View>
      );
    }

    if (error) {
      return (
        <ThemedText type="small" style={styles.errorText}>
          {error}
        </ThemedText>
      );
    }

    return (
      <ThemedText themeColor="textSecondary" style={styles.emptyText}>
        No foods found for &quot;{query.trim()}&quot;.
      </ThemedText>
    );
  }

  return (
    <>
      <Stack.Screen options={{ title: `Search — ${mealLabel}` }} />
      <ThemedView style={styles.container}>
        <SafeAreaView style={styles.safeArea}>
          <KeyboardAvoidingView
            behavior={Platform.OS === 'ios' ? 'padding' : undefined}
            style={styles.content}>
            <ThemedText type="subtitle" style={styles.title}>
              Food search
            </ThemedText>
            <ThemedText themeColor="textSecondary" style={styles.subtitle}>
              {mealLabel}
            </ThemedText>

            <ThemedView type="backgroundElement" style={styles.searchField}>
              <TextInput
                autoCapitalize="none"
                autoCorrect={false}
                clearButtonMode="while-editing"
                placeholder="Search foods..."
                placeholderTextColor={theme.textSecondary}
                returnKeyType="search"
                style={[styles.input, { color: theme.text, borderColor: theme.backgroundSelected }]}
                value={query}
                onChangeText={setQuery}
              />
            </ThemedView>

            <FlatList
              data={results}
              keyExtractor={(item) => item.external_food_id}
              renderItem={({ item }: { item: FoodSearchResult }) => <FoodSearchResultRow item={item} />}
              contentContainerStyle={styles.listContent}
              keyboardShouldPersistTaps="handled"
              ListEmptyComponent={renderListEmpty}
              ListFooterComponent={
                isLoading && hasQuery && results.length > 0 ? (
                  <View style={styles.footerLoader}>
                    <ActivityIndicator color={theme.accent} />
                  </View>
                ) : null
              }
              showsVerticalScrollIndicator={false}
            />

            <Pressable
              onPress={() => router.back()}
              style={({ pressed }) => [
                styles.backButton,
                { backgroundColor: theme.backgroundSelected },
                pressed && styles.pressed,
              ]}>
              <ThemedText type="smallBold">Back</ThemedText>
            </Pressable>
          </KeyboardAvoidingView>
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
  },
  content: {
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
  searchField: {
    borderRadius: Spacing.three,
    padding: Spacing.three,
  },
  input: {
    borderWidth: 1,
    borderRadius: Spacing.two,
    paddingHorizontal: Spacing.three,
    paddingVertical: Spacing.two,
    fontSize: 16,
  },
  listContent: {
    flexGrow: 1,
    gap: Spacing.two,
    paddingBottom: Spacing.two,
  },
  emptyText: {
    textAlign: 'center',
    lineHeight: 22,
    paddingTop: Spacing.four,
  },
  centeredState: {
    alignItems: 'center',
    paddingTop: Spacing.four,
  },
  errorText: {
    color: '#d64545',
    textAlign: 'center',
    paddingTop: Spacing.four,
  },
  footerLoader: {
    paddingVertical: Spacing.three,
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
