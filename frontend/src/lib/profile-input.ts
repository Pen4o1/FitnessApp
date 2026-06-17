export function sanitizeDecimalInput(value: string): string {
  const normalized = value.replace(',', '.');
  const cleaned = normalized.replace(/[^\d.]/g, '');
  const [whole, ...fractionalParts] = cleaned.split('.');

  if (fractionalParts.length === 0) {
    return whole;
  }

  return `${whole}.${fractionalParts.join('')}`;
}

export function sanitizeIntegerInput(value: string): string {
  return value.replace(/[^\d]/g, '');
}
