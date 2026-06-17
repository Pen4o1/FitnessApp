import { Link, useRouter } from 'expo-router';
import { useState } from 'react';
import {
  ActivityIndicator,
  KeyboardAvoidingView,
  Platform,
  Pressable,
  ScrollView,
  StyleSheet,
  TextInput,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';

import { ThemedText } from '@/components/themed-text';
import { ThemedView } from '@/components/themed-view';
import { MaxContentWidth, Spacing } from '@/constants/theme';
import { useAuth } from '@/contexts/auth-context';
import { useTheme } from '@/hooks/use-theme';
import { ApiError } from '@/lib/api';

export default function RegisterScreen() {
  const router = useRouter();
  const theme = useTheme();
  const { signUp } = useAuth();
  const [firstName, setFirstName] = useState('');
  const [lastName, setLastName] = useState('');
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [passwordConfirmation, setPasswordConfirmation] = useState('');
  const [error, setError] = useState<string | null>(null);
  const [fieldErrors, setFieldErrors] = useState<Record<string, string[]>>({});
  const [isSubmitting, setIsSubmitting] = useState(false);

  async function handleSubmit() {
    setError(null);
    setFieldErrors({});
    setIsSubmitting(true);

    try {
      await signUp({
        first_name: firstName.trim(),
        last_name: lastName.trim(),
        email: email.trim(),
        password,
        password_confirmation: passwordConfirmation,
      });
      router.replace('/(app)/(tabs)');
    } catch (err) {
      if (err instanceof ApiError) {
        setFieldErrors(err.errors);
        setError(err.message);
      } else {
        setError('Something went wrong. Please try again.');
      }
    } finally {
      setIsSubmitting(false);
    }
  }

  return (
    <ThemedView style={styles.container}>
      <SafeAreaView style={styles.safeArea}>
        <ScrollView contentContainerStyle={styles.scrollContent} keyboardShouldPersistTaps="handled">
          <KeyboardAvoidingView
            behavior={Platform.OS === 'ios' ? 'padding' : undefined}
            style={styles.form}>
            <ThemedText type="subtitle" style={styles.title}>
              Create account
            </ThemedText>
            <ThemedText themeColor="textSecondary" style={styles.subtitle}>
              Start tracking your fitness journey
            </ThemedText>

            <ThemedView type="backgroundElement" style={styles.fieldGroup}>
              <ThemedText type="smallBold">First name</ThemedText>
              <TextInput
                autoCapitalize="words"
                autoComplete="given-name"
                placeholder="Jane"
                placeholderTextColor={theme.textSecondary}
                style={[styles.input, { color: theme.text, borderColor: theme.backgroundSelected }]}
                value={firstName}
                onChangeText={setFirstName}
              />
              {fieldErrors.first_name?.map((message) => (
                <ThemedText key={message} type="small" style={styles.errorText}>
                  {message}
                </ThemedText>
              ))}
            </ThemedView>

            <ThemedView type="backgroundElement" style={styles.fieldGroup}>
              <ThemedText type="smallBold">Last name</ThemedText>
              <TextInput
                autoCapitalize="words"
                autoComplete="family-name"
                placeholder="Doe"
                placeholderTextColor={theme.textSecondary}
                style={[styles.input, { color: theme.text, borderColor: theme.backgroundSelected }]}
                value={lastName}
                onChangeText={setLastName}
              />
              {fieldErrors.last_name?.map((message) => (
                <ThemedText key={message} type="small" style={styles.errorText}>
                  {message}
                </ThemedText>
              ))}
            </ThemedView>

            <ThemedView type="backgroundElement" style={styles.fieldGroup}>
              <ThemedText type="smallBold">Email</ThemedText>
              <TextInput
                autoCapitalize="none"
                autoComplete="email"
                keyboardType="email-address"
                placeholder="you@example.com"
                placeholderTextColor={theme.textSecondary}
                style={[styles.input, { color: theme.text, borderColor: theme.backgroundSelected }]}
                value={email}
                onChangeText={setEmail}
              />
              {fieldErrors.email?.map((message) => (
                <ThemedText key={message} type="small" style={styles.errorText}>
                  {message}
                </ThemedText>
              ))}
            </ThemedView>

            <ThemedView type="backgroundElement" style={styles.fieldGroup}>
              <ThemedText type="smallBold">Password</ThemedText>
              <TextInput
                autoCapitalize="none"
                autoComplete="new-password"
                secureTextEntry
                placeholder="At least 8 characters"
                placeholderTextColor={theme.textSecondary}
                style={[styles.input, { color: theme.text, borderColor: theme.backgroundSelected }]}
                value={password}
                onChangeText={setPassword}
              />
              {fieldErrors.password?.map((message) => (
                <ThemedText key={message} type="small" style={styles.errorText}>
                  {message}
                </ThemedText>
              ))}
            </ThemedView>

            <ThemedView type="backgroundElement" style={styles.fieldGroup}>
              <ThemedText type="smallBold">Confirm password</ThemedText>
              <TextInput
                autoCapitalize="none"
                autoComplete="new-password"
                secureTextEntry
                placeholder="Repeat your password"
                placeholderTextColor={theme.textSecondary}
                style={[styles.input, { color: theme.text, borderColor: theme.backgroundSelected }]}
                value={passwordConfirmation}
                onChangeText={setPasswordConfirmation}
              />
            </ThemedView>

            {error ? (
              <ThemedText type="small" style={styles.errorText}>
                {error}
              </ThemedText>
            ) : null}

            <Pressable
              disabled={isSubmitting}
              onPress={handleSubmit}
              style={({ pressed }) => [
                styles.button,
                { backgroundColor: theme.backgroundSelected },
                pressed && styles.pressed,
                isSubmitting && styles.disabled,
              ]}>
              {isSubmitting ? (
                <ActivityIndicator />
              ) : (
                <ThemedText type="smallBold">Create account</ThemedText>
              )}
            </Pressable>

            <ThemedText style={styles.footer}>
              Already have an account?{' '}
              <Link href="/(auth)/login">
                <ThemedText type="linkPrimary">Sign in</ThemedText>
              </Link>
            </ThemedText>
          </KeyboardAvoidingView>
        </ScrollView>
      </SafeAreaView>
    </ThemedView>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
  },
  safeArea: {
    flex: 1,
  },
  scrollContent: {
    flexGrow: 1,
    alignItems: 'center',
    justifyContent: 'center',
    paddingHorizontal: Spacing.four,
    paddingVertical: Spacing.five,
  },
  form: {
    width: '100%',
    maxWidth: MaxContentWidth,
    gap: Spacing.three,
  },
  title: {
    textAlign: 'center',
  },
  subtitle: {
    textAlign: 'center',
    marginBottom: Spacing.two,
  },
  fieldGroup: {
    gap: Spacing.two,
    padding: Spacing.three,
    borderRadius: Spacing.three,
  },
  input: {
    borderWidth: 1,
    borderRadius: Spacing.two,
    paddingHorizontal: Spacing.three,
    paddingVertical: Spacing.two,
    fontSize: 16,
  },
  button: {
    alignItems: 'center',
    justifyContent: 'center',
    borderRadius: Spacing.three,
    paddingVertical: Spacing.three,
    marginTop: Spacing.two,
  },
  footer: {
    textAlign: 'center',
    marginTop: Spacing.two,
  },
  errorText: {
    color: '#d64545',
  },
  pressed: {
    opacity: 0.8,
  },
  disabled: {
    opacity: 0.6,
  },
});
