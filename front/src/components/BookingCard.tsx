import { Star } from 'lucide-react';
import { useState } from 'react';
import { Link } from 'react-router-dom';
import { api, ApiError } from '../api/client';
import type { Booking, BookingInput } from '../api/types';
import { useAuth } from '../auth/AuthContext';
import { formatPrice, formatRating, nightsBetween, plural } from '../utils/format';

interface BookingCardProps {
  listingId: number;
  hostId: number;
  priceByNight: number;
  maxGuests: number;
  averageRating: number | null;
  reviewCount: number;

  onBooked?: () => void;
}

export function BookingCard({ listingId, hostId, priceByNight, maxGuests, averageRating, reviewCount, onBooked }: BookingCardProps) {
  const { user, openLogin } = useAuth();
  const [arrival, setArrival] = useState('');
  const [departure, setDeparture] = useState('');
  const [guests, setGuests] = useState(1);
  const [error, setError] = useState<ApiError | null>(null);
  const [booking, setBooking] = useState<Booking | null>(null);
  const [isSubmitting, setSubmitting] = useState(false);

  const nights = nightsBetween(arrival, departure);
  const total = nights * priceByNight;
  const today = new Date().toISOString().slice(0, 10);
  const isHost = user?.id === hostId;

  async function book() {
    if (!user) {
      openLogin();

      return;
    }
    setSubmitting(true);
    setError(null);
    const body: BookingInput = { arrivalDate: arrival, departureDate: departure, guests };
    try {
      setBooking(await api<Booking>(`/listings/${listingId}/bookings`, { method: 'POST', body }));
      onBooked?.();
    } catch (exception) {
      setError(exception instanceof ApiError ? exception : new ApiError(0, 'Réservation impossible.'));
    } finally {
      setSubmitting(false);
    }
  }

  if (booking) {
    return (
      <aside className="booking-card booking-card--done">
        <h3>Demande de réservation envoyée</h3>
        <p>
          {plural(booking.nights, 'nuit')} · {plural(booking.guests, 'voyageur')} · <strong>{formatPrice(booking.totalPrice)}</strong>
        </p>
        <p className="muted">L’hôte doit maintenant confirmer votre séjour.</p>
        <Link to="/trips" className="button button--dark button--block">Voir mes voyages</Link>
      </aside>
    );
  }

  const fieldError = error?.fieldError('arrivalDate') ?? error?.fieldError('departureDate') ?? error?.fieldError('guests');

  return (
    <aside className="booking-card">
      <div className="booking-card__header">
        <p>
          <strong>{formatPrice(priceByNight)}</strong> par nuit
        </p>
        {averageRating !== null && (
          <a href="#reviews" className="booking-card__rating">
            <Star size={12} fill="currentColor" strokeWidth={0} /> {formatRating(averageRating)} · {plural(reviewCount, 'commentaire')}
          </a>
        )}
      </div>

      <div className="booking-card__inputs">
        <label>
          <span>Arrivée</span>
          <input type="date" value={arrival} min={today} onChange={(event) => setArrival(event.target.value)} />
        </label>
        <label>
          <span>Départ</span>
          <input type="date" value={departure} min={arrival || today} onChange={(event) => setDeparture(event.target.value)} />
        </label>
        <label className="booking-card__guests">
          <span>Voyageurs</span>
          <select value={guests} onChange={(event) => setGuests(Number(event.target.value))}>
            {Array.from({ length: maxGuests }, (_, index) => index + 1).map((count) => (
              <option key={count} value={count}>{plural(count, 'voyageur')}</option>
            ))}
          </select>
        </label>
      </div>

      {isHost ? (
        <p className="booking-card__note">C’est votre logement : vous ne pouvez pas le réserver.</p>
      ) : (
        <>
          <button type="button" className="button button--primary button--block" onClick={book} disabled={isSubmitting || nights === 0}>
            {isSubmitting ? 'Réservation…' : nights === 0 ? 'Choisissez vos dates' : 'Réserver'}
          </button>
          {error ? (
            <p className="form-error booking-card__error">{fieldError ?? error.message}</p>
          ) : (
            <p className="booking-card__note">Aucun montant ne vous sera débité pour le moment</p>
          )}
        </>
      )}

      {nights > 0 && (
        <div className="booking-card__total">
          <p>
            <span>{formatPrice(priceByNight)} x {plural(nights, 'nuit')}</span>
            <span>{formatPrice(total)}</span>
          </p>
          <hr />
          <p className="booking-card__sum">
            <span>Total</span>
            <span>{formatPrice(total)}</span>
          </p>
        </div>
      )}
    </aside>
  );
}
