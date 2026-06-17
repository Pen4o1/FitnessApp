import { clearToken, getToken, setToken } from '@/lib/auth-storage';
import type { FoodSearchResult } from '@/types/nutrition';

const API_URL = process.env.EXPO_PUBLIC_API_URL ?? 'http://localhost:8000';

export type User = {
  id: number;
  first_name: string;
  last_name: string;
  email: string;
  profile_completed_at: string | null;
  email_verified_at: string | null;
};

export type AuthResponse = {
  token: string;
  user: User;
};

export class ApiError extends Error {
  status: number;
  errors: Record<string, string[]>;

  constructor(status: number, message: string, errors: Record<string, string[]> = {}) {
    super(message);
    this.name = 'ApiError';
    this.status = status;
    this.errors = errors;
  }
}

type ApiFetchOptions = RequestInit & {
  token?: string | null;
};

export async function apiFetch<T>(path: string, options: ApiFetchOptions = {}): Promise<T> {
  const { token, headers, ...rest } = options;
  const authToken = token !== undefined ? token : await getToken();

  const response = await fetch(`${API_URL}${path}`, {
    ...rest,
    headers: {
      Accept: 'application/json',
      'Content-Type': 'application/json',
      ...(authToken ? { Authorization: `Bearer ${authToken}` } : {}),
      ...headers,
    },
  });

  if (response.status === 204) {
    return undefined as T;
  }

  const data = await response.json().catch(() => ({}));

  if (!response.ok) {
    const errors = (data.errors as Record<string, string[]>) ?? {};
    const message = (data.message as string) ?? 'Request failed';
    throw new ApiError(response.status, message, errors);
  }

  return data as T;
}

export type RegisterPayload = {
  first_name: string;
  last_name: string;
  email: string;
  password: string;
  password_confirmation: string;
};

export async function register(payload: RegisterPayload): Promise<AuthResponse> {
  const response = await apiFetch<AuthResponse>('/api/register', {
    method: 'POST',
    body: JSON.stringify(payload),
    token: null,
  });
  await setToken(response.token);
  return response;
}

export async function login(email: string, password: string): Promise<AuthResponse> {
  const response = await apiFetch<AuthResponse>('/api/login', {
    method: 'POST',
    body: JSON.stringify({ email, password }),
    token: null,
  });
  await setToken(response.token);
  return response;
}

export async function logout(): Promise<void> {
  try {
    await apiFetch<void>('/api/logout', { method: 'POST' });
  } finally {
    await clearToken();
  }
}

export async function getUser(): Promise<User> {
  return apiFetch<User>('/api/user');
}

type FoodSearchResponse = {
  data: FoodSearchResult[];
};

export async function searchFoods(
  query: string,
  options: { page?: number; perPage?: number } = {},
): Promise<FoodSearchResult[]> {
  const params = new URLSearchParams({ q: query.trim() });

  if (options.page !== undefined) {
    params.set('page', String(options.page));
  }

  if (options.perPage !== undefined) {
    params.set('per_page', String(options.perPage));
  }

  const response = await apiFetch<FoodSearchResponse>(`/api/foods/search?${params.toString()}`);

  return response.data;
}
