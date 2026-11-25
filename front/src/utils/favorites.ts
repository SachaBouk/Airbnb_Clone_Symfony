import { useCallback, useState } from 'react';

const FAVORITES_KEY = 'escale.favorites';

function readFavorites(): number[] {
  try {
    return JSON.parse(localStorage.getItem(FAVORITES_KEY) ?? '[]') as number[];
  } catch {
    return [];
  }
}

/** Favorite listings (the heart on cards), kept in this browser only. */
export function useFavorites() {
  const [favorites, setFavorites] = useState<number[]>(readFavorites);

  const toggleFavorite = useCallback((listingId: number) => {
    setFavorites((current) => {
      const next = current.includes(listingId) ? current.filter((id) => id !== listingId) : [...current, listingId];
      localStorage.setItem(FAVORITES_KEY, JSON.stringify(next));

      return next;
    });
  }, []);

  return { isFavorite: (listingId: number) => favorites.includes(listingId), toggleFavorite };
}
