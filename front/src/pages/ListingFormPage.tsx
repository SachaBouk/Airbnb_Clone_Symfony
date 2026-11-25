import { useEffect, useState, type FormEvent } from 'react';
import { Navigate, useNavigate, useParams } from 'react-router-dom';
import { api, ApiError } from '../api/client';
import type { Amenity, ListingDetail, ListingDetailResponse, ListingInput } from '../api/types';
import { useAuth } from '../auth/AuthContext';
import { AmenityIcon } from '../components/AmenityIcon';

const EMPTY_FORM = { title: '', description: '', priceByNight: '', city: '', country: '', maxGuests: '2', amenityIds: [] as number[] };

type FormState = typeof EMPTY_FORM;

/** Create (/host/listings/new) or edit (/host/listings/:id/edit) a listing. */
export function ListingFormPage() {
  const { id } = useParams();
  const navigate = useNavigate();
  const { user, openLogin } = useAuth();
  const [form, setForm] = useState<FormState>(EMPTY_FORM);
  const [amenities, setAmenities] = useState<Amenity[]>([]);
  const [error, setError] = useState<ApiError | null>(null);
  const [isSubmitting, setSubmitting] = useState(false);
  const [notAllowed, setNotAllowed] = useState(false);

  useEffect(() => {
    api<Amenity[]>('/amenities').then(setAmenities);
  }, []);

  useEffect(() => {
    if (!id || !user) {
      return;
    }
    api<ListingDetailResponse>(`/listings/${id}`).then(({ listing }) => {
      if (listing.host.id !== user.id) {
        setNotAllowed(true);

        return;
      }
      setForm(toForm(listing));
    });
  }, [id, user]);

  useEffect(() => {
    if (!user) {
      openLogin();
    }
  }, [user, openLogin]);

  if (notAllowed) {
    return <Navigate to={`/listings/${id}`} replace />;
  }

  if (!user) {
    return (
      <main className="container empty-state">
        <h2>Connectez-vous pour mettre votre logement en location</h2>
        <button type="button" className="button button--primary" onClick={openLogin}>Connexion</button>
      </main>
    );
  }

  const update = (patch: Partial<FormState>) => setForm({ ...form, ...patch });

  function toggleAmenity(amenityId: number) {
    update({
      amenityIds: form.amenityIds.includes(amenityId)
        ? form.amenityIds.filter((selected) => selected !== amenityId)
        : [...form.amenityIds, amenityId],
    });
  }

  async function handleSubmit(event: FormEvent) {
    event.preventDefault();
    setSubmitting(true);
    setError(null);
    const body: ListingInput = {
      ...form,
      priceByNight: Number(form.priceByNight),
      maxGuests: Number(form.maxGuests),
    };
    try {
      const saved = await api<ListingDetail>(id ? `/listings/${id}` : '/listings', { method: id ? 'PUT' : 'POST', body });
      navigate(`/listings/${saved.id}`);
    } catch (exception) {
      setError(exception instanceof ApiError ? exception : new ApiError(0, 'Enregistrement impossible.'));
      window.scrollTo({ top: 0, behavior: 'smooth' });
    } finally {
      setSubmitting(false);
    }
  }

  const fieldError = (field: string) => error?.fieldError(field) && <p className="form-error">{error.fieldError(field)}</p>;

  return (
    <main className="container narrow">
      <h1 className="page-title">{id ? 'Modifier votre logement' : 'Mettez votre logement en location'}</h1>
      {error && !error.violations.length && <p className="form-error">{error.message}</p>}

      <form onSubmit={handleSubmit} className="listing-form">
        <section>
          <h2>Décrivez votre logement</h2>
          <label className="field field--single">
            <span>Titre</span>
            <input value={form.title} onChange={(event) => update({ title: event.target.value })} maxLength={255} />
          </label>
          {fieldError('title')}
          <label className="field field--single">
            <span>Description</span>
            <textarea rows={6} value={form.description} onChange={(event) => update({ description: event.target.value })} maxLength={5000} />
          </label>
          {fieldError('description')}
        </section>

        <section>
          <h2>Où se situe votre logement ?</h2>
          <div className="form-row">
            <div>
              <label className="field field--single">
                <span>Ville</span>
                <input value={form.city} onChange={(event) => update({ city: event.target.value })} />
              </label>
              {fieldError('city')}
            </div>
            <div>
              <label className="field field--single">
                <span>Pays</span>
                <input value={form.country} onChange={(event) => update({ country: event.target.value })} />
              </label>
              {fieldError('country')}
            </div>
          </div>
        </section>

        <section>
          <h2>Capacité et prix</h2>
          <div className="form-row">
            <div>
              <label className="field field--single">
                <span>Voyageurs maximum</span>
                <input type="number" min={1} max={50} value={form.maxGuests} onChange={(event) => update({ maxGuests: event.target.value })} />
              </label>
              {fieldError('maxGuests')}
            </div>
            <div>
              <label className="field field--single">
                <span>Prix par nuit (€)</span>
                <input type="number" min={1} step="0.01" value={form.priceByNight} onChange={(event) => update({ priceByNight: event.target.value })} />
              </label>
              {fieldError('priceByNight')}
            </div>
          </div>
        </section>

        <section>
          <h2>Indiquez aux voyageurs ce que votre logement a à offrir</h2>
          <div className="amenity-picker">
            {amenities.map((amenity) => (
              <button
                key={amenity.id}
                type="button"
                className={`amenity-option ${form.amenityIds.includes(amenity.id) ? 'amenity-option--active' : ''}`}
                onClick={() => toggleAmenity(amenity.id)}
                aria-pressed={form.amenityIds.includes(amenity.id)}
              >
                <AmenityIcon slug={amenity.slug} size={28} />
                <span>{amenity.name}</span>
              </button>
            ))}
          </div>
          {fieldError('amenityIds')}
        </section>

        <div className="listing-form__actions">
          <button type="button" className="link-button" onClick={() => navigate(-1)}>Retour</button>
          <button type="submit" className="button button--primary" disabled={isSubmitting}>
            {isSubmitting ? 'Enregistrement…' : id ? 'Enregistrer' : 'Publier l’annonce'}
          </button>
        </div>
      </form>
    </main>
  );
}

function toForm(listing: ListingDetail): FormState {
  return {
    title: listing.title,
    description: listing.description,
    priceByNight: String(Number(listing.priceByNight)),
    city: listing.city,
    country: listing.country,
    maxGuests: String(listing.maxGuests),
    amenityIds: listing.amenities.map((amenity) => amenity.id),
  };
}
