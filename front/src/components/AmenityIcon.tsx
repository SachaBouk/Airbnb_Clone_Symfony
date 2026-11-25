import {
  AirVent,
  Car,
  Coffee,
  CookingPot,
  Fence,
  Flame,
  Laptop,
  PawPrint,
  Sailboat,
  Sparkles,
  Thermometer,
  Tv,
  WashingMachine,
  Waves,
  Wifi,
  type LucideIcon,
} from 'lucide-react';

/** Icon of each amenity slug known by the fixtures and the import file. */
const ICONS: Record<string, LucideIcon> = {
  wifi: Wifi,
  kitchen: CookingPot,
  parking: Car,
  pool: Waves,
  'air-conditioning': AirVent,
  'washing-machine': WashingMachine,
  tv: Tv,
  workspace: Laptop,
  'pets-allowed': PawPrint,
  balcony: Fence,
  'sea-view': Sailboat,
  fireplace: Flame,
  sauna: Thermometer,
  'vue-sur-l-eau': Sailboat,
  'petit-dejeuner-inclus': Coffee,
};

interface AmenityIconProps {
  slug: string;
  size?: number;
}

export function AmenityIcon({ slug, size = 24 }: AmenityIconProps) {
  const Icon = ICONS[slug] ?? Sparkles;

  return <Icon size={size} strokeWidth={1.5} aria-hidden="true" />;
}
