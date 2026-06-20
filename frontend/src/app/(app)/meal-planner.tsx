import { useLocalSearchParams } from 'expo-router';

import { MealPlannerScreen } from '@/components/meal-planner/meal-planner-screen';
import { todayDateString } from '@/lib/date';

export default function MealPlannerRoute() {
  const { date: rawDate } = useLocalSearchParams<{ date?: string }>();
  const date = typeof rawDate === 'string' && rawDate.length > 0 ? rawDate : todayDateString();

  return <MealPlannerScreen date={date} />;
}
