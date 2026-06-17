const ISO_DATE_PATTERN = /^\d{4}-\d{2}-\d{2}$/;

export function formatBirthdate(date: Date): string {
  const year = date.getFullYear();
  const month = String(date.getMonth() + 1).padStart(2, '0');
  const day = String(date.getDate()).padStart(2, '0');

  return `${year}-${month}-${day}`;
}

export function birthdateToDate(value: string): Date | null {
  if (!ISO_DATE_PATTERN.test(value)) {
    return null;
  }

  const parsed = new Date(`${value}T12:00:00`);

  return Number.isNaN(parsed.getTime()) ? null : parsed;
}

export function getBirthdateLimits(now = new Date()) {
  const maximumDate = new Date(now);
  maximumDate.setFullYear(maximumDate.getFullYear() - 13);

  const minimumDate = new Date(now);
  minimumDate.setFullYear(minimumDate.getFullYear() - 100);

  return { minimumDate, maximumDate };
}
