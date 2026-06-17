import { clearToken, getToken, setToken } from '@/lib/auth-storage';
import type { DailySummary, FoodLogItem, FoodSearchResult, MealType } from '@/types/nutrition';
import type {
  ActivityLevel,
  Gender,
  GoalType,
  UpdateProfilePayload,
  UserNutritionTarget,
} from '@/types/profile';

const API_URL = process.env.EXPO_PUBLIC_API_URL ?? 'http://localhost:8000';

export type User = {
  id: number;
  first_name: string;
  last_name: string;
  email: string;
  gender: Gender | null;
  birthdate: string | null;
  current_weight_kg: number | null;
  height_cm: number | null;
  profile_completed_at: string | null;
  email_verified_at: string | null;
  nutrition_target: UserNutritionTarget | null;
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

export async function updateProfile(payload: UpdateProfilePayload): Promise<User> {
  return apiFetch<User>('/api/profile', {
    method: 'PATCH',
    body: JSON.stringify(payload),
  });
}

export type { ActivityLevel, Gender, GoalType, UpdateProfilePayload, UserNutritionTarget };

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

export type LogFoodPayload = {
  date: string;
  meal_type: MealType;
  quantity: number;
  external_food_id: string;
  external_source: string;
  food_name: string;
  brand_name: string | null;
  calories_per_100g: number;
  protein_g_per_100g: number;
  carbs_g_per_100g: number;
  fat_g_per_100g: number;
};

export async function logFood(payload: LogFoodPayload): Promise<FoodLogItem> {
  return apiFetch<FoodLogItem>('/api/foods/log', {
    method: 'POST',
    body: JSON.stringify(payload),
  });
}

export async function getDailySummary(date?: string): Promise<DailySummary> {
  const params = date ? `?date=${encodeURIComponent(date)}` : '';

  return apiFetch<DailySummary>(`/api/daily-summary${params}`);
}
