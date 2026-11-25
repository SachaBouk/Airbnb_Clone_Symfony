import { Star } from 'lucide-react';
import { useEffect, useState } from 'react';
import { Link } from 'react-router-dom';
import { api } from '../api/client';
import type { Page, Review } from '../api/types';
import { useAuth } from '../auth/AuthContext';
import { Avatar } from '../components/Avatar';
import { formatMonthYear } from '../utils/format';
import { listingCover } from '../utils/images';

type Tab = 'following' | 'all';

const PAGE_SIZE = 10;

/** Reviews feed: from the people I follow (GET /api/feed) or from everyone (GET /api/reviews). */
export function FeedPage() {
  const { user, openLogin } = useAuth();
  const [tab, setTab] = useState<Tab>(user ? 'following' : 'all');
  const [reviews, setReviews] = useState<Review[]>([]);
  const [page, setPage] = useState(1);
  const [lastPage, setLastPage] = useState(1);
  const [isLoading, setLoading] = useState(true);

  useEffect(() => {
    if (!user && tab === 'following') {
      setTab('all');
    }
  }, [user, tab]);

  useEffect(() => {
    setPage(1);
  }, [tab, user]);

  useEffect(() => {
    if (tab === 'following' && !user) {
      return;
    }
    let isCurrent = true;
    setLoading(true);
    api<Page<Review>>(`${tab === 'following' ? '/feed' : '/reviews'}?page=${page}&limit=${PAGE_SIZE}`)
      .then((result) => {
        if (isCurrent) {
          setReviews((previous) => (page === 1 ? result.items : [...previous, ...result.items]));
          setLastPage(result.lastPage);
        }
      })
      .finally(() => isCurrent && setLoading(false));

    return () => {
      isCurrent = false;
    };
  }, [tab, page, user]);

  return (
    <main className="container narrow">
      <h1 className="page-title">Fil d’actualité</h1>

      <div className="tabs" role="tablist">
        <button
          type="button"
          role="tab"
          aria-selected={tab === 'following'}
          className={`tab ${tab === 'following' ? 'tab--active' : ''}`}
          onClick={() => (user ? setTab('following') : openLogin())}
        >
          Mes abonnements
        </button>
        <button type="button" role="tab" aria-selected={tab === 'all'} className={`tab ${tab === 'all' ? 'tab--active' : ''}`} onClick={() => setTab('all')}>
          Tous les avis
        </button>
      </div>

      {!isLoading && reviews.length === 0 && (
        <div className="empty-state">
          <h2>{tab === 'following' ? 'Rien pour le moment' : 'Aucun avis'}</h2>
          {tab === 'following' && <p>Suivez des voyageurs ou des hôtes pour voir ici leurs avis.</p>}
        </div>
      )}

      <div className="feed">
        {reviews.map((review) => (
          <article key={review.id} className="feed-item">
            <header className="review__author">
              <Avatar user={review.author} size={44} />
              <div>
                <strong>{review.author.firstName}</strong>
                <span className="muted">{formatMonthYear(review.createdAt)}</span>
              </div>
              <span className="feed-item__rating">
                <Star size={14} fill="currentColor" strokeWidth={0} /> {review.rating}/5
              </span>
            </header>
            <p className="review__comment">{review.comment}</p>
            {review.listing && (
              <Link to={`/listings/${review.listing.id}`} className="feed-item__listing">
                <img src={listingCover(review.listing, 160, 160)} alt="" />
                <span>{review.listing.title}</span>
              </Link>
            )}
          </article>
        ))}
      </div>

      {!isLoading && page < lastPage && (
        <div className="home__more">
          <button type="button" className="button button--outline" onClick={() => setPage(page + 1)}>Afficher plus</button>
        </div>
      )}
    </main>
  );
}
