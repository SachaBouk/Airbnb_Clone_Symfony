import { CircleUserRound, Menu } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import { Link } from 'react-router-dom';
import { useAuth } from '../auth/AuthContext';
import { Avatar } from './Avatar';

export function UserMenu() {
  const { user, openLogin, logout } = useAuth();
  const [isOpen, setOpen] = useState(false);
  const menuRef = useRef<HTMLDivElement>(null);

  useEffect(() => {
    const closeOnOutsideClick = (event: MouseEvent) => {
      if (!menuRef.current?.contains(event.target as Node)) {
        setOpen(false);
      }
    };
    document.addEventListener('mousedown', closeOnOutsideClick);

    return () => document.removeEventListener('mousedown', closeOnOutsideClick);
  }, []);

  const close = () => setOpen(false);

  return (
    <div className="user-menu" ref={menuRef}>
      <button type="button" className="user-menu__button" onClick={() => setOpen(!isOpen)} aria-expanded={isOpen} aria-label="Menu du compte">
        <Menu size={16} strokeWidth={2.5} />
        {user ? <Avatar user={user} size={30} /> : <CircleUserRound size={30} strokeWidth={1.25} className="user-menu__guest" />}
      </button>

      {isOpen && (
        <div className="user-menu__dropdown" role="menu">
          {user ? (
            <>
              <p className="user-menu__hello">Bonjour {user.firstName}</p>
              <Link to="/trips" onClick={close} className="user-menu__item user-menu__item--strong">Voyages</Link>
              <Link to="/feed" onClick={close} className="user-menu__item user-menu__item--strong">Fil d'actualité</Link>
              <Link to="/account" onClick={close} className="user-menu__item user-menu__item--strong">Mon compte</Link>
              <hr />
              <Link to="/host/listings/new" onClick={close} className="user-menu__item">Mettre mon logement en location</Link>
              <hr />
              <button type="button" onClick={() => { logout(); close(); }} className="user-menu__item">Déconnexion</button>
            </>
          ) : (
            <>
              <button type="button" onClick={() => { openLogin(); close(); }} className="user-menu__item user-menu__item--strong">Connexion</button>
              <Link to="/feed" onClick={close} className="user-menu__item">Derniers avis</Link>
            </>
          )}
        </div>
      )}
    </div>
  );
}
