import { Globe } from 'lucide-react';
import { Link } from 'react-router-dom';
import { useAuth } from '../auth/AuthContext';
import { Logo } from './Logo';
import { SearchBar } from './SearchBar';
import { UserMenu } from './UserMenu';

export function Header() {
  const { user, openLogin } = useAuth();

  return (
    <header className="header">
      <div className="header__inner container">
        <Logo />
        <SearchBar />
        <nav className="header__actions">
          {user ? (
            <Link to="/host/listings/new" className="header__host-link">Mettre mon logement en location</Link>
          ) : (
            <button type="button" className="header__host-link" onClick={openLogin}>Mettre mon logement en location</button>
          )}
          <span className="header__globe" aria-hidden="true"><Globe size={16} /></span>
          <UserMenu />
        </nav>
      </div>
    </header>
  );
}
