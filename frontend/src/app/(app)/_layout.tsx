import { Stack } from 'expo-router';

export default function AppLayout() {
  return (
    <Stack>
      <Stack.Screen name="(tabs)" options={{ headerShown: false }} />
      <Stack.Screen
        name="food-search"
        options={{
          title: 'Food search',
          presentation: 'modal',
        }}
      />
      <Stack.Screen
        name="analytics"
        options={{
          title: 'Analytics',
          headerBackTitle: 'Back',
        }}
      />
    </Stack>
  );
}
