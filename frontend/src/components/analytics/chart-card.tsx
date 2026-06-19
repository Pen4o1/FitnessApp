import { ReactNode } from 'react';
import { StyleSheet, View } from 'react-native';

import { ThemedText } from '@/components/themed-text';
import { ThemedView } from '@/components/themed-view';
import { Spacing } from '@/constants/theme';

type ChartCardProps = {
  title: string;
  subtitle?: string;
  legend?: ReactNode;
  emptyMessage?: string;
  isEmpty?: boolean;
  children: ReactNode;
};

export function ChartCard({
  title,
  subtitle,
  legend,
  emptyMessage,
  isEmpty = false,
  children,
}: ChartCardProps) {
  return (
    <ThemedView type="backgroundElement" style={styles.card}>
      <View style={styles.header}>
        <View style={styles.titleGroup}>
          <ThemedText type="smallBold" style={styles.title}>
            {title}
          </ThemedText>
          {subtitle ? (
            <ThemedText themeColor="textSecondary" style={styles.subtitle}>
              {subtitle}
            </ThemedText>
          ) : null}
        </View>
        {legend}
      </View>

      {children}

      {isEmpty && emptyMessage ? (
        <ThemedText themeColor="textSecondary" style={styles.emptyMessage}>
          {emptyMessage}
        </ThemedText>
      ) : null}
    </ThemedView>
  );
}

const styles = StyleSheet.create({
  card: {
    borderRadius: Spacing.four,
    padding: Spacing.three,
    gap: Spacing.two,
  },
  header: {
    flexDirection: 'row',
    alignItems: 'flex-start',
    justifyContent: 'space-between',
    gap: Spacing.two,
  },
  titleGroup: {
    flex: 1,
    gap: Spacing.half,
  },
  title: {
    fontSize: 16,
  },
  subtitle: {
    fontSize: 13,
    lineHeight: 18,
  },
  emptyMessage: {
    fontSize: 13,
    lineHeight: 20,
    fontStyle: 'italic',
    textAlign: 'center',
    paddingTop: Spacing.one,
  },
});
