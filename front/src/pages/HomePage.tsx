import { useEffect, useMemo, useState } from 'react';
import { useSearchParams } from 'react-router-dom';
import { api, ApiError } from '../api/client';
import type { Amenity, ListingSummary, Page } from '../api/types';
import { useAuth } from '../auth/AuthContext';
import { CategoryBar } from '../components/CategoryBar';
import { FiltersModal, type Filters } from '../components/FiltersModal';
import { ListingCard, ListingCardSkeleton } from '../components/ListingCard';
import { useFavorites } from '../utils/favorites';

const PAGE_SIZE = 20;

/** Query string keys forwarded as-is to GET /api/listings. */
const API_FILTERS = ['city', 'guests', 'availableFrom', 'availableTo', 'amenity', 'sort', 'maxPrice', 'title'];

export function HomePage() {
  const { user } = useAuth();
  const [params, setParams] = useSearchParams();
  const { isFavorite, toggleFavorite } = useFavorites();
  const [amenities, setAmenities] = useState<Amenity[]>([]);
  const [listings, setListings] = useState<ListingSummary[]>([]);
  const [page, setPage] = useState(1);
  const [lastPage, setLastPage] = useState(1);
  const [total, setTotal] = useState(0);
  const [isLoading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [isFiltersOpen, setFiltersOpen] = useState(false);

  const apiQuery = useMemo(() => {
    const query = new URLSearchParams();
    for (const key of API_FILTERS) {
      const value = params.get(key);
      if (value) {
        query.set(key, value);
      }
    }

    return query.toString();
  }, [params]);

  useEffect(() => {
    api<Amenity[]>('/amenities').then(setAmenities).catch(() => setAmenities([]));
  }, []);

  // New search (or login/logout, which changes "myRating"): restart from page 1.
  useEffect(() => {
    setPage(1);
  }, [apiQuery, user]);

  useEffect(() => {
    let isCurrent = true;
    setLoading(true);
    setError(null);
    api<Page<ListingSummary>>(`/listings?${apiQuery}&page=${page}&limit=${PAGE_SIZE}`)
      .then((result) => {
        if (!isCurrent) {
          return;
        }
        setListings((previous) => (page === 1 ? result.items : [...previous, ...result.items]));
        setLastPage(result.lastPage);
        setTotal(result.total);
      })
      .catch((exception) => isCurrent && setError(exception instanceof ApiError ? exception.message : 'Chargement impossible.'))
      .finally(() => isCurrent && setLoading(false));

    return () => {
      isCurrent = false;
    };
  }, [apiQuery, page, user]);

  function updateParams(patch: Record<string, string | null>) {
    const next = new URLSearchParams(params);
    for (const [key, value] of Object.entries(patch)) {
      if (value) {
        next.set(key, value);
      } else {
        next.delete(key);
      }
    }
    setParams(next);
  }

  const filters: Filters = { sort: params.get('sort') ?? '', maxPrice: params.get('maxPrice') ?? '', title: params.get('title') ?? '' };
  const activeFilterCount = Object.values(filters).filter(Boolean).length;

  return (
    <>
      <CategoryBar
        amenities={amenities}
        selected={params.get('amenity')}
        onSelect={(slug) => updateParams({ amenity: slug })}
        onOpenFilters={() => setFiltersOpen(true)}
        activeFilterCount={activeFilterCount}
      />

      <main className="container home">
        {!isLoading && !error && (
          <p className="home__count">{total > 0 ? `${total} logement${total > 1 ? 's' : ''}` : ''}</p>
        )}

        {error && <p className="form-error">{error}</p>}

        {!isLoading && !error && listings.length === 0 && (
          <div className="empty-state">
            <h2>Aucun résultat</h2>
            <p>Essayez de modifier ou de supprimer certains filtres.</p>
            <button type="button" className="button button--outline" onClick={() => setParams(new URLSearchParams())}>
              Supprimer tous les filtres
            </button>
          </div>
        )}

        <div className="listing-grid">
          {listings.map((listing) => (
            <ListingCard key={listing.id} listing={listing} isFavorite={isFavorite(listing.id)} onToggleFavorite={toggleFavorite} />
          ))}
          {isLoading && Array.from({ length: page === 1 ? 8 : 4 }, (_, index) => <ListingCardSkeleton key={index} />)}
        </div>

        {!isLoading && page < lastPage && (
          <div className="home__more">
            <p>Continuer à explorer les logements</p>
            <button type="button" className="button button--dark" onClick={() => setPage(page + 1)}>
              Afficher plus
            </button>
          </div>
        )}
      </main>

      {isFiltersOpen && (
        <FiltersModal
          initial={filters}
          onClose={() => setFiltersOpen(false)}
          onApply={(applied) => {
            updateParams({ sort: applied.sort, maxPrice: applied.maxPrice, title: applied.title.trim() });
            setFiltersOpen(false);
          }}
        />
      )}
    </>
  );
}
