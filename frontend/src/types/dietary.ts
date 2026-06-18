export type DietaryPreference = 'vegan' | 'vegetarian' | 'keto' | 'paleo';

export type AllergyRestriction = 'gluten_free' | 'nut_free' | 'dairy_free' | 'soy_free';

export type UserPreferences = {
  dietary_preferences: DietaryPreference[];
  allergies: AllergyRestriction[];
};

export const DIETARY_PREFERENCE_OPTIONS: {
  value: DietaryPreference;
  label: string;
  description: string;
}[] = [
  { value: 'vegan', label: 'Vegan', description: 'No animal products' },
  { value: 'vegetarian', label: 'Vegetarian', description: 'No meat or fish' },
  { value: 'keto', label: 'Keto', description: 'Low carb, high fat' },
  { value: 'paleo', label: 'Paleo', description: 'Whole foods, no grains' },
];

export const ALLERGY_OPTIONS: {
  value: AllergyRestriction;
  label: string;
  description: string;
}[] = [
  { value: 'gluten_free', label: 'Gluten-Free', description: 'Avoid wheat and gluten' },
  { value: 'nut_free', label: 'Nut-Free', description: 'Avoid tree nuts and peanuts' },
  { value: 'dairy_free', label: 'Dairy-Free', description: 'Avoid milk and dairy' },
  { value: 'soy_free', label: 'Soy-Free', description: 'Avoid soy products' },
];

export const EMPTY_USER_PREFERENCES: UserPreferences = {
  dietary_preferences: [],
  allergies: [],
};

const VALID_DIETARY_PREFERENCES = new Set(
  DIETARY_PREFERENCE_OPTIONS.map((option) => option.value),
);
const VALID_ALLERGIES = new Set(ALLERGY_OPTIONS.map((option) => option.value));

export function sanitizeUserPreferences(preferences: Partial<UserPreferences> | null | undefined): UserPreferences {
  return {
    dietary_preferences: (preferences?.dietary_preferences ?? []).filter((value): value is DietaryPreference =>
      VALID_DIETARY_PREFERENCES.has(value as DietaryPreference),
    ),
    allergies: (preferences?.allergies ?? []).filter((value): value is AllergyRestriction =>
      VALID_ALLERGIES.has(value as AllergyRestriction),
    ),
  };
}
