import { Search } from 'lucide-react';
import { useEffect, useState, type FormEvent } from 'react';
import { useNavigate, useSearchParams } from 'react-router-dom';

export function SearchBar() {
  const navigate = useNavigate();
  const [params] = useSearchParams();
  const [city, setCity] = useState('');
  const [availableFrom, setAvailableFrom] = useState('');
  const [availableTo, setAvailableTo] = useState('');
  const [guests, setGuests] = useState('');

  useEffect(() => {
    setCity(params.get('city') ?? '');
    setAvailableFrom(params.get('availableFrom') ?? '');
    setAvailableTo(params.get('availableTo') ?? '');
    setGuests(params.get('guests') ?? '');
  }, [params]);

  function handleSubmit(event: FormEvent) {
    event.preventDefault();
    const next = new URLSearchParams(params);
    const values = { city: city.trim(), availableFrom, availableTo, guests };
    for (const [key, value] of Object.entries(values)) {
      if (value) {
        next.set(key, value);
      } else {
        next.delete(key);
      }
    }

    if (!availableFrom || !availableTo) {
      next.delete('availableFrom');
      next.delete('availableTo');
    }
    navigate(`/?${next.toString()}`);
  }

  return (
    <form className="search-bar" onSubmit={handleSubmit} role="search">
      <label className="search-bar__segment search-bar__segment--wide">
        <span>Destination</span>
        <input value={city} onChange={(event) => setCity(event.target.value)} placeholder="Rechercher une ville" />
      </label>
      <label className="search-bar__segment">
        <span>Arrivée</span>
        <input type="date" value={availableFrom} onChange={(event) => setAvailableFrom(event.target.value)} />
      </label>
      <label className="search-bar__segment">
        <span>Départ</span>
        <input
          type="date"
          value={availableTo}
          min={availableFrom || undefined}
          onChange={(event) => setAvailableTo(event.target.value)}
        />
      </label>
      <label className="search-bar__segment">
        <span>Voyageurs</span>
        <input
          type="number"
          min={1}
          max={50}
          value={guests}
          onChange={(event) => setGuests(event.target.value)}
          placeholder="Combien ?"
        />
      </label>
      <button type="submit" className="search-bar__submit" aria-label="Rechercher">
        <Search size={16} strokeWidth={3} />
      </button>
    </form>
  );
}
