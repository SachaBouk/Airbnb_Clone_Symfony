export interface Violation {
  propertyPath: string;
  title: string;
}

export class ApiError extends Error {
  constructor(
    public readonly status: number,
    message: string,
    public readonly violations: Violation[] = [],
  ) {
    super(message);
  }

  fieldError(field: string): string | undefined {
    return this.violations.find((violation) => violation.propertyPath === field)?.title;
  }
}

const TOKEN_KEY = 'escale.token';

let token: string | null = localStorage.getItem(TOKEN_KEY);
let onSessionExpired: (() => void) | null = null;

export function setToken(value: string | null): void {
  token = value;
  if (value) {
    localStorage.setItem(TOKEN_KEY, value);
  } else {
    localStorage.removeItem(TOKEN_KEY);
  }
}

export function getToken(): string | null {
  return token;
}

export function setSessionExpiredHandler(handler: () => void): void {
  onSessionExpired = handler;
}

interface RequestOptions {
  method?: 'GET' | 'POST' | 'PUT' | 'DELETE';
  body?: unknown;
}

export async function api<T>(path: string, { method = 'GET', body }: RequestOptions = {}): Promise<T> {
  const sentToken = token;

  const isFormData = body instanceof FormData;
  const response = await fetch(`/api${path}`, {
    method,
    headers: {
      Accept: 'application/json',
      ...(body !== undefined && !isFormData ? { 'Content-Type': 'application/json' } : {}),
      ...(sentToken ? { Authorization: `Bearer ${sentToken}` } : {}),
    },
    body: isFormData ? body : body !== undefined ? JSON.stringify(body) : undefined,
  });

  if (response.status === 401 && sentToken) {
    setToken(null);
    onSessionExpired?.();

    if (method === 'GET') {
      return api<T>(path, { method, body });
    }
  }

  if (response.status === 204) {
    return undefined as T;
  }

  const data = await response.json().catch(() => null);
  if (!response.ok) {
    throw new ApiError(response.status, readableMessage(response.status, data), data?.violations ?? []);
  }

  return data as T;
}

interface ErrorBody {
  detail?: string;
  message?: string;
  violations?: Violation[];
}

function readableMessage(status: number, data: ErrorBody | null): string {
  if (status === 401) {
    return data?.message === 'Invalid credentials.' ? 'Email ou mot de passe incorrect.' : 'Veuillez vous connecter.';
  }
  if (status === 422 && data?.violations) {
    return 'Certains champs sont invalides.';
  }
  const message = data?.detail ?? data?.message ?? 'Une erreur est survenue.';

  return message.replace(/^Access Denied\.\s*/, '') || "Vous n'avez pas le droit de faire cette action.";
}
