import { ChevronLeft, House, Pencil, Share, Star, Trash2, Users } from 'lucide-react';
import { useCallback, useEffect, useState } from 'react';
import { Link, useNavigate, useParams } from 'react-router-dom';
import { api, ApiError } from '../api/client';
import type { ListingDetailResponse } from '../api/types';
import { useAuth } from '../auth/AuthContext';
import { AmenityIcon } from '../components/AmenityIcon';
import { Avatar } from '../components/Avatar';
import { BookingCard } from '../components/BookingCard';
import { FollowButton } from '../components/FollowButton';
import { HostBookings } from '../components/HostBookings';
import { ReviewsSection } from '../components/ReviewsSection';
import { formatMonthYear, formatRating, plural } from '../utils/format';
import { listingPhoto } from '../utils/images';

export function ListingPage() {
  const { id } = useParams();
  const navigate = useNavigate();
  const { user } = useAuth();
  const [data, setData] = useState<ListingDetailResponse | null>(null);
  const [error, setError] = useState<string | null>(null);
  const [bookingsVersion, setBookingsVersion] = useState(0);

  const load = useCallback(() => {
    api<ListingDetailResponse>(`/listings/${id}`)
      .then(setData)
      .catch((exception) => setError(exception instanceof ApiError && exception.status === 404 ? 'Ce logement n’existe pas.' : 'Chargement impossible.'));
  }, [id]);

  useEffect(load, [load]);

  if (error) {
    return (
      <main className="container empty-state">
        <h2>{error}</h2>
        <Link to="/" className="button button--dark">Retour à l’accueil</Link>
      </main>
    );
  }

  if (!data) {
    return <main className="container detail"><div className="skeleton skeleton--title" /><div className="gallery skeleton" /></main>;
  }

  const { listing, averageRating, reviewCount, reviews } = data;
  const isHost = user?.id === listing.host.id;

  async function deleteListing() {
    if (!window.confirm('Supprimer définitivement ce logement ?')) {
      return;
    }
    try {
      await api(`/listings/${listing.id}`, { method: 'DELETE' });
      navigate('/');
    } catch (exception) {
      window.alert(exception instanceof ApiError ? exception.message : 'Suppression impossible.');
    }
  }

  return (
    <main className="container detail">
      <Link to="/" className="detail__back"><ChevronLeft size={18} /> Logements</Link>

      <div className="detail__heading">
        <h1>{listing.title}</h1>
        <div className="detail__heading-actions">
          {isHost && (
            <>
              <Link to={`/host/listings/${listing.id}/edit`} className="link-button"><Pencil size={14} /> Modifier</Link>
              <button type="button" className="link-button" onClick={deleteListing}><Trash2 size={14} /> Supprimer</button>
            </>
          )}
          <button type="button" className="link-button" onClick={() => navigator.clipboard?.writeText(window.location.href)}>
            <Share size={14} /> Partager
          </button>
        </div>
      </div>

      <div className="gallery">
        {[0, 1, 2, 3, 4].map((index) => (
          <img key={index} src={listingPhoto(listing, index, index === 0 ? 1200 : 600, index === 0 ? 900 : 450)} alt="" />
        ))}
      </div>

      <div className="detail__layout">
        <div className="detail__main">
          <section className="detail-section detail-section--first">
            <h2>Logement entier : {listing.city}, {listing.country}</h2>
            <p className="muted">{plural(listing.maxGuests, 'voyageur')} maximum</p>
            {averageRating !== null && (
              <p className="detail__rating">
                <Star size={14} fill="currentColor" strokeWidth={0} /> {formatRating(averageRating)} · <a href="#reviews">{plural(reviewCount, 'commentaire')}</a>
              </p>
            )}
          </section>

          <section className="detail-section host-row">
            <Avatar user={listing.host} size={48} />
            <div className="host-row__text">
              <strong>Hôte : {listing.host.firstName}</strong>
              <span className="muted">Membre depuis {formatMonthYear(listing.host.createdAt)}</span>
            </div>
            <FollowButton userId={listing.host.id} />
          </section>

          <section className="detail-section highlights">
            <div><House size={24} strokeWidth={1.5} /><p><strong>Logement entier</strong><span className="muted">Vous aurez le logement rien que pour vous.</span></p></div>
            <div><Users size={24} strokeWidth={1.5} /><p><strong>Jusqu’à {plural(listing.maxGuests, 'voyageur')}</strong><span className="muted">Idéal pour voyager à plusieurs.</span></p></div>
          </section>

          <section className="detail-section">
            <p className="detail__description">{listing.description}</p>
          </section>

          <section className="detail-section">
            <h2>Ce que propose ce logement</h2>
            {listing.amenities.length === 0 ? (
              <p className="muted">Aucun équipement renseigné.</p>
            ) : (
              <ul className="amenities-list">
                {listing.amenities.map((amenity) => (
                  <li key={amenity.id}><AmenityIcon slug={amenity.slug} /> {amenity.name}</li>
                ))}
              </ul>
            )}
          </section>
        </div>

        <BookingCard
          listingId={listing.id}
          hostId={listing.host.id}
          priceByNight={Number(listing.priceByNight)}
          maxGuests={listing.maxGuests}
          averageRating={averageRating}
          reviewCount={reviewCount}
          onBooked={() => setBookingsVersion((version) => version + 1)}
        />
      </div>

      {isHost && <HostBookings listingId={listing.id} refreshKey={bookingsVersion} />}

      <ReviewsSection listingId={listing.id} hostId={listing.host.id} averageRating={averageRating} reviews={reviews} onChange={load} />
    </main>
  );
}
