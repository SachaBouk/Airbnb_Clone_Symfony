import { Pencil, Star, Trash2 } from 'lucide-react';
import { useState, type FormEvent } from 'react';
import { api, ApiError } from '../api/client';
import type { Review, ReviewInput } from '../api/types';
import { useAuth } from '../auth/AuthContext';
import { formatMonthYear, formatRating, plural } from '../utils/format';
import { Avatar } from './Avatar';

interface ReviewsSectionProps {
  listingId: number;
  hostId: number;
  averageRating: number | null;
  reviews: Review[];
  onChange: () => void;
}

export function ReviewsSection({ listingId, hostId, averageRating, reviews, onChange }: ReviewsSectionProps) {
  const { user, openLogin } = useAuth();
  const myReview = user ? reviews.find((review) => review.author.id === user.id) : undefined;
  const [isEditing, setEditing] = useState(false);

  return (
    <section className="detail-section" id="reviews">
      <h2 className="reviews__title">
        <Star size={18} fill="currentColor" strokeWidth={0} />
        {averageRating !== null ? `${formatRating(averageRating)} · ${plural(reviews.length, 'commentaire')}` : 'Aucun commentaire pour le moment'}
      </h2>

      <div className="reviews__grid">
        {reviews.map((review) => (
          <article key={review.id} className="review">
            <header className="review__author">
              <Avatar user={review.author} size={44} />
              <div>
                <strong>{review.author.firstName}</strong>
                <span className="muted">Membre depuis {formatMonthYear(review.author.createdAt)}</span>
              </div>
            </header>
            <p className="review__meta">
              <Stars rating={review.rating} /> · <span>{formatMonthYear(review.createdAt)}</span>
            </p>
            <p className="review__comment">{review.comment}</p>
            {review.id === myReview?.id && !isEditing && (
              <div className="review__actions">
                <button type="button" className="link-button" onClick={() => setEditing(true)}>
                  <Pencil size={14} /> Modifier
                </button>
                <DeleteReviewButton reviewId={review.id} onDeleted={onChange} />
              </div>
            )}
          </article>
        ))}
      </div>

      {!user && (
        <p className="reviews__hint">
          Vous avez séjourné ici ?{' '}
          <button type="button" className="link-button" onClick={openLogin}>Connectez-vous</button> pour laisser un avis.
        </p>
      )}

      {user && user.id !== hostId && (!myReview || isEditing) && (
        <ReviewForm
          listingId={listingId}
          existing={isEditing ? myReview : undefined}
          onDone={() => {
            setEditing(false);
            onChange();
          }}
          onCancel={isEditing ? () => setEditing(false) : undefined}
        />
      )}
    </section>
  );
}

function Stars({ rating }: { rating: number }) {
  return (
    <span className="stars" aria-label={`${rating} étoiles sur 5`}>
      {[1, 2, 3, 4, 5].map((value) => (
        <Star key={value} size={10} strokeWidth={0} fill={value <= rating ? 'currentColor' : '#dddddd'} />
      ))}
    </span>
  );
}

function DeleteReviewButton({ reviewId, onDeleted }: { reviewId: number; onDeleted: () => void }) {
  async function handleDelete() {
    if (window.confirm('Supprimer votre avis ?')) {
      await api(`/reviews/${reviewId}`, { method: 'DELETE' });
      onDeleted();
    }
  }

  return (
    <button type="button" className="link-button" onClick={handleDelete}>
      <Trash2 size={14} /> Supprimer
    </button>
  );
}

interface ReviewFormProps {
  listingId: number;
  existing?: Review;
  onDone: () => void;
  onCancel?: () => void;
}

function ReviewForm({ listingId, existing, onDone, onCancel }: ReviewFormProps) {
  const [rating, setRating] = useState(existing?.rating ?? 0);
  const [comment, setComment] = useState(existing?.comment ?? '');
  const [error, setError] = useState<ApiError | null>(null);
  const [isSubmitting, setSubmitting] = useState(false);

  async function handleSubmit(event: FormEvent) {
    event.preventDefault();
    setSubmitting(true);
    setError(null);
    const body: ReviewInput = { rating, comment };
    try {
      if (existing) {
        await api(`/reviews/${existing.id}`, { method: 'PUT', body });
      } else {
        await api(`/listings/${listingId}/reviews`, { method: 'POST', body });
      }
      onDone();
    } catch (exception) {
      setError(exception instanceof ApiError ? exception : new ApiError(0, 'Envoi impossible.'));
    } finally {
      setSubmitting(false);
    }
  }

  return (
    <form className="review-form" onSubmit={handleSubmit}>
      <h3>{existing ? 'Modifier votre avis' : 'Laisser un avis'}</h3>
      <div className="star-picker" role="radiogroup" aria-label="Note">
        {[1, 2, 3, 4, 5].map((value) => (
          <button
            key={value}
            type="button"
            role="radio"
            aria-checked={rating === value}
            aria-label={`${value} étoile${value > 1 ? 's' : ''}`}
            onClick={() => setRating(value)}
          >
            <Star size={28} strokeWidth={1.5} fill={value <= rating ? 'currentColor' : 'none'} />
          </button>
        ))}
      </div>
      {error?.fieldError('rating') && <p className="form-error">Choisissez une note de 1 à 5.</p>}
      <label className="field field--single">
        <span>Votre commentaire</span>
        <textarea rows={4} value={comment} onChange={(event) => setComment(event.target.value)} maxLength={2000} />
      </label>
      {error && (error.fieldError('comment') ? <p className="form-error">Le commentaire est obligatoire.</p> : !error.violations.length && <p className="form-error">{error.message}</p>)}
      <div className="review-form__actions">
        {onCancel && <button type="button" className="link-button" onClick={onCancel}>Annuler</button>}
        <button type="submit" className="button button--dark" disabled={isSubmitting}>
          {isSubmitting ? 'Envoi…' : 'Publier'}
        </button>
      </div>
    </form>
  );
}
