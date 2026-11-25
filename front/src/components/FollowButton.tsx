import { useEffect, useState } from 'react';
import { api } from '../api/client';
import type { Page, UserPublic } from '../api/types';
import { useAuth } from '../auth/AuthContext';

export function FollowButton({ userId }: { userId: number }) {
  const { user, openLogin } = useAuth();
  const [isFollowing, setFollowing] = useState(false);
  const [isBusy, setBusy] = useState(false);

  useEffect(() => {
    if (!user) {
      setFollowing(false);

      return;
    }
    api<Page<UserPublic>>(`/users/${user.id}/following?limit=50`)
      .then((page) => setFollowing(page.items.some((followed) => followed.id === userId)))
      .catch(() => setFollowing(false));
  }, [user, userId]);

  if (user?.id === userId) {
    return null;
  }

  async function toggle() {
    if (!user) {
      openLogin();

      return;
    }
    setBusy(true);
    try {
      await api(`/users/${userId}/follow`, { method: isFollowing ? 'DELETE' : 'POST' });
      setFollowing(!isFollowing);
    } finally {
      setBusy(false);
    }
  }

  return (
    <button type="button" className={`button ${isFollowing ? 'button--outline' : 'button--dark'}`} onClick={toggle} disabled={isBusy}>
      {isFollowing ? 'Abonné' : 'Suivre'}
    </button>
  );
}
