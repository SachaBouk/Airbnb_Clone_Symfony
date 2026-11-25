import { useState } from 'react';
import { Modal } from './Modal';

export interface Filters {
  sort: string;
  maxPrice: string;
  title: string;
}

const SORTS = [
  { value: '', label: 'Pertinence' },
  { value: 'rating', label: 'Les mieux notés' },
  { value: 'price_asc', label: 'Prix croissant' },
  { value: 'price_desc', label: 'Prix décroissant' },
];

interface FiltersModalProps {
  initial: Filters;
  onApply: (filters: Filters) => void;
  onClose: () => void;
}

export function FiltersModal({ initial, onApply, onClose }: FiltersModalProps) {
  const [filters, setFilters] = useState<Filters>(initial);
  const update = (patch: Partial<Filters>) => setFilters({ ...filters, ...patch });

  return (
    <Modal
      title="Filtres"
      onClose={onClose}
      footer={
        <>
          <button type="button" className="link-button" onClick={() => setFilters({ sort: '', maxPrice: '', title: '' })}>
            Tout effacer
          </button>
          <button type="button" className="button button--dark" onClick={() => onApply(filters)}>
            Afficher les logements
          </button>
        </>
      }
    >
      <section className="filters-section">
        <h3>Trier par</h3>
        <div className="chips">
          {SORTS.map((sort) => (
            <button
              key={sort.value}
              type="button"
              className={`chip ${filters.sort === sort.value ? 'chip--active' : ''}`}
              onClick={() => update({ sort: sort.value })}
            >
              {sort.label}
            </button>
          ))}
        </div>
      </section>

      <section className="filters-section">
        <h3>Prix maximum par nuit</h3>
        <p className="muted">{filters.maxPrice ? `${filters.maxPrice} €` : 'Aucune limite'}</p>
        <input
          type="range"
          min={20}
          max={500}
          step={10}
          value={filters.maxPrice || 500}
          onChange={(event) => update({ maxPrice: event.target.value === '500' ? '' : event.target.value })}
          className="range"
        />
      </section>

      <section className="filters-section">
        <h3>Mot-clé dans le titre</h3>
        <label className="field field--single">
          <span>Titre</span>
          <input value={filters.title} onChange={(event) => update({ title: event.target.value })} placeholder="villa, studio, chalet…" />
        </label>
      </section>
    </Modal>
  );
}
