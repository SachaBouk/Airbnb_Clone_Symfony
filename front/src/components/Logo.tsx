import { Link } from 'react-router-dom';
import { BRAND_NAME } from '../brand';

export function Logo() {
  return (
    <Link to="/" className="logo" aria-label={`${BRAND_NAME}, accueil`}>
      <svg viewBox="0 0 32 32" aria-hidden="true">
        <path d="M4 15.5 16 5l12 10.5V27a1 1 0 0 1-1 1h-7v-7.5a4 4 0 0 0-8 0V28H5a1 1 0 0 1-1-1z" />
      </svg>
      <span>{BRAND_NAME}</span>
    </Link>
  );
}
