import { useCallback, useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { api, ApiError } from '../api/client';
import type { Booking, Page } from '../api/types';
import { useAuth } from '../auth/AuthContext';
import { BookingStatusBadge, isCancellable } from '../components/BookingStatusBadge';
import { formatStay } from '../components/HostBookings';
import { formatPrice, plural } from '../utils/format';
import { listingCover } from '../utils/images';

export function TripsPage() {
  const { user, openLogin } = useAuth();
  const [bookings, setBookings] = useState<Booking[] | null>(null);
  const [error, setError] = useState<string | null>(null);

  const load = useCallback(() => {
    if (user) {
      api<Page<Booking>>('/bookings?limit=50').then((page) => setBookings(page.items));
    }
  }, [user]);

  useEffect(load, [load]);

  if (!user) {
    return (
      <main className="container empty-state">
        <h2>Connectez-vous pour voir vos voyages</h2>
        <button type="button" className="button button--primary" onClick={openLogin}>Connexion</button>
      </main>
    );
  }

  async function cancel(booking: Booking) {
    if (!window.confirm('Annuler cette réservation ?')) {
      return;
    }
    setError(null);
    try {
      await api(`/bookings/${booking.id}/cancel`, { method: 'POST' });
      load();
    } catch (exception) {
      setError(exception instanceof ApiError ? exception.message : 'Annulation impossible.');
    }
  }

  return (
    <main className="container narrow">
      <h1 className="page-title">Voyages</h1>
      {error && <p className="form-error">{error}</p>}

      {bookings?.length === 0 && (
        <div className="empty-state">
          <h2>Aucun voyage réservé… pour le moment !</h2>
          <p>Il est temps de dépoussiérer vos bagages et de commencer à préparer votre prochaine aventure.</p>
          <Link to="/" className="button button--dark">Commencer les recherches</Link>
        </div>
      )}

      <div className="trips">
        {bookings?.map((booking) => (
          <article key={booking.id} className="trip">
            <Link to={`/listings/${booking.listing.id}`} className="trip__photo">
              <img src={listingCover(booking.listing, 320, 240)} alt="" />
            </Link>
            <div className="trip__body">
              <BookingStatusBadge status={booking.status} />
              <Link to={`/listings/${booking.listing.id}`} className="trip__title">{booking.listing.title}</Link>
              <span className="muted">{booking.listing.city}, {booking.listing.country}</span>
              <span>{formatStay(booking)}</span>
              <span className="muted">
                {plural(booking.nights, 'nuit')} · {plural(booking.guests, 'voyageur')} · <strong>{formatPrice(booking.totalPrice)}</strong>
              </span>
            </div>
            {isCancellable(booking) && (
              <button type="button" className="button button--outline button--small trip__cancel" onClick={() => cancel(booking)}>
                Annuler
              </button>
            )}
          </article>
        ))}
      </div>
    </main>
  );
}
