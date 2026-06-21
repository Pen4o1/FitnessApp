import { Stack, useLocalSearchParams, useRouter } from 'expo-router';
import { SymbolView } from 'expo-symbols';
import { useState } from 'react';
import {
  ActivityIndicator,
  FlatList,
  Keyboard,
  KeyboardAvoidingView,
  Platform,
  Pressable,
  StyleSheet,
  TextInput,
  View,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';

import { AddFoodSheet } from '@/components/food-search/add-food-sheet';
import {
  BarcodeScannerModal,
  type ScanWarnings,
} from '@/components/food-search/barcode-scanner-modal';
import { FoodSearchResultRow } from '@/components/food-search/food-search-result-row';
import { ThemedText } from '@/components/themed-text';
import { ThemedView } from '@/components/themed-view';
import { FoodSearchSkeleton } from '@/components/ui/food-search-skeleton';
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

function todayDateString(): string {
  return new Date().toISOString().slice(0, 10);
}

export default function FoodSearchScreen() {
  const router = useRouter();
  const theme = useTheme();
  const { mealType: rawMealType, date: rawDate } = useLocalSearchParams<{
    mealType?: string;
    date?: string;
  }>();
  const [query, setQuery] = useState('');
  const [selectedFood, setSelectedFood] = useState<FoodSearchResult | null>(null);
  const [scanWarnings, setScanWarnings] = useState<ScanWarnings | null>(null);
  const [scannerVisible, setScannerVisible] = useState(false);
  const [scannerKey, setScannerKey] = useState(0);
  const { results, isLoading, error, hasQuery } = useFoodSearch(query);

  const mealType = isMealType(rawMealType) ? rawMealType : 'breakfast';
  const mealLabel = MEAL_TYPE_LABELS[mealType];
  const logDate = typeof rawDate === 'string' && rawDate.length > 0 ? rawDate : todayDateString();

  function renderListEmpty() {
    if (!hasQuery) {
      return (
        <ThemedText themeColor="textSecondary" style={styles.emptyText}>
          Type at least 2 characters to search for foods.
        </ThemedText>
      );
    }

    if (isLoading) {
      return <FoodSearchSkeleton />;
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

            <View style={styles.searchRow}>
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

              <Pressable
                accessibilityLabel="Scan barcode"
                accessibilityRole="button"
                onPress={() => {
                  setScannerKey((current) => current + 1);
                  setScannerVisible(true);
                }}
                style={({ pressed }) => [
                  styles.scanButton,
                  { backgroundColor: theme.accent },
                  pressed && styles.pressed,
                ]}>
                <SymbolView
                  name={{ ios: 'barcode.viewfinder', android: 'qr_code_scanner', web: 'qr_code_scanner' }}
                  size={24}
                  tintColor="#FFFFFF"
                />
              </Pressable>
            </View>

            <FlatList
              data={results}
              keyExtractor={(item) => item.external_food_id}
              renderItem={({ item }: { item: FoodSearchResult }) => (
                <FoodSearchResultRow
                  item={item}
                  onPress={(food) => {
                    Keyboard.dismiss();
                    setScanWarnings(null);
                    setSelectedFood(food);
                  }}
                />
              )}
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

      <AddFoodSheet
        visible={selectedFood !== null}
        food={selectedFood}
        mealType={mealType}
        date={logDate}
        hasAllergen={scanWarnings?.hasAllergen ?? false}
        hasDietaryConflict={scanWarnings?.hasDietaryConflict ?? false}
        onClose={() => {
          setSelectedFood(null);
          setScanWarnings(null);
        }}
        onAdded={() => {
          setSelectedFood(null);
          setScanWarnings(null);
          router.back();
        }}
      />

      <BarcodeScannerModal
        key={scannerKey}
        visible={scannerVisible}
        onClose={() => setScannerVisible(false)}
        onFoodFound={(food, warnings) => {
          setScannerVisible(false);
          setScanWarnings(warnings);
          setSelectedFood(food);
        }}
      />
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
  searchRow: {
    flexDirection: 'row',
    alignItems: 'stretch',
    gap: Spacing.two,
  },
  searchField: {
    flex: 1,
    borderRadius: Spacing.three,
    padding: Spacing.three,
  },
  scanButton: {
    width: 56,
    borderRadius: Spacing.three,
    alignItems: 'center',
    justifyContent: 'center',
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
