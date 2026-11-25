import { useState, type FormEvent } from 'react';
import { ApiError } from '../api/client';
import { useAuth } from '../auth/AuthContext';
import { BRAND_NAME } from '../brand';
import { Modal } from './Modal';

export function LoginModal() {
  const { login, closeLogin } = useAuth();
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [error, setError] = useState<string | null>(null);
  const [isSubmitting, setSubmitting] = useState(false);

  async function handleSubmit(event: FormEvent) {
    event.preventDefault();
    setSubmitting(true);
    setError(null);
    try {
      await login(email, password);
    } catch (exception) {
      setError(exception instanceof ApiError ? exception.message : 'Connexion impossible.');
    } finally {
      setSubmitting(false);
    }
  }

  function fillTestAccount(accountEmail: string) {
    setEmail(accountEmail);
    setPassword('password');
  }

  return (
    <Modal title="Connexion" onClose={closeLogin}>
      <h3 className="modal__welcome">Bienvenue sur {BRAND_NAME}</h3>
      <form onSubmit={handleSubmit} className="stacked-fields">
        <div className="field-group">
          <label className="field">
            <span>Adresse e-mail</span>
            <input type="email" value={email} onChange={(event) => setEmail(event.target.value)} required autoFocus />
          </label>
          <label className="field">
            <span>Mot de passe</span>
            <input type="password" value={password} onChange={(event) => setPassword(event.target.value)} required />
          </label>
        </div>
        {error && <p className="form-error">{error}</p>}
        <button type="submit" className="button button--primary button--block" disabled={isSubmitting}>
          {isSubmitting ? 'Connexion…' : 'Continuer'}
        </button>
      </form>

      <div className="divider-text">comptes de démonstration</div>
      <div className="demo-accounts">
        <button type="button" className="button button--outline" onClick={() => fillTestAccount('host@example.com')}>
          Hôte
        </button>
        <button type="button" className="button button--outline" onClick={() => fillTestAccount('traveler@example.com')}>
          Voyageur
        </button>
      </div>
    </Modal>
  );
}
