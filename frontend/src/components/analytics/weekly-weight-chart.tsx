import { useEffect, useMemo, useState } from 'react';
import { LayoutChangeEvent, StyleSheet, View } from 'react-native';
import Animated, {
  Easing,
  useAnimatedProps,
  useSharedValue,
  withDelay,
  withTiming,
} from 'react-native-reanimated';
import Svg, { Circle, Line, Path, Text as SvgText } from 'react-native-svg';

import {
  buildWeightSegments,
  CHART_HEIGHT,
  CHART_PADDING,
  formatDayLabel,
  segmentToPath,
} from '@/components/analytics/chart-utils';
import { ThemedText } from '@/components/themed-text';
import { Spacing } from '@/constants/theme';
import { useTheme } from '@/hooks/use-theme';
import type { WeeklyDaySummary } from '@/types/analytics';

const AnimatedPath = Animated.createAnimatedComponent(Path);
const AnimatedCircle = Animated.createAnimatedComponent(Circle);
const GRID_LINES = 4;

type WeeklyWeightChartProps = {
  days: WeeklyDaySummary[];
};

type AnimatedLineSegmentProps = {
  path: string;
  color: string;
  pathLength: number;
  delay: number;
};

function AnimatedLineSegment({ path, color, pathLength, delay }: AnimatedLineSegmentProps) {
  const progress = useSharedValue(pathLength);

  useEffect(() => {
    progress.value = pathLength;
    progress.value = withDelay(
      delay,
      withTiming(0, {
        duration: 900,
        easing: Easing.out(Easing.cubic),
      }),
    );
  }, [delay, path, pathLength, progress]);

  const animatedProps = useAnimatedProps(() => ({
    strokeDashoffset: progress.value,
  }));

  return (
    <AnimatedPath
      d={path}
      stroke={color}
      strokeWidth={3}
      fill="none"
      strokeLinecap="round"
      strokeLinejoin="round"
      strokeDasharray={pathLength}
      animatedProps={animatedProps}
    />
  );
}

type AnimatedDotProps = {
  cx: number;
  cy: number;
  color: string;
  delay: number;
};

function AnimatedDot({ cx, cy, color, delay }: AnimatedDotProps) {
  const radius = useSharedValue(0);

  useEffect(() => {
    radius.value = withDelay(
      delay,
      withTiming(5, {
        duration: 500,
        easing: Easing.out(Easing.cubic),
      }),
    );
  }, [cy, delay, radius]);

  const animatedProps = useAnimatedProps(() => ({
    r: radius.value,
  }));

  return (
    <AnimatedCircle
      cx={cx}
      cy={cy}
      fill={color}
      stroke={color}
      strokeWidth={2}
      animatedProps={animatedProps}
    />
  );
}

export function WeeklyWeightChart({ days }: WeeklyWeightChartProps) {
  const theme = useTheme();
  const [chartWidth, setChartWidth] = useState(0);

  const weightValues = useMemo(
    () => days.map((day) => day.weight).filter((weight): weight is number => weight !== null),
    [days],
  );

  const minWeight = weightValues.length > 0 ? Math.min(...weightValues) : 0;
  const maxWeight = weightValues.length > 0 ? Math.max(...weightValues) : 0;
  const weightPadding = weightValues.length > 0 ? Math.max((maxWeight - minWeight) * 0.2, 0.5) : 1;
  const domainMin = weightValues.length > 0 ? minWeight - weightPadding : 0;
  const domainMax = weightValues.length > 0 ? maxWeight + weightPadding : 1;

  const innerWidth = chartWidth - CHART_PADDING.left - CHART_PADDING.right;
  const innerHeight = CHART_HEIGHT - CHART_PADDING.top - CHART_PADDING.bottom;
  const baselineY = CHART_PADDING.top + innerHeight;

  const segments = useMemo(() => buildWeightSegments(days), [days]);

  function handleLayout(event: LayoutChangeEvent) {
    setChartWidth(event.nativeEvent.layout.width);
  }

  function xScale(index: number): number {
    if (days.length <= 1) {
      return CHART_PADDING.left + innerWidth / 2;
    }

    return CHART_PADDING.left + (index / (days.length - 1)) * innerWidth;
  }

  function yScale(value: number): number {
    const range = domainMax - domainMin;

    if (range <= 0) {
      return CHART_PADDING.top + innerHeight / 2;
    }

    return CHART_PADDING.top + innerHeight - ((value - domainMin) / range) * innerHeight;
  }

  const gridValues = Array.from({ length: GRID_LINES + 1 }, (_, index) => {
    const range = domainMax - domainMin;

    return domainMin + (range / GRID_LINES) * index;
  });

  const weightPoints = days
    .map((day, index) =>
      day.weight !== null ? { index, weight: day.weight, date: day.date } : null,
    )
    .filter((point): point is { index: number; weight: number; date: string } => point !== null);

  return (
    <View style={styles.container} onLayout={handleLayout}>
      {chartWidth > 0 ? (
        <Svg width={chartWidth} height={CHART_HEIGHT}>
          {gridValues.map((value) => {
            const y = yScale(value);

            return (
              <Line
                key={`grid-${value}`}
                x1={CHART_PADDING.left}
                y1={y}
                x2={chartWidth - CHART_PADDING.right}
                y2={y}
                stroke={theme.ringTrack}
                strokeWidth={1}
              />
            );
          })}

          {gridValues.map((value) => (
            <SvgText
              key={`label-${value}`}
              x={CHART_PADDING.left - 8}
              y={yScale(value) + 4}
              fill={theme.textSecondary}
              fontSize={10}
              textAnchor="end">
              {value.toFixed(1)}
            </SvgText>
          ))}

          {segments.map((segment, segmentIndex) => {
            const path = segmentToPath(segment, xScale, yScale);
            const approximateLength = segment.reduce((total, point, index) => {
              if (index === 0) {
                return total;
              }

              const previous = segment[index - 1];
              const dx = xScale(point.x) - xScale(previous.x);
              const dy = yScale(point.y) - yScale(previous.y);

              return total + Math.hypot(dx, dy);
            }, 0);

            return (
              <AnimatedLineSegment
                key={`segment-${segmentIndex}`}
                path={path}
                color={theme.accent}
                pathLength={Math.max(approximateLength, 1)}
                delay={segmentIndex * 120}
              />
            );
          })}

          {weightPoints.map((point, index) => (
            <AnimatedDot
              key={point.date}
              cx={xScale(point.index)}
              cy={yScale(point.weight)}
              color={theme.accent}
              delay={index * 80}
            />
          ))}

          {days.map((day, index) => (
            <SvgText
              key={`day-${day.date}`}
              x={xScale(index)}
              y={CHART_HEIGHT - 8}
              fill={theme.textSecondary}
              fontSize={11}
              textAnchor="middle">
              {formatDayLabel(day.date)}
            </SvgText>
          ))}

          <Line
            x1={CHART_PADDING.left}
            y1={baselineY}
            x2={chartWidth - CHART_PADDING.right}
            y2={baselineY}
            stroke={theme.backgroundSelected}
            strokeWidth={1}
          />
        </Svg>
      ) : null}

      <View style={styles.legendRow}>
        <View style={styles.legendItem}>
          <View style={[styles.legendSwatch, { backgroundColor: theme.accent }]} />
          <ThemedText themeColor="textSecondary" style={styles.legendText}>
            Body weight (kg)
          </ThemedText>
        </View>
      </View>
    </View>
  );
}

const styles = StyleSheet.create({
  container: {
    width: '100%',
  },
  legendRow: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    gap: Spacing.three,
    paddingTop: Spacing.one,
    paddingHorizontal: Spacing.one,
  },
  legendItem: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: Spacing.one,
  },
  legendSwatch: {
    width: 10,
    height: 10,
    borderRadius: 5,
  },
  legendText: {
    fontSize: 12,
  },
});
