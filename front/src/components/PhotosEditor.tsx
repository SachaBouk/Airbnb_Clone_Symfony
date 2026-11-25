import { ImagePlus, Star, Trash2 } from 'lucide-react';
import { useRef, useState, type ChangeEvent } from 'react';
import { api, ApiError } from '../api/client';
import type { Photo } from '../api/types';

const MAX_PHOTOS = 10;
const ACCEPTED_TYPES = 'image/jpeg,image/png,image/webp';

interface PhotosEditorProps {
  listingId: number;
  photos: Photo[];
  onChange: (photos: Photo[]) => void;
}

export function PhotosEditor({ listingId, photos, onChange }: PhotosEditorProps) {
  const inputRef = useRef<HTMLInputElement>(null);
  const [errors, setErrors] = useState<string[]>([]);
  const [uploading, setUploading] = useState(0);

  async function upload(event: ChangeEvent<HTMLInputElement>) {
    const files = Array.from(event.target.files ?? []).slice(0, MAX_PHOTOS - photos.length);
    event.target.value = '';
    setErrors([]);

    let current = photos;
    for (const file of files) {
      setUploading((count) => count + 1);
      const body = new FormData();
      body.append('photo', file);
      try {
        const photo = await api<Photo>(`/listings/${listingId}/photos`, { method: 'POST', body });
        current = [...current, photo];
        onChange(current);
      } catch (exception) {
        const message = exception instanceof ApiError ? exception.fieldError('photo') ?? exception.message : 'Envoi impossible.';
        setErrors((previous) => [...previous, `${file.name} : ${message}`]);
      } finally {
        setUploading((count) => count - 1);
      }
    }
  }

  async function remove(photo: Photo) {
    await api(`/photos/${photo.id}`, { method: 'DELETE' });
    onChange(photos.filter((other) => other.id !== photo.id));
  }

  async function makeCover(photo: Photo) {
    onChange(await api<Photo[]>(`/photos/${photo.id}/cover`, { method: 'POST' }));
  }

  return (
    <div className="photos-editor">
      <div className="photos-editor__grid">
        {photos.map((photo, index) => (
          <figure key={photo.id} className={`photos-editor__item ${index === 0 ? 'photos-editor__item--cover' : ''}`}>
            <img src={photo.url} alt="" />
            {index === 0 && <figcaption>Photo de couverture</figcaption>}
            <div className="photos-editor__actions">
              {index > 0 && (
                <button type="button" onClick={() => makeCover(photo)} aria-label="Définir comme couverture" title="Définir comme couverture">
                  <Star size={16} />
                </button>
              )}
              <button type="button" onClick={() => remove(photo)} aria-label="Supprimer la photo" title="Supprimer">
                <Trash2 size={16} />
              </button>
            </div>
          </figure>
        ))}

        {photos.length < MAX_PHOTOS && (
          <button type="button" className="photos-editor__add" onClick={() => inputRef.current?.click()} disabled={uploading > 0}>
            <ImagePlus size={32} strokeWidth={1.25} />
            <span>{uploading > 0 ? `Envoi en cours (${uploading})…` : 'Ajouter des photos'}</span>
            <small>JPEG, PNG ou WebP · 5 Mo max</small>
          </button>
        )}
      </div>

      <input ref={inputRef} type="file" accept={ACCEPTED_TYPES} multiple hidden onChange={upload} />
      <p className="muted">{photos.length} / {MAX_PHOTOS} photos · la première est la photo de couverture.</p>
      {errors.map((error) => <p key={error} className="form-error">{error}</p>)}
    </div>
  );
}
