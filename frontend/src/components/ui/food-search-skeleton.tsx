import { StyleSheet, View } from 'react-native';

import { SkeletonBox } from '@/components/ui/skeleton-box';
import { ThemedView } from '@/components/themed-view';
import { Spacing } from '@/constants/theme';

function FoodSearchRowSkeleton() {
  return (
    <ThemedView type="backgroundElement" style={styles.row}>
      <SkeletonBox width="70%" height={16} borderRadius={Spacing.one} />
      <SkeletonBox width="45%" height={14} borderRadius={Spacing.one} />
      <SkeletonBox width="35%" height={14} borderRadius={Spacing.one} />
    </ThemedView>
  );
}

export function FoodSearchSkeleton({ rows = 8 }: { rows?: number }) {
  return (
    <View style={styles.list}>
      {Array.from({ length: rows }).map((_, index) => (
        <FoodSearchRowSkeleton key={index} />
      ))}
    </View>
  );
}

const styles = StyleSheet.create({
  list: {
    gap: Spacing.two,
    paddingTop: Spacing.two,
  },
  row: {
    borderRadius: Spacing.three,
    padding: Spacing.three,
    gap: Spacing.two,
  },
});
