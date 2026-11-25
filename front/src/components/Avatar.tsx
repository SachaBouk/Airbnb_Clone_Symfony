import type { UserPublic } from '../api/types';
import { avatarColor } from '../utils/images';

interface AvatarProps {
  user: UserPublic;
  size?: number;
}

export function Avatar({ user, size = 40 }: AvatarProps) {
  return (
    <span
      className="avatar"
      style={{ width: size, height: size, fontSize: size * 0.42, background: avatarColor(user.id) }}
      aria-hidden="true"
    >
      {user.firstName.charAt(0).toUpperCase()}
    </span>
  );
}
