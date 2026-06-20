import { useFocusEffect } from 'expo-router';
import { useCallback, useEffect, useMemo, useRef, useState, type ReactNode } from 'react';
import {
  ActivityIndicator,
  Alert,
  KeyboardAvoidingView,
  Platform,
  Pressable,
  ScrollView,
  StyleSheet,
  TextInput,
  View,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';

import { BirthdatePicker } from '@/components/profile/birthdate-picker';
import { DietaryPreferencesSection } from '@/components/profile/dietary-preferences-section';
import { SavedMealPlansSection } from '@/components/profile/saved-meal-plans-section';
import { OptionChipGroup } from '@/components/profile/option-chip-group';
import { TargetPreviewCard } from '@/components/profile/target-preview-card';
import { ThemedText } from '@/components/themed-text';
import { ThemedView } from '@/components/themed-view';
import { BottomTabInset, MaxContentWidth, Spacing } from '@/constants/theme';
import { useAuth } from '@/contexts/auth-context';
import { useTheme } from '@/hooks/use-theme';
import { ApiError } from '@/lib/api';
import { calculateNutritionTarget } from '@/lib/nutrition-calculator';
import { sanitizeDecimalInput, sanitizeIntegerInput } from '@/lib/profile-input';
import {
  ACTIVITY_LEVEL_OPTIONS,
  GENDER_OPTIONS,
  GOAL_PACE_OPTIONS,
  GOAL_TYPE_OPTIONS,
  type ActivityLevel,
  type Gender,
  type GoalPace,
  type GoalType,
  type UpdateProfilePayload,
} from '@/types/profile';

type ProfileFormState = {
  gender: Gender;
  birthdate: string;
  current_weight_kg: string;
  height_cm: string;
  activity_level: ActivityLevel;
  goal_type: GoalType;
  goal_pace: GoalPace;
  target_weight_kg: string;
};

const DEFAULT_FORM: ProfileFormState = {
  gender: 'male',
  birthdate: '',
  current_weight_kg: '',
  height_cm: '',
  activity_level: 'moderately_active',
  goal_type: 'lose',
  goal_pace: 'moderate',
  target_weight_kg: '',
};

function formFromUser(user: NonNullable<ReturnType<typeof useAuth>['user']>): ProfileFormState {
  return {
    gender: user.gender ?? 'male',
    birthdate: user.birthdate ?? '',
    current_weight_kg: user.current_weight_kg !== null ? String(user.current_weight_kg) : '',
    height_cm: user.height_cm !== null ? String(user.height_cm) : '',
    activity_level: user.nutrition_target?.activity_level ?? 'moderately_active',
    goal_type: user.nutrition_target?.goal_type ?? 'lose',
    goal_pace: user.nutrition_target?.goal_pace ?? 'moderate',
    target_weight_kg:
      user.nutrition_target?.target_weight_kg !== undefined
        ? String(user.nutrition_target.target_weight_kg)
        : user.current_weight_kg !== null
          ? String(user.current_weight_kg)
          : '',
  };
}

function buildPayload(form: ProfileFormState): UpdateProfilePayload | null {
  const currentWeight = Number(form.current_weight_kg);
  const heightCm = Number(form.height_cm);
  const targetWeight = Number(form.target_weight_kg);

  if (
    !form.birthdate ||
    !Number.isFinite(currentWeight) ||
    !Number.isFinite(heightCm) ||
    !Number.isFinite(targetWeight)
  ) {
    return null;
  }

  return {
    gender: form.gender,
    birthdate: form.birthdate,
    current_weight_kg: currentWeight,
    height_cm: Math.round(heightCm),
    activity_level: form.activity_level,
    goal_type: form.goal_type,
    goal_pace: form.goal_type === 'maintain' ? 'moderate' : form.goal_pace,
    target_weight_kg: targetWeight,
  };
}

export function ProfileScreen() {
  const theme = useTheme();
  const { user, refreshUser, updateProfile, signOut } = useAuth();
  const [form, setForm] = useState<ProfileFormState>(() =>
    user ? formFromUser(user) : DEFAULT_FORM,
  );
  const [savedSnapshot, setSavedSnapshot] = useState<ProfileFormState>(() =>
    user ? formFromUser(user) : DEFAULT_FORM,
  );
  const [fieldErrors, setFieldErrors] = useState<Record<string, string[]>>({});
  const [error, setError] = useState<string | null>(null);
  const [isLoading, setIsLoading] = useState(() => !user);
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [isSigningOut, setIsSigningOut] = useState(false);

  const loadProfile = useCallback(async () => {
    setError(null);

    try {
      const currentUser = await refreshUser();
      if (hasChangesRef.current) {
        return;
      }

      const nextForm = formFromUser(currentUser);
      setForm(nextForm);
      setSavedSnapshot(nextForm);
    } catch {
      if (hasChangesRef.current) {
        return;
      }

      if (user) {
        const nextForm = formFromUser(user);
        setForm(nextForm);
        setSavedSnapshot(nextForm);
      } else {
        setError('Could not load your profile.');
      }
    } finally {
      setIsLoading(false);
    }
  }, [refreshUser, user]);

  const hasChanges = useMemo(
    () => JSON.stringify(form) !== JSON.stringify(savedSnapshot),
    [form, savedSnapshot],
  );
  const hasChangesRef = useRef(hasChanges);

  useEffect(() => {
    hasChangesRef.current = hasChanges;
  }, [hasChanges]);

  useFocusEffect(
    useCallback(() => {
      if (!hasChangesRef.current) {
        void loadProfile();
      }
    }, [loadProfile]),
  );

  const previewPayload = useMemo(() => buildPayload(form), [form]);
  const preview = useMemo(
    () => (previewPayload ? calculateNutritionTarget(previewPayload) : null),
    [previewPayload],
  );

  function updateField<K extends keyof ProfileFormState>(key: K, value: ProfileFormState[K]) {
    hasChangesRef.current = true;
    setForm((current) => {
      const next = { ...current, [key]: value };

      if (key === 'goal_type' && value === 'maintain') {
        next.goal_pace = 'moderate';
      }

      return next;
    });
  }

  async function handleSave() {
    const payload = buildPayload(form);
    if (!payload) {
      setError('Please fill in all profile fields with valid values.');
      return;
    }

    setError(null);
    setFieldErrors({});
    setIsSubmitting(true);

    try {
      const updatedUser = await updateProfile(payload);
      const nextForm = formFromUser(updatedUser);
      setForm(nextForm);
      setSavedSnapshot(nextForm);
      hasChangesRef.current = false;
    } catch (err) {
      if (err instanceof ApiError) {
        setFieldErrors(err.errors);
        setError(err.message);
      } else {
        setError('Could not save your profile. Please try again.');
      }
    } finally {
      setIsSubmitting(false);
    }
  }

  async function handleSignOut() {
    setIsSigningOut(true);

    try {
      await signOut();
    } catch {
      Alert.alert('Sign out failed', 'Please try again.');
    } finally {
      setIsSigningOut(false);
    }
  }

  if (isLoading && !user) {
    return (
      <ThemedView style={styles.container}>
        <SafeAreaView style={styles.centeredState} edges={['top']}>
          <ActivityIndicator color={theme.accent} size="large" />
        </SafeAreaView>
      </ThemedView>
    );
  }

  return (
    <ThemedView style={styles.container}>
      <SafeAreaView style={styles.safeArea} edges={['top']}>
        <KeyboardAvoidingView
          behavior={Platform.OS === 'ios' ? 'padding' : undefined}
          style={styles.flex}>
          <ScrollView
            contentContainerStyle={styles.scrollContent}
            keyboardShouldPersistTaps="handled"
            showsVerticalScrollIndicator={false}>
            <View style={styles.header}>
              <ThemedText type="subtitle" style={styles.title}>
                Profile
              </ThemedText>
              <ThemedText themeColor="textSecondary" style={styles.subtitle}>
                {user ? `${user.first_name} ${user.last_name}` : 'Your fitness settings'}
              </ThemedText>
            </View>

            <TargetPreviewCard
              preview={preview}
              savedCalorieTarget={user?.nutrition_target?.calorie_target ?? null}
              hasChanges={hasChanges}
            />

            <ThemedView type="backgroundElement" style={styles.section}>
              <ThemedText type="smallBold" style={styles.sectionTitle}>
                About you
              </ThemedText>

              <OptionChipGroup
                label="Gender"
                value={form.gender}
                options={GENDER_OPTIONS}
                onChange={(value) => updateField('gender', value)}
              />

              <FieldGroup label="Birthdate" error={fieldErrors.birthdate?.[0]}>
                <BirthdatePicker
                  value={form.birthdate}
                  onChange={(value) => updateField('birthdate', value)}
                />
              </FieldGroup>

              <View style={styles.row}>
                <View style={styles.halfField}>
                  <FieldGroup label="Weight (kg)" error={fieldErrors.current_weight_kg?.[0]}>
                    <TextInput
                      inputMode="decimal"
                      keyboardType="decimal-pad"
                      placeholder="80"
                      placeholderTextColor={theme.textSecondary}
                      style={[
                        styles.input,
                        { color: theme.text, borderColor: theme.backgroundSelected },
                      ]}
                      value={form.current_weight_kg}
                      onChangeText={(value) =>
                        updateField('current_weight_kg', sanitizeDecimalInput(value))
                      }
                    />
                  </FieldGroup>
                </View>
                <View style={styles.halfField}>
                  <FieldGroup label="Height (cm)" error={fieldErrors.height_cm?.[0]}>
                    <TextInput
                      inputMode="numeric"
                      keyboardType="number-pad"
                      placeholder="180"
                      placeholderTextColor={theme.textSecondary}
                      style={[
                        styles.input,
                        { color: theme.text, borderColor: theme.backgroundSelected },
                      ]}
                      value={form.height_cm}
                      onChangeText={(value) =>
                        updateField('height_cm', sanitizeIntegerInput(value))
                      }
                    />
                  </FieldGroup>
                </View>
              </View>
            </ThemedView>

            <ThemedView type="backgroundElement" style={styles.section}>
              <ThemedText type="smallBold" style={styles.sectionTitle}>
                Goals
              </ThemedText>

              <OptionChipGroup
                label="Fitness goal"
                value={form.goal_type}
                options={GOAL_TYPE_OPTIONS}
                onChange={(value) => updateField('goal_type', value)}
              />

              {form.goal_type !== 'maintain' ? (
                <OptionChipGroup
                  label="Goal pace"
                  value={form.goal_pace}
                  options={GOAL_PACE_OPTIONS}
                  onChange={(value) => updateField('goal_pace', value)}
                />
              ) : null}

              <FieldGroup label="Target weight (kg)" error={fieldErrors.target_weight_kg?.[0]}>
                <TextInput
                  inputMode="decimal"
                  keyboardType="decimal-pad"
                  placeholder="75"
                  placeholderTextColor={theme.textSecondary}
                  style={[styles.input, { color: theme.text, borderColor: theme.backgroundSelected }]}
                  value={form.target_weight_kg}
                  onChangeText={(value) =>
                    updateField('target_weight_kg', sanitizeDecimalInput(value))
                  }
                />
              </FieldGroup>

              <OptionChipGroup
                label="Activity level"
                value={form.activity_level}
                options={ACTIVITY_LEVEL_OPTIONS}
                onChange={(value) => updateField('activity_level', value)}
              />
            </ThemedView>

            <DietaryPreferencesSection />

            <SavedMealPlansSection />

            {error ? (
              <ThemedText type="small" style={styles.errorText}>
                {error}
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
                  Save profile
                </ThemedText>
              )}
            </Pressable>

            <Pressable
              disabled={isSigningOut}
              onPress={handleSignOut}
              style={({ pressed }) => [
                styles.secondaryButton,
                { borderColor: theme.backgroundSelected },
                pressed && styles.pressed,
              ]}>
              {isSigningOut ? (
                <ActivityIndicator color={theme.textSecondary} />
              ) : (
                <ThemedText themeColor="textSecondary" type="smallBold">
                  Sign out
                </ThemedText>
              )}
            </Pressable>
          </ScrollView>
        </KeyboardAvoidingView>
      </SafeAreaView>
    </ThemedView>
  );
}

function FieldGroup({
  label,
  error,
  children,
}: {
  label: string;
  error?: string;
  children: ReactNode;
}) {
  return (
    <View style={styles.fieldGroup}>
      <ThemedText type="smallBold">{label}</ThemedText>
      {children}
      {error ? (
        <ThemedText type="small" style={styles.errorText}>
          {error}
        </ThemedText>
      ) : null}
    </View>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
  },
  flex: {
    flex: 1,
  },
  safeArea: {
    flex: 1,
  },
  scrollContent: {
    paddingHorizontal: Spacing.four,
    paddingTop: Spacing.three,
    paddingBottom: BottomTabInset + Spacing.five,
    gap: Spacing.four,
    maxWidth: MaxContentWidth,
    width: '100%',
    alignSelf: 'center',
  },
  centeredState: {
    flex: 1,
    alignItems: 'center',
    justifyContent: 'center',
  },
  header: {
    gap: Spacing.one,
  },
  title: {
    fontSize: 28,
    lineHeight: 36,
  },
  subtitle: {
    fontSize: 15,
  },
  section: {
    borderRadius: Spacing.four,
    padding: Spacing.four,
    gap: Spacing.three,
  },
  sectionTitle: {
    fontSize: 16,
  },
  fieldGroup: {
    gap: Spacing.two,
  },
  row: {
    flexDirection: 'row',
    gap: Spacing.three,
  },
  halfField: {
    flex: 1,
  },
  input: {
    borderWidth: 1,
    borderRadius: Spacing.two,
    paddingHorizontal: Spacing.three,
    paddingVertical: Spacing.two,
    fontSize: 16,
    minHeight: 44,
    ...(Platform.OS === 'web'
      ? {
          outlineWidth: 0,
        }
      : {}),
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
  secondaryButton: {
    alignItems: 'center',
    justifyContent: 'center',
    borderRadius: Spacing.three,
    paddingVertical: Spacing.three,
    borderWidth: 1,
  },
  errorText: {
    color: '#d64545',
  },
  disabledButton: {
    opacity: 0.55,
  },
  pressed: {
    opacity: 0.85,
  },
});
