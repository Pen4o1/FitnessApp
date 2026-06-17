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
import { Spacing } from '@/constants/theme';
import { useTheme } from '@/hooks/use-theme';

const AnimatedCircle = Animated.createAnimatedComponent(Circle);

const SIZE = 220;
const STROKE_WIDTH = 14;
const RADIUS = (SIZE - STROKE_WIDTH) / 2;
const CIRCUMFERENCE = 2 * Math.PI * RADIUS;

type CalorieRingProps = {
  consumed: number;
  target: number;
  remaining: number;
};

export function CalorieRing({ consumed, target, remaining }: CalorieRingProps) {
  const theme = useTheme();
  const progress = useSharedValue(0);
  const ratio = target > 0 ? Math.min(consumed / target, 1) : 0;
  const isOverTarget = consumed > target;
  const progressColor = isOverTarget ? theme.warning : theme.accent;

  useEffect(() => {
    progress.value = withTiming(ratio, {
      duration: 800,
      easing: Easing.out(Easing.cubic),
    });
  }, [progress, ratio]);

  const animatedProps = useAnimatedProps(() => ({
    strokeDashoffset: CIRCUMFERENCE * (1 - progress.value),
  }));

  return (
    <View style={styles.container}>
      <Svg width={SIZE} height={SIZE} style={styles.svg}>
        <Circle
          cx={SIZE / 2}
          cy={SIZE / 2}
          r={RADIUS}
          stroke={theme.ringTrack}
          strokeWidth={STROKE_WIDTH}
          fill="none"
        />
        <G rotation="-90" origin={`${SIZE / 2}, ${SIZE / 2}`}>
          <AnimatedCircle
            cx={SIZE / 2}
            cy={SIZE / 2}
            r={RADIUS}
            stroke={progressColor}
            strokeWidth={STROKE_WIDTH}
            fill="none"
            strokeLinecap="round"
            strokeDasharray={CIRCUMFERENCE}
            animatedProps={animatedProps}
          />
        </G>
      </Svg>

      <View style={styles.centerContent}>
        <ThemedText themeColor="textSecondary" style={styles.label}>
          Consumed
        </ThemedText>
        <ThemedText style={styles.consumed}>{consumed}</ThemedText>
        <ThemedText themeColor="textSecondary" style={styles.meta}>
          Goal: {target} kcal
        </ThemedText>
        <ThemedText style={[styles.remaining, { color: isOverTarget ? theme.warning : theme.success }]}>
          Remaining: {remaining} kcal
        </ThemedText>
      </View>
    </View>
  );
}

const styles = StyleSheet.create({
  container: {
    alignItems: 'center',
    justifyContent: 'center',
    height: SIZE,
    width: SIZE,
    alignSelf: 'center',
  },
  svg: {
    position: 'absolute',
  },
  centerContent: {
    alignItems: 'center',
    gap: Spacing.half,
    paddingHorizontal: Spacing.three,
  },
  label: {
    fontSize: 13,
    fontWeight: '500',
  },
  consumed: {
    fontSize: 40,
    fontWeight: '700',
    lineHeight: 44,
  },
  meta: {
    fontSize: 13,
  },
  remaining: {
    fontSize: 14,
    fontWeight: '600',
    marginTop: Spacing.one,
  },
});
