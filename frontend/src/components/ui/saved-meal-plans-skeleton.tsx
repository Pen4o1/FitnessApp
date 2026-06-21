import { StyleSheet, View } from 'react-native';

import { SkeletonBox } from '@/components/ui/skeleton-box';
import { Spacing } from '@/constants/theme';
import { useTheme } from '@/hooks/use-theme';

function SavedPlanCardSkeleton() {
  const theme = useTheme();

  return (
    <View style={[styles.card, { backgroundColor: theme.backgroundElement }]}>
      <SkeletonBox width="55%" height={18} borderRadius={Spacing.one} />
      <SkeletonBox width="30%" height={14} borderRadius={Spacing.one} />
      <View style={styles.macrosRow}>
        <SkeletonBox width={60} height={14} borderRadius={Spacing.one} />
        <SkeletonBox width={48} height={14} borderRadius={Spacing.one} />
        <SkeletonBox width={48} height={14} borderRadius={Spacing.one} />
      </View>
    </View>
  );
}

export function SavedMealPlansSkeleton({ cards = 3 }: { cards?: number }) {
  return (
    <View style={styles.list}>
      {Array.from({ length: cards }).map((_, index) => (
        <SavedPlanCardSkeleton key={index} />
      ))}
    </View>
  );
}

const styles = StyleSheet.create({
  list: {
    gap: Spacing.two,
  },
  card: {
    borderRadius: Spacing.three,
    padding: Spacing.three,
    gap: Spacing.two,
  },
  macrosRow: {
    flexDirection: 'row',
    gap: Spacing.two,
  },
});
