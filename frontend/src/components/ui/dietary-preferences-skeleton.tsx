import { StyleSheet, View } from 'react-native';

import { SkeletonBox } from '@/components/ui/skeleton-box';
import { ThemedView } from '@/components/themed-view';
import { Spacing } from '@/constants/theme';

function PreferenceCardSkeleton() {
  return (
    <ThemedView type="backgroundSelected" style={styles.card}>
      <SkeletonBox width="45%" height={16} borderRadius={Spacing.one} />
      <SkeletonBox width="80%" height={14} borderRadius={Spacing.one} />
    </ThemedView>
  );
}

export function DietaryPreferencesSkeleton() {
  return (
    <ThemedView type="backgroundElement" style={styles.section}>
      <SkeletonBox width="50%" height={18} borderRadius={Spacing.one} />
      <SkeletonBox width="85%" height={14} borderRadius={Spacing.one} />
      <View style={styles.cardList}>
        {Array.from({ length: 4 }).map((_, index) => (
          <PreferenceCardSkeleton key={index} />
        ))}
      </View>
    </ThemedView>
  );
}

const styles = StyleSheet.create({
  section: {
    borderRadius: Spacing.four,
    padding: Spacing.four,
    gap: Spacing.three,
  },
  cardList: {
    gap: Spacing.two,
  },
  card: {
    borderRadius: Spacing.three,
    padding: Spacing.three,
    gap: Spacing.two,
  },
});
