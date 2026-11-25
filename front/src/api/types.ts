/** Shapes of the JSON returned by the Symfony API. */

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
  /** Relative URL, e.g. "/uploads/listings/3f9a….jpg". */
  url: string;
}

/** The short form of a listing embedded in bookings and reviews. */
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
  /** Decimal as a string, e.g. "120.00". */
  priceByNight: string;
  city: string;
  country: string;
  maxGuests: number;
  host: UserPublic;
  amenities: Amenity[];
  /** First uploaded photo, or null when the host has not uploaded any yet. */
  coverUrl: string | null;
  averageRating: number | null;
  reviewCount: number;
  /** Present only when logged in: my own rating, or null. */
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
  /** Gallery, cover first. */
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
  /** "YYYY-MM-DD" */
  arrivalDate: string;
  departureDate: string;
  guests: number;
  nights: number;
  /** Decimal as a string, e.g. "360.00". */
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
