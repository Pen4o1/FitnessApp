function parseDateString(date: string): Date {
  return new Date(`${date}T12:00:00`);
}

function formatDateString(date: Date): string {
  const year = date.getFullYear();
  const month = String(date.getMonth() + 1).padStart(2, '0');
  const day = String(date.getDate()).padStart(2, '0');

  return `${year}-${month}-${day}`;
}

export function todayDateString(): string {
  return formatDateString(new Date());
}

export function addDays(date: string, delta: number): string {
  const next = parseDateString(date);
  next.setDate(next.getDate() + delta);

  return formatDateString(next);
}

export function isToday(date: string): boolean {
  return date === todayDateString();
}

export function isFutureDate(date: string): boolean {
  return date > todayDateString();
}
