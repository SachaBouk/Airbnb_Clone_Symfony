import type { BookingStatus } from '../api/types';

const LABELS: Record<BookingStatus, string> = {
  pending: 'En attente',
  confirmed: 'Confirmée',
  cancelled: 'Annulée',
};

export function BookingStatusBadge({ status }: { status: BookingStatus }) {
  return <span className={`status-badge status-badge--${status}`}>{LABELS[status]}</span>;
}

/** True when a booking can still be cancelled: not cancelled and the stay has not begun. */
export function isCancellable(booking: { status: BookingStatus; arrivalDate: string }): boolean {
  return booking.status !== 'cancelled' && booking.arrivalDate > new Date().toISOString().slice(0, 10);
}
