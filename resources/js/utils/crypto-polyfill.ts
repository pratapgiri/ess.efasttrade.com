/** RFC 4122 v4 UUID without requiring a secure context (works on http:// local dev). */
function generateUuid(): string {
    return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, (c) => {
        const r = Math.trunc(Math.random() * 16);
        const v = c === 'x' ? r : (r & 0x3) | 0x8;
        return v.toString(16);
    });
}

/** Call once at app startup so libraries using crypto.randomUUID work on HTTP. */
export function installCryptoRandomUuidPolyfill(): void {
    if (typeof globalThis === 'undefined') {
        return;
    }

    const root = globalThis as typeof globalThis & { crypto?: Crypto };

    if (!root.crypto) {
        Object.defineProperty(globalThis, 'crypto', {
            value: { randomUUID: generateUuid },
            configurable: true,
        });
        return;
    }

    if (typeof root.crypto.randomUUID !== 'function') {
        root.crypto.randomUUID = generateUuid as Crypto['randomUUID'];
    }
}

export function randomUUID(): string {
    installCryptoRandomUuidPolyfill();
    return globalThis.crypto.randomUUID();
}
