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
  icon: { ios: any; android: any; web: any };
}[] = [
  { value: 'vegan', label: 'Vegan', description: 'No animal products', icon: { ios: 'leaf.fill', android: 'eco', web: 'eco' } },
  { value: 'vegetarian', label: 'Vegetarian', description: 'No meat or fish', icon: { ios: 'leaf', android: 'eco', web: 'eco' } },
  { value: 'keto', label: 'Keto', description: 'Low carb, high fat', icon: { ios: 'flame.fill', android: 'local_fire_department', web: 'local_fire_department' } },
  { value: 'paleo', label: 'Paleo', description: 'Whole foods, no grains', icon: { ios: 'hare.fill', android: 'pets', web: 'pets' } },
];

export const ALLERGY_OPTIONS: {
  value: AllergyRestriction;
  label: string;
  description: string;
  icon: { ios: any; android: any; web: any };
}[] = [
  { value: 'gluten_free', label: 'Gluten-Free', description: 'Avoid wheat and gluten', icon: { ios: 'allergens', android: 'grain', web: 'grain' } },
  { value: 'nut_free', label: 'Nut-Free', description: 'Avoid tree nuts and peanuts', icon: { ios: 'allergens', android: 'spa', web: 'spa' } },
  { value: 'dairy_free', label: 'Dairy-Free', description: 'Avoid milk and dairy', icon: { ios: 'drop.fill', android: 'water_drop', web: 'water_drop' } },
  { value: 'soy_free', label: 'Soy-Free', description: 'Avoid soy products', icon: { ios: 'allergens', android: 'eco', web: 'eco' } },
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
