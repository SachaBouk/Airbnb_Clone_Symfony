import { Heart, Star } from 'lucide-react';
import type { MouseEvent } from 'react';
import { Link } from 'react-router-dom';
import type { ListingSummary } from '../api/types';
import { formatPrice, formatRating, plural } from '../utils/format';
import { listingCover } from '../utils/images';

interface ListingCardProps {
  listing: ListingSummary;
  isFavorite: boolean;
  onToggleFavorite: (listingId: number) => void;
}

const GUEST_FAVORITE_RATING = 4.5;

export function ListingCard({ listing, isFavorite, onToggleFavorite }: ListingCardProps) {
  function toggleFavorite(event: MouseEvent) {
    event.preventDefault();
    onToggleFavorite(listing.id);
  }

  const isGuestFavorite = listing.averageRating !== null && listing.averageRating >= GUEST_FAVORITE_RATING;

  return (
    <Link to={`/listings/${listing.id}`} className="listing-card">
      <div className="listing-card__photo">
        <img src={listingCover(listing)} alt={listing.title} loading="lazy" />
        {isGuestFavorite && <span className="listing-card__badge">Coup de cœur voyageurs</span>}
        <button
          type="button"
          className={`listing-card__heart ${isFavorite ? 'listing-card__heart--on' : ''}`}
          onClick={toggleFavorite}
          aria-label={isFavorite ? 'Retirer des favoris' : 'Ajouter aux favoris'}
        >
          <Heart size={24} strokeWidth={2} />
        </button>
      </div>

      <div className="listing-card__body">
        <div className="listing-card__line">
          <span className="listing-card__title">{listing.city}, {listing.country}</span>
          {listing.averageRating !== null ? (
            <span className="listing-card__rating">
              <Star size={12} fill="currentColor" strokeWidth={0} /> {formatRating(listing.averageRating)} ({listing.reviewCount})
            </span>
          ) : (
            <span className="listing-card__rating">Nouveau</span>
          )}
        </div>
        <p className="listing-card__muted">{listing.title}</p>
        <p className="listing-card__muted">
          {plural(listing.maxGuests, 'voyageur')} · Hôte : {listing.host.firstName}
        </p>
        {listing.myRating != null && <p className="listing-card__mine">Vous avez noté {listing.myRating}/5</p>}
        <p className="listing-card__price">
          <strong>{formatPrice(listing.priceByNight)}</strong> par nuit
        </p>
      </div>
    </Link>
  );
}

export function ListingCardSkeleton() {
  return (
    <div className="listing-card listing-card--skeleton" aria-hidden="true">
      <div className="listing-card__photo skeleton" />
      <div className="skeleton skeleton--line" />
      <div className="skeleton skeleton--line skeleton--short" />
    </div>
  );
}
