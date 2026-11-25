import { useCallback, useEffect, useState, type ReactNode } from 'react';
import { api } from '../api/client';
import type { Page, UserPublic } from '../api/types';
import { useAuth } from '../auth/AuthContext';
import { Avatar } from '../components/Avatar';
import { formatMonthYear } from '../utils/format';

/** The logged-in user: profile, who he follows and who follows him. */
export function AccountPage() {
  const { user, openLogin } = useAuth();
  const [following, setFollowing] = useState<UserPublic[]>([]);
  const [followers, setFollowers] = useState<UserPublic[]>([]);

  const load = useCallback(() => {
    if (!user) {
      return;
    }
    api<Page<UserPublic>>(`/users/${user.id}/following?limit=50`).then((page) => setFollowing(page.items));
    api<Page<UserPublic>>(`/users/${user.id}/followers?limit=50`).then((page) => setFollowers(page.items));
  }, [user]);

  useEffect(load, [load]);

  if (!user) {
    return (
      <main className="container empty-state">
        <h2>Connectez-vous pour voir votre compte</h2>
        <button type="button" className="button button--primary" onClick={openLogin}>Connexion</button>
      </main>
    );
  }

  async function unfollow(userId: number) {
    await api(`/users/${userId}/follow`, { method: 'DELETE' });
    load();
  }

  return (
    <main className="container narrow">
      <h1 className="page-title">Compte</h1>
      <section className="profile-card">
        <Avatar user={user} size={96} />
        <div>
          <h2>{user.firstName} {user.lastName}</h2>
          <p className="muted">{user.email}</p>
          <p className="muted">Membre depuis {formatMonthYear(user.createdAt)}</p>
        </div>
      </section>

      <div className="people-columns">
        <section>
          <h2>Abonnements ({following.length})</h2>
          <PeopleList people={following} emptyText="Vous ne suivez personne pour le moment." action={(person) => (
            <button type="button" className="button button--outline button--small" onClick={() => unfollow(person.id)}>Ne plus suivre</button>
          )} />
        </section>
        <section>
          <h2>Abonnés ({followers.length})</h2>
          <PeopleList people={followers} emptyText="Personne ne vous suit encore." />
        </section>
      </div>
    </main>
  );
}

interface PeopleListProps {
  people: UserPublic[];
  emptyText: string;
  action?: (person: UserPublic) => ReactNode;
}

function PeopleList({ people, emptyText, action }: PeopleListProps) {
  if (people.length === 0) {
    return <p className="muted">{emptyText}</p>;
  }

  return (
    <ul className="people-list">
      {people.map((person) => (
        <li key={person.id}>
          <Avatar user={person} size={40} />
          <span>{person.firstName}</span>
          {action?.(person)}
        </li>
      ))}
    </ul>
  );
}
