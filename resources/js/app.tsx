import React, {
    createContext,
    useCallback,
    useContext,
    useEffect,
    useMemo,
    useState,
    type ReactNode,
} from 'react';
import { createRoot } from 'react-dom/client';

type ThemeMode = 'light' | 'dark';

const THEME_STORAGE_KEY = 'healme-theme';

function readStoredTheme(): ThemeMode {
    try {
        const saved = localStorage.getItem(THEME_STORAGE_KEY);
        return saved === 'light' || saved === 'dark' ? saved : 'dark';
    } catch {
        return 'dark';
    }
}

function applyTheme(theme: ThemeMode) {
    document.documentElement.dataset.theme = theme;
    try {
        localStorage.setItem(THEME_STORAGE_KEY, theme);
    } catch {
        // ignore storage failures
    }
}

type ThemeContextValue = {
    theme: ThemeMode;
    setTheme: (theme: ThemeMode) => void;
    toggleTheme: () => void;
};

const ThemeContext = createContext<ThemeContextValue | null>(null);

function ThemeProvider({ children }: { children: ReactNode }) {
    const [theme, setTheme] = useState<ThemeMode>(() => readStoredTheme());

    useEffect(() => {
        applyTheme(theme);
    }, [theme]);

    const toggleTheme = useCallback(() => {
        setTheme((current) => (current === 'dark' ? 'light' : 'dark'));
    }, []);

    const value = useMemo(
        () => ({ theme, setTheme, toggleTheme }),
        [theme, toggleTheme],
    );

    return <ThemeContext.Provider value={value}>{children}</ThemeContext.Provider>;
}

function useTheme(): ThemeContextValue {
    const context = useContext(ThemeContext);

    if (context === null) {
        throw new Error('useTheme must be used within ThemeProvider');
    }

    return context;
}

function ThemeToggle() {
    const { theme, toggleTheme } = useTheme();
    const isDark = theme === 'dark';

    return (
        <button
            aria-checked={isDark}
            aria-label={isDark ? 'Switch to light theme' : 'Switch to dark theme'}
            className="relative inline-flex h-9 w-16 shrink-0 items-center rounded-full border border-hm-border-strong bg-hm-surface-2 px-1 transition hover:border-hm-accent-border focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-hm-accent"
            onClick={toggleTheme}
            role="switch"
            title={isDark ? 'Light theme' : 'Dark theme'}
            type="button"
        >
            <span className="pointer-events-none absolute inset-0 z-0 flex items-center justify-between px-2">
                <span
                    aria-hidden="true"
                    className={`flex size-4 items-center justify-center transition-colors ${isDark ? 'text-hm-fg-subtle' : 'text-hm-warning-fg'}`}
                >
                    <svg className="size-3.5" fill="none" stroke="currentColor" strokeWidth="2" viewBox="0 0 24 24">
                        <circle cx="12" cy="12" r="4" />
                        <path
                            d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M4.93 19.07l1.41-1.41M17.66 6.34l1.41-1.41"
                            strokeLinecap="round"
                        />
                    </svg>
                </span>
                <span
                    aria-hidden="true"
                    className={`flex size-4 items-center justify-center transition-colors ${isDark ? 'text-hm-accent' : 'text-hm-fg-subtle'}`}
                >
                    <svg className="size-3.5" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M21 14.5A8.5 8.5 0 0 1 9.5 3 7 7 0 1 0 21 14.5Z" />
                    </svg>
                </span>
            </span>
            <span
                aria-hidden="true"
                className={`relative z-10 size-7 rounded-full bg-hm-accent shadow-sm transition-transform duration-200 ease-out ${isDark ? 'translate-x-7' : 'translate-x-0'}`}
            />
        </button>
    );
}

function BrandLogo({
    variant = 'mark',
    className = '',
    showName = false,
}: {
    variant?: 'full' | 'wordmark' | 'mark';
    className?: string;
    showName?: boolean;
}) {
    const withName = showName || variant === 'full' || variant === 'wordmark';
    const defaultSize = withName ? 'h-12 w-12' : 'h-10 w-10';

    return (
        <span className="inline-flex items-center gap-3">
            <img
                alt={withName ? '' : 'HealMe'}
                className={`object-contain ${className || defaultSize}`}
                src="/images/healme-mark.png"
            />
            {withName ? (
                <span className="flex flex-col leading-tight">
                    <span className="text-lg font-semibold tracking-[0.08em] text-hm-fg">HEALME</span>
                    {variant === 'full' ? (
                        <span className="text-[0.65rem] font-medium tracking-[0.18em] text-hm-fg-subtle">
                            INTELLIGENT HEALTHCARE CONNECTED
                        </span>
                    ) : null}
                </span>
            ) : null}
            {withName ? <span className="sr-only">HealMe</span> : null}
        </span>
    );
}

function UserAvatar({
    avatar,
    name,
    size = 'md',
}: {
    avatar: string | null;
    name: string;
    size?: 'sm' | 'md';
}) {
    const [failed, setFailed] = useState(false);
    const showImage = Boolean(avatar) && !failed;
    const sizeClass = size === 'sm' ? 'size-8' : 'size-11';
    const iconClass = size === 'sm' ? 'size-4' : 'size-5';

    useEffect(() => {
        setFailed(false);
    }, [avatar]);

    return (
        <span
            className={`inline-flex shrink-0 items-center justify-center overflow-hidden rounded-full border border-hm-border-strong bg-hm-surface-3 text-hm-fg-muted ${sizeClass}`}
            title={name}
        >
            {showImage ? (
                <img
                    alt=""
                    className="size-full object-cover"
                    key={avatar}
                    onError={() => setFailed(true)}
                    referrerPolicy="no-referrer"
                    src={avatar!}
                />
            ) : (
                <svg
                    aria-hidden="true"
                    className={iconClass}
                    fill="none"
                    stroke="currentColor"
                    strokeWidth="1.75"
                    viewBox="0 0 24 24"
                >
                    <path
                        d="M20 21a8 8 0 0 0-16 0"
                        strokeLinecap="round"
                        strokeLinejoin="round"
                    />
                    <circle cx="12" cy="8" r="3.5" />
                </svg>
            )}
            <span className="sr-only">{name}</span>
        </span>
    );
}

type StatusTone = 'neutral' | 'accent' | 'warning' | 'success' | 'danger';

function toneClasses(tone: StatusTone): string {
    switch (tone) {
        case 'success':
            return 'border-hm-success-border bg-hm-success-bg text-hm-success-fg';
        case 'warning':
            return 'border-hm-warning-border bg-hm-warning-bg text-hm-warning-fg';
        case 'danger':
            return 'border-hm-danger-border bg-hm-danger-bg text-hm-danger-fg';
        case 'accent':
            return 'border-hm-accent-border bg-hm-accent-soft text-hm-accent';
        default:
            return 'border-hm-border-strong bg-hm-surface-3 text-hm-fg-muted';
    }
}

function appointmentStatusTone(status: string): StatusTone {
    switch (status.toLowerCase()) {
        case 'pending':
            return 'warning';
        case 'confirmed':
        case 'completed':
        case 'approved':
        case 'success':
            return 'success';
        case 'cancelled':
        case 'canceled':
        case 'rejected':
        case 'revoked':
        case 'failure':
        case 'failed':
            return 'danger';
        default:
            return 'neutral';
    }
}

function accessStatusTone(status: string | null | undefined): StatusTone {
    if (status === 'approved') {
        return 'success';
    }

    if (status === 'revoked') {
        return 'danger';
    }

    return 'neutral';
}

function urgencyTone(level: string): StatusTone {
    switch (level.toLowerCase()) {
        case 'low':
            return 'success';
        case 'medium':
            return 'warning';
        case 'high':
            return 'accent';
        case 'emergency':
            return 'danger';
        default:
            return 'neutral';
    }
}

function StatusBadge({
    value,
    tone,
}: {
    value: string;
    tone?: StatusTone;
}) {
    return (
        <span
            className={`inline-flex items-center rounded-full border px-3 py-1 text-xs font-semibold uppercase tracking-wide ${toneClasses(tone ?? appointmentStatusTone(value))}`}
        >
            {value}
        </span>
    );
}

function UrgencyBadge({ level }: { level: string }) {
    return <StatusBadge tone={urgencyTone(level)} value={level} />;
}

type UserRole = 'patient' | 'doctor' | 'super_admin';

type SessionPayload = {
    authenticated: boolean;
    user: null | {
        id: number;
        name: string;
        email: string;
        role: UserRole;
        avatar: string | null;
        patient_ready: boolean;
        google_calendar_connected: boolean;
        permissions: string[];
    };
};

type TriageSummary = {
    chief_complaint: string;
    duration: string;
    recommended_specialties: string[];
    urgency_level: 'Low' | 'Medium' | 'High' | 'Emergency';
    clinical_summary: string;
};

type MatchSlot = {
    id: number;
    starts_at: string;
    ends_at: string;
};

type DoctorMatch = {
    id: number;
    name: string | null;
    specialization: string;
    rating: number;
    years_of_experience: number;
    match_score: number;
    next_available_slot: string | null;
    available_slots: MatchSlot[];
};

type BookingConfirmation = {
    id: number;
    status: string;
    doctor: string | null;
    specialization: string;
    patient: string | null;
    slot: string | null;
    chief_complaint: string | null;
    urgency_level: string;
    clinical_summary: string | null;
    google_meet_url: string | null;
};

type DirectoryDoctor = {
    id: number;
    name: string | null;
    specialization: string;
    bio: string | null;
    rating: number;
    years_of_experience: number;
    card_access_status: 'approved' | 'revoked' | null;
    available_slots: MatchSlot[];
};

type AuditLogEntry = {
    id: number;
    action: string;
    outcome: string;
    phi_involved: boolean;
    resource_type: string | null;
    resource_id: number | null;
    ip_address: string | null;
    metadata: Record<string, unknown> | null;
    created_at: string | null;
    actor: null | {
        id: number;
        name: string;
        email: string;
    };
};

type PatientCardNote = {
    id: number;
    author_type: 'doctor' | 'system';
    author_doctor_id: number | null;
    author_doctor_name: string | null;
    source: 'manual' | 'gemini';
    appointment_id: number | null;
    body: string;
    created_at: string;
};

type DoctorAccessGrant = {
    id: number;
    doctor_id: number;
    doctor_name: string | null;
    specialization: string;
    status: 'approved' | 'revoked';
    source: 'appointment' | 'patient';
    granted_via_appointment_id: number | null;
    updated_at: string;
};

type DoctorCandidate = {
    id: number;
    name: string | null;
    specialization: string;
};

type AccessiblePatient = {
    patient_profile_id: number;
    patient_name: string | null;
    access_id: number;
    access_status: string;
    access_source: string;
    updated_at: string;
};

type PatientPage = 'intake' | 'bookings' | 'card' | 'doctors';

const csrfToken =
    document
        .querySelector<HTMLMetaElement>('meta[name="csrf-token"]')
        ?.getAttribute('content') ?? '';

async function request<T>(url: string, options?: RequestInit): Promise<T> {
    const response = await fetch(url, {
        credentials: 'same-origin',
        headers: {
            Accept: 'application/json',
            ...(options?.body ? { 'Content-Type': 'application/json' } : {}),
            ...(csrfToken ? { 'X-CSRF-TOKEN': csrfToken } : {}),
            ...options?.headers,
        },
        ...options,
    });

    const payload = (await response.json().catch(() => ({}))) as {
        data?: T;
        message?: string;
    };

    if (!response.ok) {
        throw new Error(payload.message ?? 'Request failed.');
    }

    return payload.data as T;
}

type PaginationMeta = {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
};

async function requestPaginated<T>(
    url: string,
): Promise<{ data: T; meta: PaginationMeta; filters?: Record<string, unknown> }> {
    const response = await fetch(url, {
        credentials: 'same-origin',
        headers: {
            Accept: 'application/json',
            ...(csrfToken ? { 'X-CSRF-TOKEN': csrfToken } : {}),
        },
    });

    const payload = (await response.json().catch(() => ({}))) as {
        data?: T;
        meta?: PaginationMeta;
        filters?: Record<string, unknown>;
        message?: string;
    };

    if (!response.ok) {
        throw new Error(payload.message ?? 'Request failed.');
    }

    return {
        data: (payload.data ?? []) as T,
        meta: payload.meta ?? {
            current_page: 1,
            last_page: 1,
            per_page: 25,
            total: 0,
        },
        filters: payload.filters,
    };
}

function formatDate(value: string | null): string {
    if (!value) {
        return 'No slot selected';
    }

    return new Intl.DateTimeFormat(undefined, {
        dateStyle: 'medium',
        timeStyle: 'short',
    }).format(new Date(value));
}

function AuthScreen({
    onPasswordLogin,
}: {
    onPasswordLogin: (email: string, password: string) => Promise<void>;
}) {
    const authError = new URLSearchParams(window.location.search).get('auth_error');
    const [email, setEmail] = useState('doctor@healme.test');
    const [password, setPassword] = useState('password');
    const [loginError, setLoginError] = useState<string | null>(null);
    const [loginSubmitting, setLoginSubmitting] = useState(false);

    return (
        <div className="mx-auto max-w-2xl rounded-3xl border border-hm-border bg-hm-surface p-8 shadow-xl">
            <div className="flex items-start justify-between gap-3">
                <BrandLogo variant="full" />
                <ThemeToggle />
            </div>
            <h1 className="mt-4 text-4xl font-semibold tracking-tight text-hm-fg">
                Sign in to start your care journey.
            </h1>
            <p className="mt-4 text-base leading-7 text-hm-fg-muted">
                Patients and doctors can continue with Google. Local demo accounts also support
                email and password sign-in.
            </p>

            {authError === 'role_mismatch' ? (
                <div className="mt-6 rounded-2xl border border-hm-warning-border bg-hm-warning-bg p-4 text-sm text-hm-warning-fg">
                    This Google account is already linked to a different HealMe role. Use the same
                    role you originally registered with.
                </div>
            ) : null}

            <div className="mt-8 grid gap-4 sm:grid-cols-2">
                <a
                    className="rounded-2xl border border-hm-accent-border bg-hm-accent px-5 py-4 text-center text-sm font-semibold text-hm-accent-fg transition hover:bg-hm-accent-hover"
                    href="/auth/google/redirect/patient"
                >
                    Continue as Patient (Google)
                </a>
                <a
                    className="rounded-2xl border border-hm-border-strong bg-hm-surface-3 px-5 py-4 text-center text-sm font-semibold text-hm-fg transition hover:border-hm-border-strong hover:bg-hm-surface-3"
                    href="/auth/google/redirect/doctor"
                >
                    Continue as Doctor (Google)
                </a>
            </div>

            <form
                className="mt-8 border-t border-hm-border pt-8"
                onSubmit={(event) => {
                    event.preventDefault();
                    setLoginError(null);
                    setLoginSubmitting(true);
                    void onPasswordLogin(email, password)
                        .catch((error: unknown) => {
                            setLoginError(error instanceof Error ? error.message : 'Login failed.');
                        })
                        .finally(() => setLoginSubmitting(false));
                }}
            >
                <p className="text-sm font-semibold uppercase tracking-[0.3em] text-hm-fg-subtle">
                    Email sign-in
                </p>
                <p className="mt-2 text-sm text-hm-fg-subtle">
                    Demo doctor: <span className="text-hm-fg-muted">doctor@healme.test</span> /
                    password. Super admin: <span className="text-hm-fg-muted">admin@healme.test</span>{' '}
                    / password.
                </p>
                <div className="mt-4 flex flex-wrap gap-2">
                    <button
                        className="rounded-2xl border border-hm-border-strong px-4 py-2 text-xs font-semibold text-hm-fg hover:bg-hm-surface-3"
                        onClick={() => {
                            setEmail('doctor@healme.test');
                            setPassword('password');
                        }}
                        type="button"
                    >
                        Use doctor account
                    </button>
                    <button
                        className="rounded-2xl border border-hm-border-strong px-4 py-2 text-xs font-semibold text-hm-fg hover:bg-hm-surface-3"
                        onClick={() => {
                            setEmail('admin@healme.test');
                            setPassword('password');
                        }}
                        type="button"
                    >
                        Use super admin account
                    </button>
                </div>
                <div className="mt-4 grid gap-3 sm:grid-cols-2">
                    <input
                        className="rounded-2xl border border-hm-border-strong bg-hm-surface-2 px-4 py-3 text-sm text-hm-fg outline-none focus:border-hm-accent"
                        onChange={(event) => setEmail(event.target.value)}
                        placeholder="Email"
                        type="email"
                        value={email}
                    />
                    <input
                        className="rounded-2xl border border-hm-border-strong bg-hm-surface-2 px-4 py-3 text-sm text-hm-fg outline-none focus:border-hm-accent"
                        onChange={(event) => setPassword(event.target.value)}
                        placeholder="Password"
                        type="password"
                        value={password}
                    />
                </div>
                <button
                    className="mt-4 rounded-2xl bg-hm-accent px-5 py-3 text-sm font-semibold text-hm-accent-fg transition hover:bg-hm-accent-hover disabled:opacity-60"
                    disabled={loginSubmitting || !email || !password}
                    type="submit"
                >
                    {loginSubmitting ? 'Signing in...' : 'Sign in with email'}
                </button>
                {loginError ? (
                    <div className="mt-4 rounded-2xl border border-hm-danger-border bg-hm-danger-bg p-4 text-sm text-hm-danger-fg">
                        {loginError}
                    </div>
                ) : null}
            </form>
        </div>
    );
}

function DoctorDashboard({
    session,
    onLogout,
}: {
    session: NonNullable<SessionPayload['user']>;
    onLogout: () => Promise<void>;
}) {
    const [tab, setTab] = useState<'appointments' | 'cards'>('appointments');
    const [patients, setPatients] = useState<AccessiblePatient[]>([]);
    const [appointments, setAppointments] = useState<BookingConfirmation[]>([]);
    const [selectedPatientId, setSelectedPatientId] = useState<number | null>(null);
    const [notes, setNotes] = useState<PatientCardNote[]>([]);
    const [noteBody, setNoteBody] = useState('');
    const [loading, setLoading] = useState(true);
    const [notesLoading, setNotesLoading] = useState(false);
    const [submitting, setSubmitting] = useState(false);
    const [busyId, setBusyId] = useState<number | null>(null);
    const [error, setError] = useState<string | null>(null);

    const loadPatients = async () => {
        const data = await request<AccessiblePatient[]>('/api/v1/doctor/patients');
        setPatients(data ?? []);
    };

    const loadAppointments = async () => {
        const data = await request<BookingConfirmation[]>('/api/v1/doctor/appointments');
        setAppointments(data ?? []);
    };

    const refresh = async () => {
        setLoading(true);
        setError(null);

        try {
            await Promise.all([loadPatients(), loadAppointments()]);
        } catch (err) {
            setError(err instanceof Error ? err.message : 'Unable to load doctor workspace.');
        } finally {
            setLoading(false);
        }
    };

    const loadNotes = async (patientProfileId: number) => {
        setNotesLoading(true);
        setError(null);

        try {
            const data = await request<PatientCardNote[]>(
                `/api/v1/doctor/patients/${patientProfileId}/notes`,
            );
            setNotes(data ?? []);
        } catch (err) {
            setError(err instanceof Error ? err.message : 'Unable to load patient card.');
        } finally {
            setNotesLoading(false);
        }
    };

    useEffect(() => {
        void refresh();
    }, []);

    useEffect(() => {
        if (selectedPatientId === null) {
            setNotes([]);
            return;
        }

        void loadNotes(selectedPatientId);
    }, [selectedPatientId]);

    const selectedPatient =
        patients.find((patient) => patient.patient_profile_id === selectedPatientId) ?? null;

    const decideAppointment = async (appointmentId: number, action: 'approve' | 'reject') => {
        setBusyId(appointmentId);
        setError(null);

        try {
            await request(`/api/v1/doctor/appointments/${appointmentId}/${action}`, {
                method: 'POST',
            });
            await loadAppointments();
        } catch (err) {
            setError(err instanceof Error ? err.message : 'Unable to update appointment.');
        } finally {
            setBusyId(null);
        }
    };

    return (
        <div className="mx-auto max-w-6xl">
            <div className="flex flex-col gap-4 rounded-3xl border border-hm-border bg-hm-surface p-6 md:flex-row md:items-center md:justify-between">
                <div className="flex items-start gap-4">
                    <UserAvatar avatar={session.avatar} name={session.name} />
                    <div>
                        <div className="flex items-center gap-3">
                            <BrandLogo className="h-8" variant="mark" />
                            <p className="text-sm uppercase tracking-[0.3em] text-hm-accent">Doctor</p>
                        </div>
                        <h1 className="mt-2 text-3xl font-semibold text-hm-fg">Workspace</h1>
                        <p className="mt-2 text-sm text-hm-fg-muted">
                            Signed in as {session.name}. Approve pending appointments to create Google
                            Meet links, and manage patient cards you can access.
                        </p>
                    </div>
                </div>
                <div className="flex flex-wrap gap-3">
                    <button
                        className={`rounded-2xl px-5 py-3 text-sm font-semibold ${
                            tab === 'appointments'
                                ? 'bg-hm-accent text-hm-accent-fg'
                                : 'border border-hm-border-strong text-hm-fg hover:bg-hm-surface-3'
                        }`}
                        onClick={() => setTab('appointments')}
                        type="button"
                    >
                        Appointments
                    </button>
                    <button
                        className={`rounded-2xl px-5 py-3 text-sm font-semibold ${
                            tab === 'cards'
                                ? 'bg-hm-accent text-hm-accent-fg'
                                : 'border border-hm-border-strong text-hm-fg hover:bg-hm-surface-3'
                        }`}
                        onClick={() => setTab('cards')}
                        type="button"
                    >
                        Patient cards
                    </button>
                    <button
                        className="rounded-2xl border border-hm-border-strong px-5 py-3 text-sm font-semibold text-hm-fg hover:bg-hm-surface-3"
                        onClick={() => void refresh()}
                        type="button"
                    >
                        Refresh
                    </button>
                    <ThemeToggle />
                    <button
                        className="rounded-2xl border border-hm-border-strong px-5 py-3 text-sm font-semibold text-hm-fg hover:bg-hm-surface-3"
                        onClick={() => void onLogout()}
                        type="button"
                    >
                        Sign out
                    </button>
                </div>
            </div>

            {error ? (
                <div className="mt-6 rounded-2xl border border-hm-danger-border bg-hm-danger-bg p-4 text-sm text-hm-danger-fg">
                    {error}
                </div>
            ) : null}

            {tab === 'appointments' ? (
                <div className="mt-8 rounded-3xl border border-hm-border bg-hm-surface p-6">
                    <h2 className="text-xl font-semibold text-hm-fg">Appointment requests</h2>
                    <p className="mt-2 text-sm text-hm-fg-subtle">
                        Approving creates a Google Meet link and confirms the visit.
                    </p>
                    {loading ? (
                        <p className="mt-4 text-sm text-hm-fg-subtle">Loading...</p>
                    ) : appointments.length === 0 ? (
                        <p className="mt-4 text-sm text-hm-fg-subtle">No appointments yet.</p>
                    ) : (
                        <div className="mt-5 space-y-4">
                            {appointments.map((appointment) => (
                                <article
                                    className="rounded-2xl border border-hm-border bg-hm-surface-2 p-5"
                                    key={appointment.id}
                                >
                                    <div className="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                        <div>
                                            <h3 className="text-lg font-semibold text-hm-fg">
                                                #{appointment.id} ·{' '}
                                                {appointment.patient ?? 'Patient'}
                                            </h3>
                                            <p className="mt-1 flex flex-wrap items-center gap-2 text-sm text-hm-fg-muted">
                                                <span>{formatDate(appointment.slot)}</span>
                                                <UrgencyBadge level={appointment.urgency_level} />
                                            </p>
                                            <p className="mt-2 text-sm text-hm-fg-subtle">
                                                {appointment.chief_complaint}
                                            </p>
                                            {appointment.google_meet_url ? (
                                                <a
                                                    className="mt-3 inline-block text-sm font-semibold text-hm-accent hover:text-hm-accent-hover"
                                                    href={appointment.google_meet_url}
                                                    rel="noreferrer"
                                                    target="_blank"
                                                >
                                                    Open Google Meet
                                                </a>
                                            ) : null}
                                        </div>
                                        <StatusBadge value={appointment.status} />
                                    </div>
                                    {appointment.status === 'pending' ? (
                                        <div className="mt-4 flex flex-wrap gap-3">
                                            <button
                                                className="rounded-2xl bg-hm-accent px-4 py-2 text-sm font-semibold text-hm-accent-fg disabled:opacity-60"
                                                disabled={busyId === appointment.id}
                                                onClick={() =>
                                                    void decideAppointment(appointment.id, 'approve')
                                                }
                                                type="button"
                                            >
                                                Approve & create Meet
                                            </button>
                                            <button
                                                className="rounded-2xl border border-hm-danger-border px-4 py-2 text-sm font-semibold text-hm-danger-fg disabled:opacity-60"
                                                disabled={busyId === appointment.id}
                                                onClick={() =>
                                                    void decideAppointment(appointment.id, 'reject')
                                                }
                                                type="button"
                                            >
                                                Reject
                                            </button>
                                        </div>
                                    ) : null}
                                </article>
                            ))}
                        </div>
                    )}
                </div>
            ) : (
                <div className="mt-8 grid gap-6 lg:grid-cols-[0.9fr_1.1fr]">
                    <div className="rounded-3xl border border-hm-border bg-hm-surface p-6">
                        <h2 className="text-xl font-semibold text-hm-fg">Approved patients</h2>
                        {loading ? (
                            <p className="mt-4 text-sm text-hm-fg-subtle">Loading...</p>
                        ) : patients.length === 0 ? (
                            <p className="mt-4 text-sm text-hm-fg-subtle">
                                No approved patients yet.
                            </p>
                        ) : (
                            <div className="mt-4 space-y-3">
                                {patients.map((patient) => {
                                    const active =
                                        patient.patient_profile_id === selectedPatientId;

                                    return (
                                        <button
                                            className={`w-full rounded-2xl border px-4 py-3 text-left text-sm ${
                                                active
                                                    ? 'border-hm-accent bg-hm-accent-soft text-hm-accent'
                                                    : 'border-hm-border-strong text-hm-fg-muted hover:bg-hm-surface-3'
                                            }`}
                                            key={patient.patient_profile_id}
                                            onClick={() =>
                                                setSelectedPatientId(patient.patient_profile_id)
                                            }
                                            type="button"
                                        >
                                            <p className="font-semibold text-hm-fg">
                                                {patient.patient_name ??
                                                    `Patient #${patient.patient_profile_id}`}
                                            </p>
                                            <p className="mt-1 text-xs text-hm-fg-subtle">
                                                via {patient.access_source} ·{' '}
                                                {formatDate(patient.updated_at)}
                                            </p>
                                        </button>
                                    );
                                })}
                            </div>
                        )}
                    </div>

                    <div className="rounded-3xl border border-hm-border bg-hm-surface p-6">
                        <h2 className="text-xl font-semibold text-hm-fg">
                            {selectedPatient
                                ? `Card · ${selectedPatient.patient_name ?? `Patient #${selectedPatient.patient_profile_id}`}`
                                : 'Illness history'}
                        </h2>

                        {!selectedPatient ? (
                            <p className="mt-4 text-sm text-hm-fg-subtle">
                                Select a patient to read their card and add clinical notes.
                            </p>
                        ) : (
                            <>
                                <form
                                    className="mt-5"
                                    onSubmit={(event) => {
                                        event.preventDefault();
                                        if (!selectedPatientId || noteBody.trim().length < 3) {
                                            return;
                                        }

                                        setSubmitting(true);
                                        setError(null);

                                        void request<PatientCardNote>(
                                            `/api/v1/doctor/patients/${selectedPatientId}/notes`,
                                            {
                                                method: 'POST',
                                                body: JSON.stringify({ body: noteBody }),
                                            },
                                        )
                                            .then(async () => {
                                                setNoteBody('');
                                                await loadNotes(selectedPatientId);
                                            })
                                            .catch((err: unknown) => {
                                                setError(
                                                    err instanceof Error
                                                        ? err.message
                                                        : 'Unable to save note.',
                                                );
                                            })
                                            .finally(() => setSubmitting(false));
                                    }}
                                >
                                    <textarea
                                        className="min-h-28 w-full rounded-2xl border border-hm-border-strong bg-hm-surface-2 px-4 py-3 text-sm text-hm-fg outline-none focus:border-hm-accent"
                                        onChange={(event) => setNoteBody(event.target.value)}
                                        placeholder="Write a clinical note for this patient's illness history..."
                                        value={noteBody}
                                    />
                                    <button
                                        className="mt-3 rounded-2xl bg-hm-accent px-5 py-3 text-sm font-semibold text-hm-accent-fg transition hover:bg-hm-accent-hover disabled:opacity-60"
                                        disabled={submitting || noteBody.trim().length < 3}
                                        type="submit"
                                    >
                                        {submitting ? 'Saving...' : 'Add note'}
                                    </button>
                                </form>

                                <div className="mt-6 space-y-4">
                                    {notesLoading ? (
                                        <p className="text-sm text-hm-fg-subtle">Loading notes...</p>
                                    ) : notes.length === 0 ? (
                                        <p className="text-sm text-hm-fg-subtle">No notes yet.</p>
                                    ) : (
                                        notes.map((note) => (
                                            <article
                                                className="rounded-2xl border border-hm-border bg-hm-surface-2 p-4"
                                                key={note.id}
                                            >
                                                <div className="flex flex-wrap items-center justify-between gap-2 text-xs text-hm-fg-subtle">
                                                    <span>
                                                        {note.author_type === 'system'
                                                            ? 'System (Gemini)'
                                                            : note.author_doctor_name ?? 'Doctor'}
                                                        {' · '}
                                                        {note.source}
                                                    </span>
                                                    <span>{formatDate(note.created_at)}</span>
                                                </div>
                                                <p className="mt-3 whitespace-pre-wrap text-sm leading-6 text-hm-fg-muted">
                                                    {note.body}
                                                </p>
                                            </article>
                                        ))
                                    )}
                                </div>
                            </>
                        )}
                    </div>
                </div>
            )}
        </div>
    );
}

function PatientCardPage() {
    const [notes, setNotes] = useState<PatientCardNote[]>([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState<string | null>(null);

    const loadCard = async () => {
        setLoading(true);
        setError(null);

        try {
            const notesData = await request<PatientCardNote[]>('/api/v1/patient-card/notes');
            setNotes(notesData ?? []);
        } catch (err) {
            setError(err instanceof Error ? err.message : 'Unable to load patient card.');
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        void loadCard();
    }, []);

    return (
        <div className="rounded-3xl border border-hm-border bg-hm-surface p-6">
            <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p className="text-sm font-semibold uppercase tracking-[0.3em] text-hm-accent">
                        My card
                    </p>
                    <h2 className="mt-2 text-2xl font-semibold text-hm-fg">Illness history</h2>
                    <p className="mt-2 text-sm text-hm-fg-muted">
                        Read-only for you. Manage which doctors can access this card on the Doctors
                        page.
                    </p>
                </div>
                <button
                    className="rounded-2xl border border-hm-border-strong px-5 py-3 text-sm font-semibold text-hm-fg hover:bg-hm-surface-3"
                    onClick={() => void loadCard()}
                    type="button"
                >
                    Refresh
                </button>
            </div>

            {error ? (
                <div className="mt-6 rounded-2xl border border-hm-danger-border bg-hm-danger-bg p-4 text-sm text-hm-danger-fg">
                    {error}
                </div>
            ) : null}

            {loading ? (
                <p className="mt-6 text-sm text-hm-fg-subtle">Loading card...</p>
            ) : notes.length === 0 ? (
                <p className="mt-6 text-sm text-hm-fg-subtle">
                    No notes yet. Book an appointment to create the first Gemini intake note.
                </p>
            ) : (
                <div className="mt-6 space-y-4">
                    {notes.map((note) => (
                        <article
                            className="rounded-2xl border border-hm-border bg-hm-surface-2 p-5"
                            key={note.id}
                        >
                            <div className="flex flex-wrap items-center justify-between gap-2 text-xs text-hm-fg-subtle">
                                <span>
                                    {note.author_type === 'system'
                                        ? 'System (Gemini)'
                                        : note.author_doctor_name ?? 'Doctor'}
                                    {' · '}
                                    {note.source}
                                </span>
                                <span>{formatDate(note.created_at)}</span>
                            </div>
                            <p className="mt-3 whitespace-pre-wrap text-sm leading-6 text-hm-fg-muted">
                                {note.body}
                            </p>
                        </article>
                    ))}
                </div>
            )}
        </div>
    );
}

function DoctorsPage() {
    const [doctors, setDoctors] = useState<DirectoryDoctor[]>([]);
    const [specializations, setSpecializations] = useState<string[]>([]);
    const [nameFilter, setNameFilter] = useState('');
    const [specializationFilter, setSpecializationFilter] = useState('');
    const [appliedName, setAppliedName] = useState('');
    const [appliedSpecialization, setAppliedSpecialization] = useState('');
    const [page, setPage] = useState(1);
    const [meta, setMeta] = useState<PaginationMeta>({
        current_page: 1,
        last_page: 1,
        per_page: 12,
        total: 0,
    });
    const [selectedDoctorId, setSelectedDoctorId] = useState<number | null>(null);
    const [selectedSlotId, setSelectedSlotId] = useState<number | null>(null);
    const [symptoms, setSymptoms] = useState('');
    const [loading, setLoading] = useState(true);
    const [submitting, setSubmitting] = useState(false);
    const [busyDoctorId, setBusyDoctorId] = useState<number | null>(null);
    const [error, setError] = useState<string | null>(null);
    const [bookingMessage, setBookingMessage] = useState<string | null>(null);

    const loadDoctors = async (
        pageNumber = page,
        name = appliedName,
        specialization = appliedSpecialization,
    ) => {
        setLoading(true);
        setError(null);

        try {
            const params = new URLSearchParams({
                page: String(pageNumber),
                per_page: '12',
            });

            if (name.trim() !== '') {
                params.set('name', name.trim());
            }

            if (specialization.trim() !== '') {
                params.set('specialization', specialization.trim());
            }

            const result = await requestPaginated<DirectoryDoctor[]>(
                `/api/v1/doctors?${params.toString()}`,
            );
            setDoctors(result.data ?? []);
            setMeta(result.meta);
            setPage(result.meta.current_page);

            const nextSpecializations = result.filters?.specializations;
            if (Array.isArray(nextSpecializations)) {
                setSpecializations(
                    nextSpecializations.filter((value): value is string => typeof value === 'string'),
                );
            }

            if (
                selectedDoctorId !== null &&
                !(result.data ?? []).some((doctor) => doctor.id === selectedDoctorId)
            ) {
                setSelectedDoctorId(null);
                setSelectedSlotId(null);
            }
        } catch (err) {
            setError(err instanceof Error ? err.message : 'Unable to load doctors.');
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        void loadDoctors(page, appliedName, appliedSpecialization);
        // eslint-disable-next-line react-hooks/exhaustive-deps -- reload when page/filters change
    }, [page, appliedName, appliedSpecialization]);

    const selectedDoctor =
        doctors.find((doctor) => doctor.id === selectedDoctorId) ?? null;

    const applyFilters = () => {
        setSelectedDoctorId(null);
        setSelectedSlotId(null);
        setAppliedName(nameFilter);
        setAppliedSpecialization(specializationFilter);
        setPage(1);
    };

    const clearFilters = () => {
        setNameFilter('');
        setSpecializationFilter('');
        setAppliedName('');
        setAppliedSpecialization('');
        setSelectedDoctorId(null);
        setSelectedSlotId(null);
        setPage(1);
    };

    const updateAccess = async (doctorId: number, action: 'approve' | 'revoke') => {
        setBusyDoctorId(doctorId);
        setError(null);

        try {
            await request(`/api/v1/patient-card/access/${action}`, {
                method: 'POST',
                body: JSON.stringify({ doctor_id: doctorId }),
            });
            await loadDoctors(page, appliedName, appliedSpecialization);
        } catch (err) {
            setError(err instanceof Error ? err.message : 'Unable to update access.');
        } finally {
            setBusyDoctorId(null);
        }
    };

    const bookAppointment = async () => {
        if (!selectedDoctor || !selectedSlotId || symptoms.trim().length < 10) {
            return;
        }

        setSubmitting(true);
        setError(null);
        setBookingMessage(null);

        try {
            const booking = await request<BookingConfirmation>('/api/v1/appointments', {
                method: 'POST',
                body: JSON.stringify({
                    doctor_id: selectedDoctor.id,
                    doctor_availability_slot_id: selectedSlotId,
                    symptoms,
                }),
            });

            setBookingMessage(
                `Pending appointment #${booking.id} created. The doctor must approve it before a Meet link appears.`,
            );
            setSymptoms('');
            setSelectedSlotId(null);
            await loadDoctors(page, appliedName, appliedSpecialization);
        } catch (err) {
            setError(err instanceof Error ? err.message : 'Unable to book appointment.');
        } finally {
            setSubmitting(false);
        }
    };

    return (
        <div className="space-y-8">
            <div className="rounded-3xl border border-hm-border bg-hm-surface p-6">
                <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <p className="text-sm font-semibold uppercase tracking-[0.3em] text-hm-accent">
                            Doctors
                        </p>
                        <h2 className="mt-2 text-2xl font-semibold text-hm-fg">
                            Learn, grant access, and book
                        </h2>
                        <p className="mt-2 text-sm text-hm-fg-muted">
                            Search by name or specialization, then request an appointment. Doctors
                            approve every booking before Google Meet is created.
                        </p>
                    </div>
                    <button
                        className="rounded-2xl border border-hm-border-strong px-5 py-3 text-sm font-semibold text-hm-fg hover:bg-hm-surface-3"
                        onClick={() => void loadDoctors(page, appliedName, appliedSpecialization)}
                        type="button"
                    >
                        Refresh
                    </button>
                </div>

                <form
                    className="mt-6 grid gap-3 md:grid-cols-[1.2fr_1fr_auto_auto]"
                    onSubmit={(event) => {
                        event.preventDefault();
                        applyFilters();
                    }}
                >
                    <input
                        className="rounded-2xl border border-hm-border-strong bg-hm-surface-2 px-4 py-3 text-sm text-hm-fg outline-none focus:border-hm-accent"
                        onChange={(event) => setNameFilter(event.target.value)}
                        placeholder="Search by doctor name"
                        type="search"
                        value={nameFilter}
                    />
                    <select
                        className="rounded-2xl border border-hm-border-strong bg-hm-surface-2 px-4 py-3 text-sm text-hm-fg outline-none focus:border-hm-accent"
                        onChange={(event) => setSpecializationFilter(event.target.value)}
                        value={specializationFilter}
                    >
                        <option value="">All specializations</option>
                        {specializations.map((specialization) => (
                            <option key={specialization} value={specialization}>
                                {specialization}
                            </option>
                        ))}
                    </select>
                    <button
                        className="rounded-2xl bg-hm-accent px-5 py-3 text-sm font-semibold text-hm-accent-fg transition hover:bg-hm-accent-hover"
                        type="submit"
                    >
                        Search
                    </button>
                    <button
                        className="rounded-2xl border border-hm-border-strong px-5 py-3 text-sm font-semibold text-hm-fg hover:bg-hm-surface-3"
                        onClick={clearFilters}
                        type="button"
                    >
                        Clear
                    </button>
                </form>

                {error ? (
                    <div className="mt-6 rounded-2xl border border-hm-danger-border bg-hm-danger-bg p-4 text-sm text-hm-danger-fg">
                        {error}
                    </div>
                ) : null}
                {bookingMessage ? (
                    <div className="mt-6 rounded-2xl border border-hm-accent-border bg-hm-accent-soft p-4 text-sm text-hm-accent">
                        {bookingMessage}
                    </div>
                ) : null}
            </div>

            {loading ? (
                <p className="text-sm text-hm-fg-subtle">Loading doctors...</p>
            ) : (
                <div className="grid gap-6 lg:grid-cols-[0.95fr_1.05fr]">
                    <div className="space-y-3">
                        {doctors.length === 0 ? (
                            <p className="rounded-3xl border border-hm-border bg-hm-surface p-5 text-sm text-hm-fg-subtle">
                                No doctors match these filters.
                            </p>
                        ) : (
                            doctors.map((doctor) => {
                                const active = doctor.id === selectedDoctorId;

                                return (
                                    <button
                                        className={`w-full rounded-3xl border p-5 text-left transition ${
                                            active
                                                ? 'border-hm-accent bg-hm-accent-soft'
                                                : 'border-hm-border bg-hm-surface hover:border-hm-border-strong'
                                        }`}
                                        key={doctor.id}
                                        onClick={() => {
                                            setSelectedDoctorId(doctor.id);
                                            setSelectedSlotId(doctor.available_slots[0]?.id ?? null);
                                            setBookingMessage(null);
                                        }}
                                        type="button"
                                    >
                                        <div className="flex items-start justify-between gap-3">
                                            <div>
                                                <h3 className="text-lg font-semibold text-hm-fg">
                                                    {doctor.name ?? `Doctor #${doctor.id}`}
                                                </h3>
                                                <p className="mt-1 text-sm text-hm-fg-muted">
                                                    {doctor.specialization}
                                                </p>
                                            </div>
                                            <span className="rounded-full border border-hm-border-strong px-3 py-1 text-xs text-hm-fg-muted">
                                                {doctor.rating.toFixed(1)} / 5
                                            </span>
                                        </div>
                                        <p className="mt-3 flex flex-wrap items-center gap-2 text-xs uppercase tracking-wide text-hm-fg-subtle">
                                            <span>Card access</span>
                                            <StatusBadge
                                                tone={accessStatusTone(doctor.card_access_status)}
                                                value={doctor.card_access_status ?? 'not granted'}
                                            />
                                        </p>
                                    </button>
                                );
                            })
                        )}

                        <div className="flex flex-col gap-3 rounded-3xl border border-hm-border bg-hm-surface px-4 py-4 text-sm text-hm-fg-subtle sm:flex-row sm:items-center sm:justify-between">
                            <p>
                                Page {meta.current_page} of {Math.max(meta.last_page, 1)} ·{' '}
                                {meta.total} doctors
                            </p>
                            <div className="flex gap-2">
                                <button
                                    className="rounded-2xl border border-hm-border-strong px-4 py-2 font-semibold text-hm-fg hover:bg-hm-surface-3 disabled:cursor-not-allowed disabled:opacity-40"
                                    disabled={loading || meta.current_page <= 1}
                                    onClick={() => setPage((current) => Math.max(current - 1, 1))}
                                    type="button"
                                >
                                    Previous
                                </button>
                                <button
                                    className="rounded-2xl border border-hm-border-strong px-4 py-2 font-semibold text-hm-fg hover:bg-hm-surface-3 disabled:cursor-not-allowed disabled:opacity-40"
                                    disabled={loading || meta.current_page >= meta.last_page}
                                    onClick={() =>
                                        setPage((current) => Math.min(current + 1, meta.last_page))
                                    }
                                    type="button"
                                >
                                    Next
                                </button>
                            </div>
                        </div>
                    </div>

                    <div className="rounded-3xl border border-hm-border bg-hm-surface p-6">
                        {!selectedDoctor ? (
                            <p className="text-sm text-hm-fg-subtle">
                                Select a doctor to learn more, manage card access, and book.
                            </p>
                        ) : (
                            <>
                                <h3 className="text-2xl font-semibold text-hm-fg">
                                    {selectedDoctor.name ?? `Doctor #${selectedDoctor.id}`}
                                </h3>
                                <p className="mt-2 text-sm text-hm-fg-muted">
                                    {selectedDoctor.specialization} ·{' '}
                                    {selectedDoctor.years_of_experience} years experience · rating{' '}
                                    {selectedDoctor.rating.toFixed(1)}
                                </p>
                                <div className="mt-3">
                                    <StatusBadge
                                        tone={accessStatusTone(selectedDoctor.card_access_status)}
                                        value={
                                            selectedDoctor.card_access_status ?? 'not granted'
                                        }
                                    />
                                </div>
                                <p className="mt-4 text-sm leading-6 text-hm-fg-subtle">
                                    {selectedDoctor.bio ?? 'No biography provided yet.'}
                                </p>

                                <div className="mt-6 flex flex-wrap gap-3">
                                    {selectedDoctor.card_access_status === 'approved' ? (
                                        <button
                                            className="rounded-2xl border border-hm-danger-border px-4 py-2 text-sm font-semibold text-hm-danger-fg disabled:opacity-60"
                                            disabled={busyDoctorId === selectedDoctor.id}
                                            onClick={() =>
                                                void updateAccess(selectedDoctor.id, 'revoke')
                                            }
                                            type="button"
                                        >
                                            Revoke card access
                                        </button>
                                    ) : (
                                        <button
                                            className="rounded-2xl border border-hm-accent-border px-4 py-2 text-sm font-semibold text-hm-accent disabled:opacity-60"
                                            disabled={busyDoctorId === selectedDoctor.id}
                                            onClick={() =>
                                                void updateAccess(selectedDoctor.id, 'approve')
                                            }
                                            type="button"
                                        >
                                            Approve card access
                                        </button>
                                    )}
                                </div>

                                <div className="mt-8">
                                    <h4 className="text-lg font-semibold text-hm-fg">
                                        Book an appointment
                                    </h4>
                                    <p className="mt-2 text-sm text-hm-fg-subtle">
                                        Request stays pending until the doctor approves and a Meet
                                        link is created.
                                    </p>

                                    <div className="mt-4 space-y-2">
                                        {selectedDoctor.available_slots.length === 0 ? (
                                            <p className="text-sm text-hm-fg-subtle">
                                                No open slots in the next year.
                                            </p>
                                        ) : (
                                            selectedDoctor.available_slots.map((slot) => {
                                                const active = slot.id === selectedSlotId;

                                                return (
                                                    <button
                                                        className={`w-full rounded-2xl border px-4 py-3 text-left text-sm ${
                                                            active
                                                                ? 'border-hm-accent bg-hm-accent-soft text-hm-accent'
                                                                : 'border-hm-border-strong text-hm-fg-muted hover:bg-hm-surface-3'
                                                        }`}
                                                        key={slot.id}
                                                        onClick={() => setSelectedSlotId(slot.id)}
                                                        type="button"
                                                    >
                                                        {formatDate(slot.starts_at)}
                                                    </button>
                                                );
                                            })
                                        )}
                                    </div>

                                    <textarea
                                        className="mt-4 min-h-28 w-full rounded-2xl border border-hm-border-strong bg-hm-surface-2 px-4 py-3 text-sm text-hm-fg outline-none focus:border-hm-accent"
                                        onChange={(event) => setSymptoms(event.target.value)}
                                        placeholder="Briefly describe why you need this visit..."
                                        value={symptoms}
                                    />
                                    <button
                                        className="mt-4 rounded-2xl bg-hm-accent px-5 py-3 text-sm font-semibold text-hm-accent-fg transition hover:bg-hm-accent-hover disabled:opacity-60"
                                        disabled={
                                            submitting ||
                                            !selectedSlotId ||
                                            symptoms.trim().length < 10
                                        }
                                        onClick={() => void bookAppointment()}
                                        type="button"
                                    >
                                        {submitting
                                            ? 'Requesting...'
                                            : 'Request pending appointment'}
                                    </button>
                                </div>
                            </>
                        )}
                    </div>
                </div>
            )}
        </div>
    );
}

function MyBookingsPage({
    bookings,
    loading,
    error,
    onRefresh,
}: {
    bookings: BookingConfirmation[];
    loading: boolean;
    error: string | null;
    onRefresh: () => void;
}) {
    return (
        <div className="rounded-3xl border border-hm-border bg-hm-surface p-6">
            <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p className="text-sm font-semibold uppercase tracking-[0.3em] text-hm-accent">
                        My bookings
                    </p>
                    <h2 className="mt-2 text-2xl font-semibold text-hm-fg">Your appointments</h2>
                    <p className="mt-2 text-sm text-hm-fg-muted">
                        Review pending and past bookings created from your intake flow.
                    </p>
                </div>
                <button
                    className="rounded-2xl border border-hm-border-strong px-5 py-3 text-sm font-semibold text-hm-fg hover:bg-hm-surface-3"
                    onClick={onRefresh}
                    type="button"
                >
                    Refresh
                </button>
            </div>

            {error ? (
                <div className="mt-6 rounded-2xl border border-hm-danger-border bg-hm-danger-bg p-4 text-sm text-hm-danger-fg">
                    {error}
                </div>
            ) : null}

            {loading ? (
                <p className="mt-6 text-sm text-hm-fg-subtle">Loading bookings...</p>
            ) : bookings.length === 0 ? (
                <p className="mt-6 text-sm text-hm-fg-subtle">
                    No bookings yet. Create one from the Book appointment tab.
                </p>
            ) : (
                <div className="mt-6 space-y-4">
                    {bookings.map((booking) => (
                        <article
                            className="rounded-2xl border border-hm-border bg-hm-surface-2 p-5"
                            key={booking.id}
                        >
                            <div className="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                <div>
                                    <h3 className="text-lg font-semibold text-hm-fg">
                                        Appointment #{booking.id}
                                    </h3>
                                    <p className="mt-1 text-sm text-hm-fg-muted">
                                        {booking.doctor ?? 'Doctor pending'} · {booking.specialization}
                                    </p>
                                </div>
                                <StatusBadge value={booking.status} />
                            </div>

                            <div className="mt-4 grid gap-3 text-sm text-hm-fg-muted md:grid-cols-3">
                                <div>
                                    <p className="text-hm-fg-subtle">Slot</p>
                                    <p className="mt-1 text-hm-fg">{formatDate(booking.slot)}</p>
                                </div>
                                <div>
                                    <p className="text-hm-fg-subtle">Urgency</p>
                                    <div className="mt-1">
                                        <UrgencyBadge level={booking.urgency_level} />
                                    </div>
                                </div>
                                <div>
                                    <p className="text-hm-fg-subtle">Chief complaint</p>
                                    <p className="mt-1 text-hm-fg">
                                        {booking.chief_complaint ?? 'Not provided'}
                                    </p>
                                </div>
                            </div>

                            {booking.clinical_summary ? (
                                <p className="mt-4 text-sm leading-6 text-hm-fg-subtle">
                                    {booking.clinical_summary}
                                </p>
                            ) : null}

                            {booking.google_meet_url ? (
                                <a
                                    className="mt-4 inline-block text-sm font-semibold text-hm-accent hover:text-hm-accent-hover"
                                    href={booking.google_meet_url}
                                    rel="noreferrer"
                                    target="_blank"
                                >
                                    Join Google Meet
                                </a>
                            ) : booking.status === 'pending' ? (
                                <p className="mt-4 text-sm text-hm-warning-fg">
                                    Waiting for doctor approval. Google Meet appears after confirmation.
                                </p>
                            ) : null}
                        </article>
                    ))}
                </div>
            )}
        </div>
    );
}

function SuperAdminAuditLogsPage({
    session,
    onLogout,
}: {
    session: NonNullable<SessionPayload['user']>;
    onLogout: () => Promise<void>;
}) {
    const [logs, setLogs] = useState<AuditLogEntry[]>([]);
    const [page, setPage] = useState(1);
    const [meta, setMeta] = useState<PaginationMeta>({
        current_page: 1,
        last_page: 1,
        per_page: 25,
        total: 0,
    });
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState<string | null>(null);

    const loadLogs = async (pageNumber = page) => {
        setLoading(true);
        setError(null);

        try {
            const result = await requestPaginated<AuditLogEntry[]>(
                `/api/v1/audit-logs?page=${pageNumber}&per_page=25`,
            );
            setLogs(result.data ?? []);
            setMeta(result.meta);
            setPage(result.meta.current_page);
        } catch (err) {
            setError(err instanceof Error ? err.message : 'Failed to load audit logs.');
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        void loadLogs(page);
        // eslint-disable-next-line react-hooks/exhaustive-deps -- reload when page changes
    }, [page]);

    return (
        <div className="flex h-dvh min-h-0 flex-col bg-hm-bg text-hm-fg">
            <header className="flex shrink-0 flex-col gap-2 border-b border-hm-border px-3 py-2 sm:flex-row sm:items-center sm:justify-between">
                <div className="flex min-w-0 items-center gap-3">
                    <BrandLogo className="h-8 w-8" variant="mark" />
                    <UserAvatar avatar={session.avatar} name={session.name} size="sm" />
                    <div className="min-w-0">
                        <p className="text-xs uppercase tracking-[0.25em] text-hm-accent">HIPAA Audit</p>
                        <h1 className="truncate text-lg font-semibold text-hm-fg">Compliance logs</h1>
                        <p className="truncate text-xs text-hm-fg-subtle">
                            {session.name} ({session.email}) · no raw PHI
                        </p>
                    </div>
                </div>
                <div className="flex shrink-0 gap-2">
                    <button
                        className="rounded-lg border border-hm-border-strong px-3 py-1.5 text-sm font-semibold text-hm-fg hover:bg-hm-surface-3"
                        onClick={() => void loadLogs(page)}
                        type="button"
                    >
                        Refresh
                    </button>
                    <ThemeToggle />
                    <button
                        className="rounded-lg border border-hm-border-strong px-3 py-1.5 text-sm font-semibold text-hm-fg hover:bg-hm-surface-3"
                        onClick={() => void onLogout()}
                        type="button"
                    >
                        Sign out
                    </button>
                </div>
            </header>

            <div className="flex min-h-0 flex-1 flex-col">
                {error ? (
                    <div className="border-b border-hm-danger-border bg-hm-danger-bg px-3 py-2 text-sm text-hm-danger-fg">
                        {error}
                    </div>
                ) : null}

                <div className="min-h-0 flex-1 overflow-auto">
                    {loading ? (
                        <p className="px-3 py-3 text-sm text-hm-fg-subtle">Loading audit logs...</p>
                    ) : logs.length === 0 ? (
                        <p className="px-3 py-3 text-sm text-hm-fg-subtle">No audit events recorded yet.</p>
                    ) : (
                        <table className="min-w-full text-left text-sm text-hm-fg-muted">
                            <thead className="sticky top-0 border-b border-hm-border bg-hm-bg text-xs uppercase tracking-wide text-hm-fg-subtle">
                                <tr>
                                    <th className="px-3 py-2 font-medium">When</th>
                                    <th className="px-3 py-2 font-medium">Action</th>
                                    <th className="px-3 py-2 font-medium">Outcome</th>
                                    <th className="px-3 py-2 font-medium">Actor</th>
                                    <th className="px-3 py-2 font-medium">PHI</th>
                                    <th className="px-3 py-2 font-medium">Metadata</th>
                                </tr>
                            </thead>
                            <tbody>
                                {logs.map((log) => (
                                    <tr className="border-b border-hm-border" key={log.id}>
                                        <td className="whitespace-nowrap px-3 py-2 text-hm-fg-subtle">
                                            {log.created_at
                                                ? formatDate(log.created_at)
                                                : '—'}
                                        </td>
                                        <td className="px-3 py-2 font-medium text-hm-fg">
                                            {log.action}
                                        </td>
                                        <td className="px-3 py-2">
                                            <StatusBadge value={log.outcome} />
                                        </td>
                                        <td className="px-3 py-2">
                                            {log.actor
                                                ? `${log.actor.name} (#${log.actor.id})`
                                                : 'system'}
                                        </td>
                                        <td className="px-3 py-2">
                                            {log.phi_involved ? 'yes' : 'no'}
                                        </td>
                                        <td className="max-w-xl truncate px-3 py-2 font-mono text-xs text-hm-fg-subtle">
                                            {log.metadata ? JSON.stringify(log.metadata) : '—'}
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    )}
                </div>

                <div className="flex shrink-0 flex-col gap-2 border-t border-hm-border px-3 py-2 text-sm text-hm-fg-subtle sm:flex-row sm:items-center sm:justify-between">
                    <p>
                        Page {meta.current_page} of {Math.max(meta.last_page, 1)} · {meta.total}{' '}
                        total
                    </p>
                    <div className="flex gap-2">
                        <button
                            className="rounded-lg border border-hm-border-strong px-3 py-1.5 font-semibold text-hm-fg hover:bg-hm-surface-3 disabled:cursor-not-allowed disabled:opacity-40"
                            disabled={loading || meta.current_page <= 1}
                            onClick={() => setPage((current) => Math.max(current - 1, 1))}
                            type="button"
                        >
                            Previous
                        </button>
                        <button
                            className="rounded-lg border border-hm-border-strong px-3 py-1.5 font-semibold text-hm-fg hover:bg-hm-surface-3 disabled:cursor-not-allowed disabled:opacity-40"
                            disabled={loading || meta.current_page >= meta.last_page}
                            onClick={() =>
                                setPage((current) => Math.min(current + 1, meta.last_page))
                            }
                            type="button"
                        >
                            Next
                        </button>
                    </div>
                </div>
            </div>
        </div>
    );
}

function App() {
    const [session, setSession] = useState<SessionPayload | null>(null);
    const [sessionLoading, setSessionLoading] = useState(true);
    const [page, setPage] = useState<PatientPage>('intake');
    const [symptoms, setSymptoms] = useState('');
    const [triage, setTriage] = useState<TriageSummary | null>(null);
    const [matches, setMatches] = useState<DoctorMatch[]>([]);
    const [selectedDoctorId, setSelectedDoctorId] = useState<number | null>(null);
    const [selectedSlotId, setSelectedSlotId] = useState<number | null>(null);
    const [booking, setBooking] = useState<BookingConfirmation | null>(null);
    const [bookings, setBookings] = useState<BookingConfirmation[]>([]);
    const [bookingsLoading, setBookingsLoading] = useState(false);
    const [bookingsError, setBookingsError] = useState<string | null>(null);
    const [submitting, setSubmitting] = useState(false);
    const [error, setError] = useState<string | null>(null);

    const selectedDoctor = useMemo(
        () => matches.find((doctor) => doctor.id === selectedDoctorId) ?? null,
        [matches, selectedDoctorId],
    );
    const selectedSlot = useMemo(
        () =>
            selectedDoctor?.available_slots.find((slot) => slot.id === selectedSlotId) ?? null,
        [selectedDoctor, selectedSlotId],
    );

    async function loadBookings() {
        setBookingsLoading(true);
        setBookingsError(null);

        try {
            const data = await request<BookingConfirmation[]>('/api/v1/appointments');
            setBookings(data);
        } catch (loadError) {
            setBookingsError(
                loadError instanceof Error ? loadError.message : 'Unable to load bookings.',
            );
        } finally {
            setBookingsLoading(false);
        }
    }

    useEffect(() => {
        let cancelled = false;

        void (async () => {
            try {
                const data = await request<SessionPayload>('/api/v1/auth/session');

                if (!cancelled) {
                    setSession(data);
                }
            } catch (loadError) {
                if (!cancelled) {
                    setError(
                        loadError instanceof Error ? loadError.message : 'Unable to load session.',
                    );
                }
            } finally {
                if (!cancelled) {
                    setSessionLoading(false);
                }
            }
        })();

        return () => {
            cancelled = true;
        };
    }, []);

    useEffect(() => {
        if (!session?.authenticated || session.user?.role !== 'patient' || page !== 'bookings') {
            return;
        }

        let cancelled = false;

        void (async () => {
            try {
                const data = await request<BookingConfirmation[]>('/api/v1/appointments');

                if (!cancelled) {
                    setBookings(data);
                    setBookingsError(null);
                }
            } catch (loadError) {
                if (!cancelled) {
                    setBookingsError(
                        loadError instanceof Error
                            ? loadError.message
                            : 'Unable to load bookings.',
                    );
                }
            } finally {
                if (!cancelled) {
                    setBookingsLoading(false);
                }
            }
        })();

        return () => {
            cancelled = true;
        };
    }, [session?.authenticated, session?.user?.role, page]);

    async function handleLogout() {
        setError(null);

        try {
            await request('/api/v1/auth/logout', {
                method: 'POST',
            });
            setSession({ authenticated: false, user: null });
            setTriage(null);
            setMatches([]);
            setSelectedDoctorId(null);
            setSelectedSlotId(null);
            setBooking(null);
            setBookings([]);
            setPage('intake');
            window.history.replaceState({}, '', '/');
        } catch (logoutError) {
            setError(logoutError instanceof Error ? logoutError.message : 'Unable to sign out.');
        }
    }

    async function handlePasswordLogin(email: string, password: string) {
        const data = await request<SessionPayload>('/api/v1/auth/login', {
            method: 'POST',
            body: JSON.stringify({ email, password }),
        });

        setSession(data);
        window.history.replaceState({}, '', '/');
    }

    async function handleAnalyzeAndMatch(event: React.FormEvent<HTMLFormElement>) {
        event.preventDefault();
        setSubmitting(true);
        setError(null);
        setBooking(null);
        setSelectedDoctorId(null);
        setSelectedSlotId(null);

        try {
            const [triageData, matchData] = await Promise.all([
                request<TriageSummary>('/api/v1/triage/analyze', {
                    method: 'POST',
                    body: JSON.stringify({ symptoms }),
                }),
                request<DoctorMatch[]>('/api/v1/doctors/match', {
                    method: 'POST',
                    body: JSON.stringify({ symptoms, limit: 3 }),
                }),
            ]);

            setTriage(triageData);
            setMatches(matchData);
        } catch (submitError) {
            setError(
                submitError instanceof Error ? submitError.message : 'Unable to analyze symptoms.',
            );
        } finally {
            setSubmitting(false);
        }
    }

    async function handleBooking() {
        if (!triage || !selectedDoctor || !selectedSlot) {
            return;
        }

        setSubmitting(true);
        setError(null);

        try {
            const data = await request<BookingConfirmation>('/api/v1/appointments', {
                method: 'POST',
                body: JSON.stringify({
                    doctor_id: selectedDoctor.id,
                    doctor_availability_slot_id: selectedSlot.id,
                    symptoms,
                    chief_complaint: triage.chief_complaint,
                    clinical_summary: triage.clinical_summary,
                    urgency_level: triage.urgency_level,
                }),
            });

            setBooking(data);
            setBookingsLoading(true);
            setPage('bookings');
        } catch (bookingError) {
            setError(
                bookingError instanceof Error ? bookingError.message : 'Unable to create booking.',
            );
        } finally {
            setSubmitting(false);
        }
    }

    if (sessionLoading) {
        return (
            <main className="flex min-h-screen flex-col items-center justify-center gap-4 bg-hm-bg px-6 text-hm-fg-muted">
                <BrandLogo variant="wordmark" />
                <p>Loading your workspace...</p>
            </main>
        );
    }

    if (!session?.authenticated || !session.user) {
        return (
            <main className="min-h-screen bg-hm-bg px-6 py-16 text-hm-fg">
                <div className="mx-auto flex min-h-[80vh] max-w-6xl items-center justify-center">
                    <AuthScreen onPasswordLogin={handlePasswordLogin} />
                </div>
            </main>
        );
    }

    if (session.user.role === 'super_admin') {
        return (
            <SuperAdminAuditLogsPage
                onLogout={handleLogout}
                session={session.user}
            />
        );
    }

    if (session.user.role === 'doctor') {
        return (
            <main className="min-h-screen bg-hm-bg px-6 py-12 text-hm-fg">
                <DoctorDashboard
                    onLogout={handleLogout}
                    session={session.user}
                />
            </main>
        );
    }

    return (
        <main className="min-h-screen bg-hm-bg px-6 py-12 text-hm-fg">
            <section className="mx-auto max-w-6xl">
                <div className="flex flex-col gap-4 rounded-3xl border border-hm-border bg-hm-surface p-6 md:flex-row md:items-center md:justify-between">
                    <div className="flex items-start gap-4">
                        <UserAvatar avatar={session.user.avatar} name={session.user.name} />
                        <div>
                            <BrandLogo className="h-9" variant="wordmark" />
                            <h1 className="mt-3 text-3xl font-semibold text-hm-fg">
                                {page === 'bookings'
                                    ? 'My bookings'
                                    : page === 'card'
                                      ? 'My patient card'
                                      : page === 'doctors'
                                        ? 'Doctors'
                                        : 'Intake and matching'}
                            </h1>
                            <p className="mt-2 text-sm text-hm-fg-muted">
                                Signed in as {session.user.name} ({session.user.email})
                            </p>
                        </div>
                    </div>
                    <div className="flex flex-wrap gap-3">
                        <button
                            className={`rounded-2xl px-5 py-3 text-sm font-semibold ${
                                page === 'intake'
                                    ? 'bg-hm-accent text-hm-accent-fg'
                                    : 'border border-hm-border-strong text-hm-fg hover:bg-hm-surface-3'
                            }`}
                            onClick={() => setPage('intake')}
                            type="button"
                        >
                            Symptom match
                        </button>
                        <button
                            className={`rounded-2xl px-5 py-3 text-sm font-semibold ${
                                page === 'doctors'
                                    ? 'bg-hm-accent text-hm-accent-fg'
                                    : 'border border-hm-border-strong text-hm-fg hover:bg-hm-surface-3'
                            }`}
                            onClick={() => setPage('doctors')}
                            type="button"
                        >
                            Doctors
                        </button>
                        <button
                            className={`rounded-2xl px-5 py-3 text-sm font-semibold ${
                                page === 'bookings'
                                    ? 'bg-hm-accent text-hm-accent-fg'
                                    : 'border border-hm-border-strong text-hm-fg hover:bg-hm-surface-3'
                            }`}
                            onClick={() => {
                                setPage('bookings');
                                setBookingsLoading(true);
                            }}
                            type="button"
                        >
                            My bookings
                        </button>
                        <button
                            className={`rounded-2xl px-5 py-3 text-sm font-semibold ${
                                page === 'card'
                                    ? 'bg-hm-accent text-hm-accent-fg'
                                    : 'border border-hm-border-strong text-hm-fg hover:bg-hm-surface-3'
                            }`}
                            onClick={() => setPage('card')}
                            type="button"
                        >
                            My card
                        </button>
                        <button
                            className="rounded-2xl border border-hm-border-strong px-5 py-3 text-sm font-semibold text-hm-fg hover:bg-hm-surface-3"
                            onClick={() => void handleLogout()}
                            type="button"
                        >
                            Sign out
                        </button>
                        <ThemeToggle />
                    </div>
                </div>

                {page === 'bookings' ? (
                    <div className="mt-8">
                        <MyBookingsPage
                            bookings={bookings}
                            error={bookingsError}
                            loading={bookingsLoading}
                            onRefresh={() => void loadBookings()}
                        />
                    </div>
                ) : page === 'card' ? (
                    <div className="mt-8">
                        <PatientCardPage />
                    </div>
                ) : page === 'doctors' ? (
                    <div className="mt-8">
                        <DoctorsPage />
                    </div>
                ) : (
                    <>
                <div className="mt-8 grid gap-6 lg:grid-cols-[1.1fr_0.9fr]">
                    <form
                        className="rounded-3xl border border-hm-border bg-hm-surface p-6"
                        onSubmit={(event) => void handleAnalyzeAndMatch(event)}
                    >
                        <p className="text-sm font-semibold uppercase tracking-[0.3em] text-hm-accent">
                            Step 1
                        </p>
                        <h2 className="mt-2 text-2xl font-semibold text-hm-fg">
                            Describe your symptoms
                        </h2>
                        <p className="mt-3 text-sm leading-6 text-hm-fg-muted">
                            Write in natural language. HealMe will generate a structured Gemini
                            summary and recommend matching doctors with the nearest open slots.
                        </p>

                        <textarea
                            className="mt-6 min-h-48 w-full rounded-2xl border border-hm-border-strong bg-hm-surface-2 px-4 py-3 text-sm text-hm-fg outline-none ring-0 placeholder:text-hm-fg-subtle focus:border-hm-accent"
                            onChange={(event) => setSymptoms(event.target.value)}
                            placeholder="Example: I have had migraines with aura and severe light sensitivity for four days..."
                            value={symptoms}
                        />

                        <button
                            className="mt-5 rounded-2xl bg-hm-accent px-5 py-3 text-sm font-semibold text-hm-accent-fg transition hover:bg-hm-accent-hover disabled:cursor-not-allowed disabled:opacity-60"
                            disabled={submitting || symptoms.trim().length < 10}
                            type="submit"
                        >
                            {submitting ? 'Analyzing...' : 'Analyze symptoms and find doctors'}
                        </button>

                        {error ? (
                            <div className="mt-4 rounded-2xl border border-hm-danger-border bg-hm-danger-bg p-4 text-sm text-hm-danger-fg">
                                {error}
                            </div>
                        ) : null}
                    </form>

                    <div className="rounded-3xl border border-hm-border bg-hm-surface p-6">
                        <p className="text-sm font-semibold uppercase tracking-[0.3em] text-hm-accent">
                            Step 2
                        </p>
                        <h2 className="mt-2 text-2xl font-semibold text-hm-fg">Triage summary</h2>

                        {triage ? (
                            <div className="mt-5 space-y-4 text-sm text-hm-fg-muted">
                                <div className="rounded-2xl border border-hm-border bg-hm-surface-2 p-4">
                                    <p className="text-hm-fg-subtle">Chief complaint</p>
                                    <p className="mt-1 font-medium text-hm-fg">
                                        {triage.chief_complaint}
                                    </p>
                                </div>
                                <div className="grid gap-4 sm:grid-cols-2">
                                    <div className="rounded-2xl border border-hm-border bg-hm-surface-2 p-4">
                                        <p className="text-hm-fg-subtle">Duration</p>
                                        <p className="mt-1 font-medium text-hm-fg">
                                            {triage.duration}
                                        </p>
                                    </div>
                                    <div className="rounded-2xl border border-hm-border bg-hm-surface-2 p-4">
                                        <p className="text-hm-fg-subtle">Urgency</p>
                                        <div className="mt-2">
                                            <UrgencyBadge level={triage.urgency_level} />
                                        </div>
                                    </div>
                                </div>
                                <div className="rounded-2xl border border-hm-border bg-hm-surface-2 p-4">
                                    <p className="text-hm-fg-subtle">Recommended specialties</p>
                                    <div className="mt-2 flex flex-wrap gap-2">
                                        {triage.recommended_specialties.map((specialty) => (
                                            <span
                                                className="rounded-full border border-hm-accent-border bg-hm-accent-soft px-3 py-1 text-xs text-hm-accent"
                                                key={specialty}
                                            >
                                                {specialty}
                                            </span>
                                        ))}
                                    </div>
                                </div>
                                <div className="rounded-2xl border border-hm-border bg-hm-surface-2 p-4">
                                    <p className="text-hm-fg-subtle">Clinical summary</p>
                                    <p className="mt-1 leading-6 text-hm-fg-muted">
                                        {triage.clinical_summary}
                                    </p>
                                </div>
                            </div>
                        ) : (
                            <p className="mt-5 text-sm leading-6 text-hm-fg-subtle">
                                Your structured symptom summary will appear here after analysis.
                            </p>
                        )}
                    </div>
                </div>

                <div className="mt-8 rounded-3xl border border-hm-border bg-hm-surface p-6">
                    <p className="text-sm font-semibold uppercase tracking-[0.3em] text-hm-accent">
                        Step 3
                    </p>
                    <h2 className="mt-2 text-2xl font-semibold text-hm-fg">
                        Choose a doctor and available slot
                    </h2>

                    {matches.length > 0 ? (
                        <div className="mt-5 grid gap-4 lg:grid-cols-3">
                            {matches.map((doctor) => {
                                const isSelected = doctor.id === selectedDoctorId;

                                return (
                                    <article
                                        className={`rounded-2xl border p-5 transition ${
                                            isSelected
                                                ? 'border-hm-accent bg-hm-accent-soft'
                                                : 'border-hm-border bg-hm-surface-2'
                                        }`}
                                        key={doctor.id}
                                    >
                                        <div className="flex items-start justify-between gap-3">
                                            <div>
                                                <h3 className="text-lg font-semibold text-hm-fg">
                                                    {doctor.name ?? 'Doctor profile'}
                                                </h3>
                                                <p className="mt-1 text-sm text-hm-fg-muted">
                                                    {doctor.specialization}
                                                </p>
                                            </div>
                                            <div className="rounded-full border border-hm-border-strong px-3 py-1 text-xs text-hm-fg-muted">
                                                {Math.round(doctor.match_score * 100)}% match
                                            </div>
                                        </div>

                                        <div className="mt-4 space-y-2 text-sm text-hm-fg-muted">
                                            <p>Rating: {doctor.rating.toFixed(1)} / 5.0</p>
                                            <p>
                                                Experience: {doctor.years_of_experience} years
                                            </p>
                                            <p>
                                                Nearest slot:{' '}
                                                {formatDate(doctor.next_available_slot)}
                                            </p>
                                        </div>

                                        <button
                                            className={`mt-5 w-full rounded-2xl px-4 py-3 text-sm font-semibold ${
                                                isSelected
                                                    ? 'bg-hm-accent text-hm-accent-fg'
                                                    : 'border border-hm-border-strong text-hm-fg hover:bg-hm-surface-3'
                                            }`}
                                            onClick={() => {
                                                setSelectedDoctorId(doctor.id);
                                                setSelectedSlotId(doctor.available_slots[0]?.id ?? null);
                                            }}
                                            type="button"
                                        >
                                            {isSelected ? 'Selected' : 'Select doctor'}
                                        </button>

                                        {isSelected ? (
                                            <div className="mt-5 space-y-2">
                                                {doctor.available_slots.map((slot) => {
                                                    const active = slot.id === selectedSlotId;

                                                    return (
                                                        <button
                                                            className={`w-full rounded-2xl border px-4 py-3 text-left text-sm ${
                                                                active
                                                                    ? 'border-hm-accent bg-hm-accent-soft text-hm-accent'
                                                                    : 'border-hm-border-strong text-hm-fg-muted hover:bg-hm-surface-3'
                                                            }`}
                                                            key={slot.id}
                                                            onClick={() => setSelectedSlotId(slot.id)}
                                                            type="button"
                                                        >
                                                            {formatDate(slot.starts_at)}
                                                        </button>
                                                    );
                                                })}
                                            </div>
                                        ) : null}
                                    </article>
                                );
                            })}
                        </div>
                    ) : (
                        <p className="mt-5 text-sm text-hm-fg-subtle">
                            Matching doctors will appear here after symptom analysis.
                        </p>
                    )}
                </div>

                <div className="mt-8 rounded-3xl border border-hm-accent-border bg-hm-accent-soft p-6">
                    <p className="text-sm font-semibold uppercase tracking-[0.3em] text-hm-accent">
                        Step 4
                    </p>
                    <h2 className="mt-2 text-2xl font-semibold text-hm-fg">
                        Confirm pending appointment
                    </h2>

                    <div className="mt-5 grid gap-4 md:grid-cols-2">
                        <div className="rounded-2xl border border-hm-border bg-hm-surface-2 p-4 text-sm text-hm-fg-muted">
                            <p className="text-hm-fg-subtle">Doctor</p>
                            <p className="mt-1 text-hm-fg">
                                {selectedDoctor?.name ?? 'No doctor selected'}
                            </p>
                            <p className="mt-4 text-hm-fg-subtle">Slot</p>
                            <p className="mt-1 text-hm-fg">
                                {formatDate(selectedSlot?.starts_at ?? null)}
                            </p>
                        </div>

                        <div className="rounded-2xl border border-hm-border bg-hm-surface-2 p-4 text-sm text-hm-fg-muted">
                            <p className="text-hm-fg-subtle">Booking status</p>
                            {booking ? (
                                <div className="mt-1">
                                    <p className="flex flex-wrap items-center gap-2 font-medium text-hm-fg">
                                        <span>Appointment #{booking.id}</span>
                                        <StatusBadge value={booking.status} />
                                    </p>
                                    <p className="mt-2">
                                        {booking.doctor} · {booking.specialization}
                                    </p>
                                    <p className="mt-2">{formatDate(booking.slot)}</p>
                                </div>
                            ) : (
                                <p className="mt-1 text-hm-fg">
                                    No pending appointment created yet.
                                </p>
                            )}
                        </div>
                    </div>

                    <button
                        className="mt-5 rounded-2xl bg-hm-accent px-5 py-3 text-sm font-semibold text-hm-accent-fg transition hover:bg-hm-accent-hover disabled:cursor-not-allowed disabled:opacity-60"
                        disabled={submitting || !triage || !selectedDoctor || !selectedSlot}
                        onClick={() => void handleBooking()}
                        type="button"
                    >
                        {submitting ? 'Saving booking...' : 'Request pending appointment'}
                    </button>
                </div>
                    </>
                )}
            </section>
        </main>
    );
}

const element = document.getElementById('app');

if (element) {
    createRoot(element).render(
        <React.StrictMode>
            <ThemeProvider>
                <App />
            </ThemeProvider>
        </React.StrictMode>,
    );
}
