export interface UserPublic {
  id: number;
  firstName: string;
  createdAt?: string | null;
}

export interface Me extends UserPublic {
  email: string;
  lastName: string;
  roles: string[];
}

export interface Photo {
  id: number;

  url: string;
}

export interface ListingRef {
  id: number;
  title: string;
  coverUrl: string | null;
}

export interface Amenity {
  id: number;
  name: string;
  slug: string;
}

export interface ListingSummary {
  id: number;
  title: string;

  priceByNight: string;
  city: string;
  country: string;
  maxGuests: number;
  host: UserPublic;
  amenities: Amenity[];

  coverUrl: string | null;
  averageRating: number | null;
  reviewCount: number;

  myRating?: number | null;
}

export interface ListingDetail {
  id: number;
  title: string;
  description: string;
  priceByNight: string;
  city: string;
  country: string;
  maxGuests: number;
  host: UserPublic;
  amenities: Amenity[];
  coverUrl: string | null;

  photos: Photo[];
  createdAt?: string | null;
}

export interface Review {
  id: number;
  rating: number;
  comment: string;
  createdAt: string;
  updatedAt?: string | null;
  author: UserPublic;
  listing?: ListingRef;
}

export interface ListingDetailResponse {
  listing: ListingDetail;
  averageRating: number | null;
  reviewCount: number;
  reviews: Review[];
}

export interface Page<T> {
  total: number;
  lastPage: number;
  items: T[];
}

export interface LoginResponse {
  token: string;
  ttl: number;
  user: Me;
}

export interface ListingInput {
  title: string;
  description: string;
  priceByNight: number;
  city: string;
  country: string;
  maxGuests: number;
  amenityIds: number[];
}

export interface ReviewInput {
  rating: number;
  comment: string;
}

export type BookingStatus = 'pending' | 'confirmed' | 'cancelled';

export interface Booking {
  id: number;

  arrivalDate: string;
  departureDate: string;
  guests: number;
  nights: number;

  totalPrice: string;
  status: BookingStatus;
  traveler: UserPublic;
  listing: ListingRef & { city: string; country: string };
}

export interface BookingInput {
  arrivalDate: string;
  departureDate: string;
  guests: number;
}
