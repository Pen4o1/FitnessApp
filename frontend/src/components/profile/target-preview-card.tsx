import { useEffect } from 'react';
import { StyleSheet, View } from 'react-native';
import Animated, {
  Easing,
  useAnimatedProps,
  useSharedValue,
  withTiming,
} from 'react-native-reanimated';
import Svg, { Circle, G } from 'react-native-svg';

import { ThemedText } from '@/components/themed-text';
import { ThemedView } from '@/components/themed-view';
import { Spacing } from '@/constants/theme';
import { useTheme } from '@/hooks/use-theme';
import type { CalculatedNutritionTarget } from '@/types/profile';

const AnimatedCircle = Animated.createAnimatedComponent(Circle);

const RING_SIZE = 120;
const STROKE_WIDTH = 10;
const RADIUS = (RING_SIZE - STROKE_WIDTH) / 2;
const CIRCUMFERENCE = 2 * Math.PI * RADIUS;

type TargetPreviewCardProps = {
  preview: CalculatedNutritionTarget | null;
  savedCalorieTarget: number | null;
  hasChanges: boolean;
};

export function TargetPreviewCard({
  preview,
  savedCalorieTarget,
  hasChanges,
}: TargetPreviewCardProps) {
  const theme = useTheme();
  const progress = useSharedValue(0);
  const calorieTarget = preview?.calorie_target ?? 0;
  const maxReference = Math.max(calorieTarget, savedCalorieTarget ?? 0, 1);
  const ratio = calorieTarget / maxReference;

  useEffect(() => {
    progress.value = withTiming(ratio, {
      duration: 450,
      easing: Easing.out(Easing.cubic),
    });
  }, [progress, ratio]);

  const animatedProps = useAnimatedProps(() => ({
    strokeDashoffset: CIRCUMFERENCE * (1 - progress.value),
  }));

  const delta =
    preview && savedCalorieTarget !== null ? preview.calorie_target - savedCalorieTarget : null;

  return (
    <ThemedView type="backgroundElement" style={styles.card}>
      <View style={styles.headerRow}>
        <View style={styles.headerCopy}>
          <ThemedText type="smallBold">Daily calorie target</ThemedText>
          <ThemedText themeColor="textSecondary" type="small">
            {hasChanges ? 'Updates instantly as you edit' : 'Your current goal'}
          </ThemedText>
        </View>
        {delta !== null && delta !== 0 ? (
          <ThemedView
            style={[
              styles.deltaBadge,
              { backgroundColor: delta > 0 ? theme.success : theme.warning },
            ]}>
            <ThemedText type="smallBold" style={styles.deltaText}>
              {delta > 0 ? '+' : ''}
              {delta} kcal
            </ThemedText>
          </ThemedView>
        ) : null}
      </View>

      <View style={styles.previewRow}>
        <View style={styles.ringWrap}>
          <Svg width={RING_SIZE} height={RING_SIZE}>
            <Circle
              cx={RING_SIZE / 2}
              cy={RING_SIZE / 2}
              r={RADIUS}
              stroke={theme.ringTrack}
              strokeWidth={STROKE_WIDTH}
              fill="none"
            />
            <G rotation="-90" origin={`${RING_SIZE / 2}, ${RING_SIZE / 2}`}>
              <AnimatedCircle
                cx={RING_SIZE / 2}
                cy={RING_SIZE / 2}
                r={RADIUS}
                stroke={theme.accent}
                strokeWidth={STROKE_WIDTH}
                fill="none"
                strokeLinecap="round"
                strokeDasharray={CIRCUMFERENCE}
                animatedProps={animatedProps}
              />
            </G>
          </Svg>
          <View style={styles.ringCenter}>
            <ThemedText style={styles.calorieValue}>
              {preview ? preview.calorie_target : '—'}
            </ThemedText>
            <ThemedText themeColor="textSecondary" type="small">
              kcal
            </ThemedText>
          </View>
        </View>

        <View style={styles.statsColumn}>
          <StatRow label="BMR" value={preview?.bmr} suffix="kcal" />
          <StatRow label="TDEE" value={preview?.tdee} suffix="kcal" />
          {preview?.estimated_days_to_goal ? (
            <StatRow label="Est. timeline" value={preview.estimated_days_to_goal} suffix="days" />
          ) : null}
        </View>
      </View>

      {preview ? (
        <View style={styles.macrosRow}>
          <MacroPill label="Protein" value={preview.protein_target_g} color={theme.protein} />
          <MacroPill label="Carbs" value={preview.carbs_target_g} color={theme.carbs} />
          <MacroPill label="Fat" value={preview.fat_target_g} color={theme.fat} />
        </View>
      ) : (
        <ThemedText themeColor="textSecondary" type="small" style={styles.placeholder}>
          Enter your details below to preview your personalized targets.
        </ThemedText>
      )}
    </ThemedView>
  );
}

function StatRow({
  label,
  value,
  suffix,
}: {
  label: string;
  value: number | null | undefined;
  suffix: string;
}) {
  return (
    <View style={styles.statRow}>
      <ThemedText themeColor="textSecondary" type="small">
        {label}
      </ThemedText>
      <ThemedText type="smallBold">
        {value ?? '—'} {value !== null && value !== undefined ? suffix : ''}
      </ThemedText>
    </View>
  );
}

function MacroPill({ label, value, color }: { label: string; value: number; color: string }) {
  return (
    <View style={[styles.macroPill, { borderColor: color }]}>
      <ThemedText type="small" themeColor="textSecondary">
        {label}
      </ThemedText>
      <ThemedText type="smallBold" style={{ color }}>
        {value}g
      </ThemedText>
    </View>
  );
}

const styles = StyleSheet.create({
  card: {
    borderRadius: Spacing.four,
    padding: Spacing.four,
    gap: Spacing.three,
  },
  headerRow: {
    flexDirection: 'row',
    alignItems: 'flex-start',
    justifyContent: 'space-between',
    gap: Spacing.two,
  },
  headerCopy: {
    flex: 1,
    gap: Spacing.half,
  },
  deltaBadge: {
    borderRadius: Spacing.two,
    paddingHorizontal: Spacing.two,
    paddingVertical: Spacing.one,
  },
  deltaText: {
    color: '#FFFFFF',
  },
  previewRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: Spacing.four,
  },
  ringWrap: {
    width: RING_SIZE,
    height: RING_SIZE,
    alignItems: 'center',
    justifyContent: 'center',
  },
  ringCenter: {
    position: 'absolute',
    alignItems: 'center',
  },
  calorieValue: {
    fontSize: 28,
    fontWeight: '700',
    lineHeight: 32,
  },
  statsColumn: {
    flex: 1,
    gap: Spacing.two,
  },
  statRow: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    gap: Spacing.two,
  },
  macrosRow: {
    flexDirection: 'row',
    gap: Spacing.two,
  },
  macroPill: {
    flex: 1,
    borderWidth: 1,
    borderRadius: Spacing.two,
    paddingVertical: Spacing.two,
    paddingHorizontal: Spacing.two,
    alignItems: 'center',
    gap: Spacing.half,
  },
  placeholder: {
    lineHeight: 20,
  },
});
