export type User = {
    id: number;
    name: string;
    email: string;
    role: 'admin' | 'guru' | 'mpk';
    avatar?: string;
    email_verified_at: string | null;
    two_factor_enabled?: boolean;
    created_at: string;
    updated_at: string;
    /** Kelas yang ditempati MPK. Null = belum ditempati / bukan MPK. */
    kelas_id?: number | null;
    [key: string]: unknown;
};

export type Auth = {
    user: User;
};

export type Passkey = {
    id: number;
    name: string;
    authenticator: string | null;
    created_at_diff: string;
    last_used_at_diff: string | null;
};

export type TwoFactorSetupData = {
    svg: string;
    url: string;
};

export type TwoFactorSecretKey = {
    secretKey: string;
};
