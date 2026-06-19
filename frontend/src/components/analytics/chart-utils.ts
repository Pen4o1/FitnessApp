import type { WeeklyDaySummary } from '@/types/analytics';

export const CHART_HEIGHT = 200;
export const CHART_PADDING = { top: 16, right: 12, bottom: 28, left: 40 };

export function formatDayLabel(dateString: string): string {
  const date = new Date(`${dateString}T12:00:00`);

  return new Intl.DateTimeFormat('en-US', { weekday: 'short' }).format(date);
}

export function hasCalorieData(days: WeeklyDaySummary[]): boolean {
  return days.some((day) => day.calories > 0);
}

export function hasWeightData(days: WeeklyDaySummary[]): boolean {
  return days.some((day) => day.weight !== null);
}

export function buildWeightSegments(
  days: WeeklyDaySummary[],
): { x: number; y: number; weight: number; date: string }[][] {
  const points = days
    .map((day, index) =>
      day.weight !== null
        ? { index, weight: day.weight, date: day.date }
        : null,
    )
    .filter((point): point is { index: number; weight: number; date: string } => point !== null);

  if (points.length === 0) {
    return [];
  }

  const segments: { x: number; y: number; weight: number; date: string }[][] = [];
  let currentSegment: { x: number; y: number; weight: number; date: string }[] = [];

  for (const point of points) {
    const chartPoint = { x: point.index, y: point.weight, weight: point.weight, date: point.date };

    if (currentSegment.length === 0) {
      currentSegment.push(chartPoint);
      continue;
    }

    const lastIndex = currentSegment[currentSegment.length - 1]?.x ?? point.index;

    if (point.index - lastIndex === 1) {
      currentSegment.push(chartPoint);
    } else {
      segments.push(currentSegment);
      currentSegment = [chartPoint];
    }
  }

  if (currentSegment.length > 0) {
    segments.push(currentSegment);
  }

  return segments;
}

export function segmentToPath(
  segment: { x: number; y: number }[],
  xScale: (index: number) => number,
  yScale: (value: number) => number,
): string {
  return segment
    .map((point, index) => {
      const command = index === 0 ? 'M' : 'L';
      return `${command}${xScale(point.x)},${yScale(point.y)}`;
    })
    .join(' ');
}
