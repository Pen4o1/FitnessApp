import { StyleSheet, View } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';

import { SkeletonBox } from '@/components/ui/skeleton-box';
import { ThemedView } from '@/components/themed-view';
import { BottomTabInset, MaxContentWidth, Spacing } from '@/constants/theme';

function FormSectionSkeleton() {
  return (
    <ThemedView type="backgroundElement" style={styles.section}>
      <SkeletonBox width="40%" height={18} borderRadius={Spacing.one} />
      <SkeletonBox height={48} borderRadius={Spacing.three} />
      <SkeletonBox height={48} borderRadius={Spacing.three} />
    </ThemedView>
  );
}

export function ProfileSkeleton() {
  return (
    <ThemedView style={styles.container}>
      <SafeAreaView style={styles.safeArea} edges={['top']}>
        <View style={styles.scrollContent}>
          <SkeletonBox width="35%" height={32} borderRadius={Spacing.two} />
          <ThemedView type="backgroundElement" style={styles.targetCard}>
            <SkeletonBox width="50%" height={18} borderRadius={Spacing.one} />
            <View style={styles.targetRow}>
              <SkeletonBox width="22%" height={48} borderRadius={Spacing.two} />
              <SkeletonBox width="22%" height={48} borderRadius={Spacing.two} />
              <SkeletonBox width="22%" height={48} borderRadius={Spacing.two} />
              <SkeletonBox width="22%" height={48} borderRadius={Spacing.two} />
            </View>
          </ThemedView>
          <FormSectionSkeleton />
          <FormSectionSkeleton />
          <FormSectionSkeleton />
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
  targetCard: {
    borderRadius: Spacing.four,
    padding: Spacing.four,
    gap: Spacing.three,
  },
  targetRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
  },
  section: {
    borderRadius: Spacing.four,
    padding: Spacing.four,
    gap: Spacing.three,
  },
});
