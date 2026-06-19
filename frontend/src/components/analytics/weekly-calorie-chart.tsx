import { useEffect, useMemo, useState } from 'react';
import { LayoutChangeEvent, StyleSheet, View } from 'react-native';
import Animated, {
  Easing,
  useAnimatedProps,
  useSharedValue,
  withDelay,
  withTiming,
} from 'react-native-reanimated';
import Svg, { Line, Rect, Text as SvgText } from 'react-native-svg';

import {
  CHART_HEIGHT,
  CHART_PADDING,
  formatDayLabel,
} from '@/components/analytics/chart-utils';
import { ThemedText } from '@/components/themed-text';
import { Spacing } from '@/constants/theme';
import { useTheme } from '@/hooks/use-theme';
import type { WeeklyDaySummary } from '@/types/analytics';

const AnimatedRect = Animated.createAnimatedComponent(Rect);
const GRID_LINES = 4;

type WeeklyCalorieChartProps = {
  days: WeeklyDaySummary[];
  calorieGoal: number;
};

type AnimatedBarProps = {
  x: number;
  width: number;
  targetHeight: number;
  baselineY: number;
  color: string;
  delay: number;
};

function AnimatedBar({ x, width, targetHeight, baselineY, color, delay }: AnimatedBarProps) {
  const height = useSharedValue(0);

  useEffect(() => {
    height.value = withDelay(
      delay,
      withTiming(targetHeight, {
        duration: 700,
        easing: Easing.out(Easing.cubic),
      }),
    );
  }, [delay, height, targetHeight]);

  const animatedProps = useAnimatedProps(() => ({
    height: Math.max(height.value, 0),
    y: baselineY - Math.max(height.value, 0),
  }));

  return (
    <AnimatedRect
      x={x}
      width={width}
      fill={color}
      rx={6}
      ry={6}
      animatedProps={animatedProps}
    />
  );
}

export function WeeklyCalorieChart({ days, calorieGoal }: WeeklyCalorieChartProps) {
  const theme = useTheme();
  const [chartWidth, setChartWidth] = useState(0);

  const maxCalories = useMemo(() => {
    const peak = Math.max(...days.map((day) => day.calories), calorieGoal);

    return peak > 0 ? peak * 1.15 : calorieGoal * 1.15;
  }, [calorieGoal, days]);

  const innerWidth = chartWidth - CHART_PADDING.left - CHART_PADDING.right;
  const innerHeight = CHART_HEIGHT - CHART_PADDING.top - CHART_PADDING.bottom;
  const barGap = 8;
  const barWidth = days.length > 0 ? (innerWidth - barGap * (days.length - 1)) / days.length : 0;
  const baselineY = CHART_PADDING.top + innerHeight;

  function handleLayout(event: LayoutChangeEvent) {
    setChartWidth(event.nativeEvent.layout.width);
  }

  function yScale(value: number): number {
    if (maxCalories <= 0) {
      return baselineY;
    }

    return CHART_PADDING.top + innerHeight - (value / maxCalories) * innerHeight;
  }

  const goalY = yScale(calorieGoal);
  const gridValues = Array.from({ length: GRID_LINES + 1 }, (_, index) => (maxCalories / GRID_LINES) * index);

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
              {Math.round(value)}
            </SvgText>
          ))}

          <Line
            x1={CHART_PADDING.left}
            y1={goalY}
            x2={chartWidth - CHART_PADDING.right}
            y2={goalY}
            stroke={theme.textSecondary}
            strokeWidth={1.5}
            strokeDasharray="6,5"
            opacity={0.85}
          />

          {days.map((day, index) => {
            const barHeight = maxCalories > 0 ? (day.calories / maxCalories) * innerHeight : 0;
            const x = CHART_PADDING.left + index * (barWidth + barGap);
            const barColor = day.calories > calorieGoal ? theme.warning : theme.success;

            return (
              <AnimatedBar
                key={day.date}
                x={x}
                width={barWidth}
                targetHeight={barHeight}
                baselineY={baselineY}
                color={barColor}
                delay={index * 60}
              />
            );
          })}

          {days.map((day, index) => {
            const x = CHART_PADDING.left + index * (barWidth + barGap) + barWidth / 2;

            return (
              <SvgText
                key={`day-${day.date}`}
                x={x}
                y={CHART_HEIGHT - 8}
                fill={theme.textSecondary}
                fontSize={11}
                textAnchor="middle">
                {formatDayLabel(day.date)}
              </SvgText>
            );
          })}
        </Svg>
      ) : null}

      <View style={styles.legendRow}>
        <View style={styles.legendItem}>
          <View style={[styles.legendSwatch, { backgroundColor: theme.success }]} />
          <ThemedText themeColor="textSecondary" style={styles.legendText}>
            Intake
          </ThemedText>
        </View>
        <View style={styles.legendItem}>
          <View style={[styles.legendDash, { borderColor: theme.textSecondary }]} />
          <ThemedText themeColor="textSecondary" style={styles.legendText}>
            Goal ({calorieGoal} kcal)
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
    borderRadius: 3,
  },
  legendDash: {
    width: 16,
    borderTopWidth: 2,
    borderStyle: 'dashed',
  },
  legendText: {
    fontSize: 12,
  },
});
