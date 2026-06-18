import { useFocusEffect } from 'expo-router';
import { useCallback, useEffect, useMemo, useRef, useState } from 'react';
import { ActivityIndicator, Pressable, StyleSheet, View } from 'react-native';

import { PreferenceToggleCard } from '@/components/profile/preference-toggle-card';
import { ThemedText } from '@/components/themed-text';
import { ThemedView } from '@/components/themed-view';
import { Spacing } from '@/constants/theme';
import { useTheme } from '@/hooks/use-theme';
import { ApiError, getUserPreferences, updateUserPreferences } from '@/lib/api';
import {
  ALLERGY_OPTIONS,
  DIETARY_PREFERENCE_OPTIONS,
  EMPTY_USER_PREFERENCES,
  type AllergyRestriction,
  type DietaryPreference,
  type UserPreferences,
} from '@/types/dietary';

function preferencesEqual(a: UserPreferences, b: UserPreferences): boolean {
  return (
    JSON.stringify(a.dietary_preferences.slice().sort()) ===
      JSON.stringify(b.dietary_preferences.slice().sort()) &&
    JSON.stringify(a.allergies.slice().sort()) === JSON.stringify(b.allergies.slice().sort())
  );
}

export function DietaryPreferencesSection() {
  const theme = useTheme();
  const [selections, setSelections] = useState<UserPreferences>(EMPTY_USER_PREFERENCES);
  const [savedSnapshot, setSavedSnapshot] = useState<UserPreferences>(EMPTY_USER_PREFERENCES);
  const [isLoading, setIsLoading] = useState(true);
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [successMessage, setSuccessMessage] = useState<string | null>(null);

  const hasChanges = useMemo(
    () => !preferencesEqual(selections, savedSnapshot),
    [selections, savedSnapshot],
  );
  const hasChangesRef = useRef(hasChanges);

  useEffect(() => {
    hasChangesRef.current = hasChanges;
  }, [hasChanges]);

  useEffect(() => {
    if (!successMessage) {
      return;
    }

    const timeout = setTimeout(() => setSuccessMessage(null), 3000);
    return () => clearTimeout(timeout);
  }, [successMessage]);

  const loadPreferences = useCallback(async () => {
    setError(null);

    try {
      const preferences = await getUserPreferences();
      if (hasChangesRef.current) {
        return;
      }

      setSelections(preferences);
      setSavedSnapshot(preferences);
    } catch {
      if (hasChangesRef.current) {
        return;
      }

      setError('Could not load your dietary preferences.');
    } finally {
      setIsLoading(false);
    }
  }, []);

  useFocusEffect(
    useCallback(() => {
      if (!hasChangesRef.current) {
        void loadPreferences();
      }
    }, [loadPreferences]),
  );

  function toggleDietaryPreference(value: DietaryPreference, enabled: boolean) {
    hasChangesRef.current = true;
    setSuccessMessage(null);
    setSelections((current) => ({
      ...current,
      dietary_preferences: enabled
        ? [...current.dietary_preferences, value]
        : current.dietary_preferences.filter((item) => item !== value),
    }));
  }

  function toggleAllergy(value: AllergyRestriction, enabled: boolean) {
    hasChangesRef.current = true;
    setSuccessMessage(null);
    setSelections((current) => ({
      ...current,
      allergies: enabled
        ? [...current.allergies, value]
        : current.allergies.filter((item) => item !== value),
    }));
  }

  async function handleSave() {
    setError(null);
    setSuccessMessage(null);
    setIsSubmitting(true);

    try {
      const savedPreferences = await updateUserPreferences(selections);
      setSelections(savedPreferences);
      setSavedSnapshot(savedPreferences);
      hasChangesRef.current = false;
      setSuccessMessage('Preferences saved.');
    } catch (err) {
      if (err instanceof ApiError) {
        setError(err.message);
      } else {
        setError('Could not save your preferences. Please try again.');
      }
    } finally {
      setIsSubmitting(false);
    }
  }

  if (isLoading) {
    return (
      <ThemedView type="backgroundElement" style={styles.section}>
        <View style={styles.loadingState}>
          <ActivityIndicator color={theme.accent} />
          <ThemedText themeColor="textSecondary" type="small">
            Loading dietary preferences...
          </ThemedText>
        </View>
      </ThemedView>
    );
  }

  return (
    <ThemedView type="backgroundElement" style={styles.section}>
      <ThemedText type="smallBold" style={styles.sectionTitle}>
        Dietary preferences
      </ThemedText>
      <ThemedText themeColor="textSecondary" type="small" style={styles.sectionDescription}>
        Select the diets you follow. You can choose more than one.
      </ThemedText>

      <View style={styles.cardList}>
        {DIETARY_PREFERENCE_OPTIONS.map((option) => (
          <PreferenceToggleCard
            key={option.value}
            description={option.description}
            label={option.label}
            value={selections.dietary_preferences.includes(option.value)}
            onChange={(enabled) => toggleDietaryPreference(option.value, enabled)}
          />
        ))}
      </View>

      <ThemedText type="smallBold" style={styles.subsectionTitle}>
        Allergies
      </ThemedText>
      <ThemedText themeColor="textSecondary" type="small" style={styles.sectionDescription}>
        Tell us what to avoid when suggesting meals and foods.
      </ThemedText>

      <View style={styles.cardList}>
        {ALLERGY_OPTIONS.map((option) => (
          <PreferenceToggleCard
            key={option.value}
            description={option.description}
            label={option.label}
            value={selections.allergies.includes(option.value)}
            onChange={(enabled) => toggleAllergy(option.value, enabled)}
          />
        ))}
      </View>

      {error ? (
        <ThemedText type="small" style={styles.errorText}>
          {error}
        </ThemedText>
      ) : null}

      {successMessage ? (
        <ThemedText style={[styles.successText, { color: theme.success }]} type="small">
          {successMessage}
        </ThemedText>
      ) : null}

      <Pressable
        disabled={isSubmitting || !hasChanges}
        onPress={handleSave}
        style={({ pressed }) => [
          styles.primaryButton,
          { backgroundColor: theme.accent },
          (!hasChanges || isSubmitting) && styles.disabledButton,
          pressed && styles.pressed,
        ]}>
        {isSubmitting ? (
          <ActivityIndicator color="#FFFFFF" />
        ) : (
          <ThemedText type="smallBold" style={styles.primaryButtonText}>
            Save preferences
          </ThemedText>
        )}
      </Pressable>
    </ThemedView>
  );
}

const styles = StyleSheet.create({
  section: {
    borderRadius: Spacing.four,
    padding: Spacing.four,
    gap: Spacing.three,
  },
  sectionTitle: {
    fontSize: 16,
  },
  subsectionTitle: {
    fontSize: 15,
    marginTop: Spacing.one,
  },
  sectionDescription: {
    marginTop: -Spacing.one,
  },
  cardList: {
    gap: Spacing.two,
  },
  loadingState: {
    alignItems: 'center',
    gap: Spacing.two,
    paddingVertical: Spacing.three,
  },
  primaryButton: {
    alignItems: 'center',
    justifyContent: 'center',
    borderRadius: Spacing.three,
    paddingVertical: Spacing.three,
  },
  primaryButtonText: {
    color: '#FFFFFF',
  },
  errorText: {
    color: '#d64545',
  },
  successText: {
    textAlign: 'center',
  },
  disabledButton: {
    opacity: 0.55,
  },
  pressed: {
    opacity: 0.85,
  },
});
