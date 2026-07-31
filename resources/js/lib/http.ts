function readCookie(name: string): string | null {
    const match = document.cookie.match(
        new RegExp('(?:^|; )' + name + '=([^;]*)'),
    );

    return match ? decodeURIComponent(match[1]) : null;
}

export class HttpError extends Error {
    constructor(
        message: string,
        public status: number,
        public errors: Record<string, string> = {},
    ) {
        super(message);
        this.name = 'HttpError';
    }
}

/**
 * POST JSON to a Laravel endpoint outside the Inertia flow (e.g. inline
 * quick-create). Sends the XSRF token and returns the decoded JSON body.
 * Throws HttpError (with per-field `errors` on 422) on failure.
 */
export async function postJson<T = unknown>(
    url: string,
    payload: Record<string, unknown>,
): Promise<T> {
    const token = readCookie('XSRF-TOKEN');

    const response = await fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            ...(token ? { 'X-XSRF-TOKEN': token } : {}),
        },
        credentials: 'same-origin',
        body: JSON.stringify(payload),
    });

    if (response.ok) {
        return (await response.json()) as T;
    }

    if (response.status === 422) {
        const body = (await response.json()) as {
            message?: string;
            errors?: Record<string, string[]>;
        };
        const errors: Record<string, string> = {};

        for (const [key, messages] of Object.entries(body.errors ?? {})) {
            errors[key] = messages[0];
        }

        throw new HttpError(body.message ?? 'Validasi gagal.', 422, errors);
    }

    throw new HttpError('Terjadi kesalahan.', response.status);
}
