import { StyleSheet, View } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';

import { SkeletonBox } from '@/components/ui/skeleton-box';
import { ThemedView } from '@/components/themed-view';
import { BottomTabInset, MaxContentWidth, Spacing } from '@/constants/theme';

export function DashboardSkeleton() {
  return (
    <ThemedView style={styles.container}>
      <SafeAreaView style={styles.safeArea} edges={['top']}>
        <View style={styles.scrollContent}>
          <View style={styles.header}>
            <SkeletonBox width="55%" height={32} borderRadius={Spacing.two} />
            <SkeletonBox width="70%" height={18} borderRadius={Spacing.two} />
          </View>

          <ThemedView type="backgroundElement" style={styles.summaryCard}>
            <View style={styles.ringRow}>
              <SkeletonBox width={160} height={160} borderRadius={80} />
            </View>
            <View style={styles.macrosSection}>
              <SkeletonBox height={14} borderRadius={Spacing.one} />
              <SkeletonBox height={10} borderRadius={Spacing.one} />
              <SkeletonBox height={14} borderRadius={Spacing.one} />
              <SkeletonBox height={10} borderRadius={Spacing.one} />
              <SkeletonBox height={14} borderRadius={Spacing.one} />
              <SkeletonBox height={10} borderRadius={Spacing.one} />
            </View>
          </ThemedView>

          <View style={styles.mealsSection}>
            <SkeletonBox width="35%" height={16} borderRadius={Spacing.one} />
            {Array.from({ length: 4 }).map((_, index) => (
              <ThemedView key={index} type="backgroundElement" style={styles.mealCard}>
                <View style={styles.mealHeader}>
                  <SkeletonBox width="30%" height={16} borderRadius={Spacing.one} />
                  <SkeletonBox width={32} height={32} borderRadius={Spacing.two} />
                </View>
                <SkeletonBox width="60%" height={14} borderRadius={Spacing.one} />
              </ThemedView>
            ))}
          </View>
        </View>
      </SafeAreaView>
    </ThemedView>
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
  },
  safeArea: {
    flex: 1,
  },
  scrollContent: {
    paddingHorizontal: Spacing.four,
    paddingTop: Spacing.three,
    paddingBottom: BottomTabInset + Spacing.five,
    gap: Spacing.four,
    maxWidth: MaxContentWidth,
    width: '100%',
    alignSelf: 'center',
  },
  header: {
    gap: Spacing.two,
  },
  summaryCard: {
    borderRadius: Spacing.four,
    padding: Spacing.four,
    gap: Spacing.four,
  },
  ringRow: {
    alignItems: 'center',
  },
  macrosSection: {
    gap: Spacing.two,
  },
  mealsSection: {
    gap: Spacing.three,
  },
  mealCard: {
    borderRadius: Spacing.three,
    padding: Spacing.three,
    gap: Spacing.two,
  },
  mealHeader: {
    flexDirection: 'row',
    alignItems: 'center',
    justifyContent: 'space-between',
  },
});
