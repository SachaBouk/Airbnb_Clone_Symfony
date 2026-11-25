import { LayoutGrid, SlidersHorizontal } from 'lucide-react';
import type { Amenity } from '../api/types';
import { AmenityIcon } from './AmenityIcon';

interface CategoryBarProps {
  amenities: Amenity[];
  selected: string | null;
  onSelect: (slug: string | null) => void;
  onOpenFilters: () => void;
  activeFilterCount: number;
}

/** Horizontal strip of icon categories (one per amenity), like the top of a listings search page. */
export function CategoryBar({ amenities, selected, onSelect, onOpenFilters, activeFilterCount }: CategoryBarProps) {
  return (
    <div className="category-bar">
      <div className="category-bar__inner container">
        <div className="category-bar__list">
          <button type="button" className={`category ${selected === null ? 'category--active' : ''}`} onClick={() => onSelect(null)}>
            <LayoutGrid size={24} strokeWidth={1.5} aria-hidden="true" />
            <span>Tout</span>
          </button>
          {amenities.map((amenity) => (
            <button
              key={amenity.id}
              type="button"
              className={`category ${selected === amenity.slug ? 'category--active' : ''}`}
              onClick={() => onSelect(amenity.slug)}
            >
              <AmenityIcon slug={amenity.slug} />
              <span>{amenity.name}</span>
            </button>
          ))}
        </div>
        <button type="button" className="filters-button" onClick={onOpenFilters}>
          <SlidersHorizontal size={14} strokeWidth={2.5} />
          Filtres
          {activeFilterCount > 0 && <span className="filters-button__count">{activeFilterCount}</span>}
        </button>
      </div>
    </div>
  );
}
