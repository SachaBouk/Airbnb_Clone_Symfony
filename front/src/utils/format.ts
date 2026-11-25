const priceFormatter = new Intl.NumberFormat('fr-FR', { style: 'currency', currency: 'EUR', maximumFractionDigits: 0 });
const ratingFormatter = new Intl.NumberFormat('fr-FR', { minimumFractionDigits: 1, maximumFractionDigits: 2 });
const monthYearFormatter = new Intl.DateTimeFormat('fr-FR', { month: 'long', year: 'numeric' });

export function formatPrice(amount: number | string): string {
  return priceFormatter.format(Number(amount));
}

export function formatRating(rating: number): string {
  return ratingFormatter.format(rating);
}

export function formatMonthYear(date: string | null | undefined): string {
  return date ? monthYearFormatter.format(new Date(date)) : '';
}

export function plural(count: number, singular: string, pluralForm = `${singular}s`): string {
  return `${count} ${count > 1 ? pluralForm : singular}`;
}

export function nightsBetween(from: string, to: string): number {
  if (!from || !to) {
    return 0;
  }
  const nights = Math.round((Date.parse(to) - Date.parse(from)) / 86_400_000);

  return Math.max(0, nights);
}
