import { useCallback, useEffect, useState } from 'react';
import { api, ApiError } from '../api/client';
import type { Booking, Page } from '../api/types';
import { formatPrice, plural } from '../utils/format';
import { Avatar } from './Avatar';
import { BookingStatusBadge, isCancellable } from './BookingStatusBadge';

const dayFormatter = new Intl.DateTimeFormat('fr-FR', { day: 'numeric', month: 'short', year: 'numeric' });

export function formatStay(booking: Booking): string {
  return `${dayFormatter.format(new Date(booking.arrivalDate))} → ${dayFormatter.format(new Date(booking.departureDate))}`;
}

/** Bookings of a listing, shown to its host, who confirms or cancels them. */
export function HostBookings({ listingId, refreshKey }: { listingId: number; refreshKey: number }) {
  const [bookings, setBookings] = useState<Booking[]>([]);
  const [error, setError] = useState<string | null>(null);

  const load = useCallback(() => {
    api<Page<Booking>>(`/listings/${listingId}/bookings?limit=50`)
      .then((page) => setBookings(page.items))
      .catch(() => setBookings([]));
  }, [listingId]);

  useEffect(load, [load, refreshKey]);

  async function act(booking: Booking, action: 'confirm' | 'cancel') {
    if (action === 'cancel' && !window.confirm('Annuler cette réservation ?')) {
      return;
    }
    setError(null);
    try {
      await api(`/bookings/${booking.id}/${action}`, { method: 'POST' });
      load();
    } catch (exception) {
      setError(exception instanceof ApiError ? exception.message : 'Action impossible.');
    }
  }

  return (
    <section className="detail-section">
      <h2>Réservations de votre logement</h2>
      {error && <p className="form-error">{error}</p>}
      {bookings.length === 0 ? (
        <p className="muted">Aucune réservation pour le moment.</p>
      ) : (
        <ul className="booking-list">
          {bookings.map((booking) => (
            <li key={booking.id} className="booking-row">
              <Avatar user={booking.traveler} size={40} />
              <div className="booking-row__info">
                <strong>{booking.traveler.firstName}</strong>
                <span className="muted">
                  {formatStay(booking)} · {plural(booking.guests, 'voyageur')} · {formatPrice(booking.totalPrice)}
                </span>
              </div>
              <BookingStatusBadge status={booking.status} />
              <div className="booking-row__actions">
                {booking.status === 'pending' && (
                  <button type="button" className="button button--dark button--small" onClick={() => act(booking, 'confirm')}>Confirmer</button>
                )}
                {isCancellable(booking) && (
                  <button type="button" className="button button--outline button--small" onClick={() => act(booking, 'cancel')}>Annuler</button>
                )}
              </div>
            </li>
          ))}
        </ul>
      )}
    </section>
  );
}
