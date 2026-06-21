import { StyleSheet, View } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';

import { SkeletonBox } from '@/components/ui/skeleton-box';
import { ThemedView } from '@/components/themed-view';
import { BottomTabInset, MaxContentWidth, Spacing } from '@/constants/theme';

function ChartCardSkeleton() {
  return (
    <ThemedView type="backgroundElement" style={styles.chartCard}>
      <SkeletonBox width="50%" height={20} borderRadius={Spacing.one} />
      <SkeletonBox width="75%" height={14} borderRadius={Spacing.one} />
      <SkeletonBox height={180} borderRadius={Spacing.three} />
    </ThemedView>
  );
}

export function AnalyticsSkeleton() {
  return (
    <ThemedView style={styles.container}>
      <SafeAreaView style={styles.safeArea} edges={['top']}>
        <View style={styles.scrollContent}>
          <View style={styles.header}>
            <SkeletonBox width="40%" height={32} borderRadius={Spacing.two} />
            <SkeletonBox width="65%" height={18} borderRadius={Spacing.two} />
          </View>
          <ChartCardSkeleton />
          <ChartCardSkeleton />
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
    gap: Spacing.one,
  },
  chartCard: {
    borderRadius: Spacing.four,
    padding: Spacing.four,
    gap: Spacing.three,
  },
});
