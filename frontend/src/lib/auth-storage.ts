import { Platform } from 'react-native';

const TOKEN_KEY = 'auth_token';

async function getSecureStore() {
  if (Platform.OS === 'web') {
    return null;
  }

  return import('expo-secure-store');
}

export async function getToken(): Promise<string | null> {
  if (Platform.OS === 'web') {
    return localStorage.getItem(TOKEN_KEY);
  }

  const SecureStore = await getSecureStore();
  return SecureStore?.getItemAsync(TOKEN_KEY) ?? null;
}

export async function setToken(token: string): Promise<void> {
  if (Platform.OS === 'web') {
    localStorage.setItem(TOKEN_KEY, token);
    return;
  }

  const SecureStore = await getSecureStore();
  await SecureStore?.setItemAsync(TOKEN_KEY, token);
}

export async function clearToken(): Promise<void> {
  if (Platform.OS === 'web') {
    localStorage.removeItem(TOKEN_KEY);
    return;
  }

  const SecureStore = await getSecureStore();
  await SecureStore?.deleteItemAsync(TOKEN_KEY);
}
