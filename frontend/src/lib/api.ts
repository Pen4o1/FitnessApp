import { clearToken, getToken, setToken } from '@/lib/auth-storage';
import type { UserPreferences } from '@/types/dietary';
import type { WeeklyDaySummary } from '@/types/analytics';
import type { MealPlan, SavedMealPlan, SavedMealPlanSummary } from '@/types/meal-plan';
import type {
  DailySummary,
  FoodBarcodeScanResult,
  FoodLogItem,
  FoodSearchResult,
  MealType,
} from '@/types/nutrition';
import type {
  ActivityLevel,
  Gender,
  GoalPace,
  GoalType,
  UpdateProfilePayload,
  UserNutritionTarget,
} from '@/types/profile';

const API_URL = normalizeApiUrl(process.env.EXPO_PUBLIC_API_URL ?? 'http://localhost:8000');

function normalizeApiUrl(url: string): string {
  const trimmed = url.trim().replace(/\/+$/, '');

  if (/^https?:\/\//i.test(trimmed)) {
    return trimmed;
  }

  return `http://${trimmed}`;
}

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

export async function getUserPreferences(): Promise<UserPreferences> {
  return apiFetch<UserPreferences>('/api/user/preferences');
}

export async function updateUserPreferences(payload: UserPreferences): Promise<UserPreferences> {
  return apiFetch<UserPreferences>('/api/user/preferences', {
    method: 'PUT',
    body: JSON.stringify(payload),
  });
}

export type { UserPreferences };

export type { ActivityLevel, Gender, GoalPace, GoalType, UpdateProfilePayload, UserNutritionTarget };

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

export async function scanFoodByBarcode(barcode: string): Promise<FoodBarcodeScanResult> {
  const params = new URLSearchParams({ barcode: barcode.trim() });

  return apiFetch<FoodBarcodeScanResult>(`/api/food/scan?${params.toString()}`);
}

export type LogFoodPayload = {
  date: string;
  meal_type: MealType;
  quantity: number;
  serving_unit: string;
  serving_description: string;
  base_quantity: number;
  external_food_id: string;
  external_source: string;
  food_name: string;
  brand_name: string | null;
  calories_per_base: number;
  protein_g_per_base: number;
  carbs_g_per_base: number;
  fat_g_per_base: number;
};

export async function logFood(payload: LogFoodPayload): Promise<FoodLogItem> {
  return apiFetch<FoodLogItem>('/api/foods/log', {
    method: 'POST',
    body: JSON.stringify(payload),
  });
}

export async function generateMealPlan(options?: {
  includeSnack?: boolean;
  mealsCount?: number;
}): Promise<MealPlan> {
  const params = new URLSearchParams();

  if (options?.mealsCount !== undefined) {
    params.set('meals_count', String(options.mealsCount));
  } else if (options?.includeSnack === false) {
    params.set('include_snack', 'false');
  }

  const query = params.toString() ? `?${params.toString()}` : '';

  return apiFetch<MealPlan>(`/api/meal-planner/generate${query}`);
}

export async function saveMealPlan(date: string, plan: MealPlan): Promise<SavedMealPlan> {
  return apiFetch<SavedMealPlan>('/api/meal-planner/save', {
    method: 'POST',
    body: JSON.stringify({ date, plan }),
  });
}

export async function logMealPlanToDiary(date: string, plan: MealPlan): Promise<void> {
  await apiFetch<{ message: string }>('/api/meal-planner/log', {
    method: 'POST',
    body: JSON.stringify({ date, plan }),
  });
}

type SavedMealPlanListResponse = SavedMealPlanSummary[] | { data: SavedMealPlanSummary[] };

export async function getSavedMealPlans(limit = 20): Promise<SavedMealPlanSummary[]> {
  const response = await apiFetch<SavedMealPlanListResponse>(
    `/api/meal-plans?limit=${encodeURIComponent(String(limit))}`,
  );

  if (Array.isArray(response)) {
    return response;
  }

  return response.data ?? [];
}

export async function getSavedMealPlan(id: number): Promise<SavedMealPlan> {
  return apiFetch<SavedMealPlan>(`/api/meal-plans/${id}`);
}

export async function getDailySummary(date?: string): Promise<DailySummary> {
  const params = date ? `?date=${encodeURIComponent(date)}` : '';

  return apiFetch<DailySummary>(`/api/daily-summary${params}`);
}

type WeeklyAnalyticsResponse = {
  days: WeeklyDaySummary[];
};

export async function getWeeklyAnalytics(): Promise<WeeklyDaySummary[]> {
  const response = await apiFetch<WeeklyAnalyticsResponse>('/api/analytics/weekly');

  return response.days;
}

export type { WeeklyDaySummary };
