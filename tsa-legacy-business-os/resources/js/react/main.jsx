import React, { useMemo, useState } from 'react';
import { createRoot } from 'react-dom/client';

const readProps = () => {
    const node = document.getElementById('tsa-react-props');
    if (!node) {
        return {};
    }

    try {
        return JSON.parse(node.textContent || '{}');
    } catch {
        return {};
    }
};

const formatCurrency = (value) => {
    const amount = Number(value || 0);
    return new Intl.NumberFormat('en-IN', {
        maximumFractionDigits: 0,
    }).format(amount);
};

const FieldError = ({ errors, name }) => {
    const message = errors?.[name]?.[0];
    if (!message) {
        return null;
    }

    return <p className="mt-1 text-xs text-rose-300">{message}</p>;
};

const AuthShell = ({ title, subtitle, children, accent }) => (
    <div className="space-y-5">
        <div className="space-y-2">
            <p className="text-[11px] font-bold uppercase tracking-[0.2em] text-cyan-300">{accent}</p>
            <h1 className="text-2xl font-black text-white">{title}</h1>
            <p className="text-sm text-slate-300">{subtitle}</p>
        </div>
        {children}
    </div>
);

const LoginPage = ({
    csrfToken,
    loginUrl,
    registerUrl,
    forgotPasswordUrl,
    pricingUrl,
    status,
    old = {},
    errors = {},
}) => {
    const [showPassword, setShowPassword] = useState(false);

    return (
        <div className="mx-auto grid w-full max-w-6xl overflow-hidden rounded-[28px] border border-white/15 bg-slate-900/70 shadow-[0_30px_120px_rgba(2,8,23,.7)] backdrop-blur lg:grid-cols-2">
            <section className="relative overflow-hidden bg-gradient-to-br from-cyan-600/35 via-slate-950 to-emerald-500/20 p-7 lg:p-10">
                <div className="pointer-events-none absolute -right-20 -top-16 h-56 w-56 rounded-full bg-cyan-300/20 blur-3xl" />
                <div className="pointer-events-none absolute -bottom-20 -left-20 h-64 w-64 rounded-full bg-emerald-300/20 blur-3xl" />

                <div className="relative">
                    <div className="inline-flex items-center gap-2 rounded-full border border-white/25 bg-white/10 px-3 py-1 text-xs font-bold uppercase tracking-[0.18em] text-cyan-100">
                        Enterprise Suite
                    </div>
                    <h1 className="mt-5 text-4xl font-black leading-tight text-white">Run your business core from one secure control room.</h1>
                    <p className="mt-4 max-w-md text-sm text-slate-200">
                        Track inventory, invoices, purchases, team activity, and subscription health with strict tenant isolation.
                    </p>

                    <div className="mt-7 grid grid-cols-3 gap-3">
                        <div className="rounded-xl border border-white/20 bg-white/10 px-3 py-2">
                            <p className="text-[10px] uppercase text-slate-300">Latency</p>
                            <p className="mt-1 text-lg font-black text-white">&lt;120ms</p>
                        </div>
                        <div className="rounded-xl border border-white/20 bg-white/10 px-3 py-2">
                            <p className="text-[10px] uppercase text-slate-300">Isolation</p>
                            <p className="mt-1 text-lg font-black text-white">Tenant</p>
                        </div>
                        <div className="rounded-xl border border-white/20 bg-white/10 px-3 py-2">
                            <p className="text-[10px] uppercase text-slate-300">Billing</p>
                            <p className="mt-1 text-lg font-black text-white">Verified</p>
                        </div>
                    </div>

                    <div className="mt-7 rounded-2xl border border-white/20 bg-slate-900/45 p-4">
                        <p className="text-xs font-semibold uppercase tracking-wide text-cyan-200">Why teams pick TSA Legacy</p>
                        <ul className="mt-3 space-y-2 text-sm text-slate-200">
                            <li className="flex items-start gap-2">
                                <span className="mt-1 h-2 w-2 rounded-full bg-emerald-300" />
                                Access control and audit visibility by default.
                            </li>
                            <li className="flex items-start gap-2">
                                <span className="mt-1 h-2 w-2 rounded-full bg-emerald-300" />
                                Billing and subscription lifecycle ready for SaaS scale.
                            </li>
                            <li className="flex items-start gap-2">
                                <span className="mt-1 h-2 w-2 rounded-full bg-emerald-300" />
                                Real-time operations data with role-safe workflows.
                            </li>
                        </ul>
                    </div>
                </div>
            </section>

            <section className="bg-slate-950/80 p-7 lg:p-10">
                <div className="mb-6 flex items-center justify-between gap-3">
                    <div>
                        <p className="text-[11px] font-extrabold uppercase tracking-[0.2em] text-cyan-300">Secure Sign In</p>
                        <h2 className="mt-2 text-3xl font-black text-white">Welcome Back</h2>
                    </div>
                    <span className="rounded-full border border-emerald-300/35 bg-emerald-400/10 px-3 py-1 text-[11px] font-bold text-emerald-200">
                        Protected Workspace
                    </span>
                </div>

                {status ? (
                    <div className="mb-5 rounded-xl border border-emerald-400/30 bg-emerald-400/10 px-4 py-3 text-xs text-emerald-200">
                        {status}
                    </div>
                ) : null}

                <form method="POST" action={loginUrl} className="space-y-4">
                    <input type="hidden" name="_token" value={csrfToken} />

                    <div>
                        <label htmlFor="email" className="text-xs font-semibold uppercase tracking-wide text-slate-200">
                            Work Email
                        </label>
                        <input
                            id="email"
                            type="email"
                            name="email"
                            defaultValue={old.email || ''}
                            autoComplete="username"
                            required
                            className="mt-1.5 w-full rounded-xl border border-white/20 bg-white/10 px-3 py-3 text-sm text-white outline-none ring-cyan-300/50 placeholder:text-slate-400 focus:ring"
                            placeholder="you@company.com"
                        />
                        <FieldError errors={errors} name="email" />
                    </div>

                    <div>
                        <div className="flex items-center justify-between">
                            <label htmlFor="password" className="text-xs font-semibold uppercase tracking-wide text-slate-200">
                                Password
                            </label>
                            {forgotPasswordUrl ? (
                                <a href={forgotPasswordUrl} className="text-xs font-semibold text-cyan-300 hover:text-cyan-200">
                                    Forgot password?
                                </a>
                            ) : null}
                        </div>
                        <div className="relative mt-1.5">
                            <input
                                id="password"
                                type={showPassword ? 'text' : 'password'}
                                name="password"
                                autoComplete="current-password"
                                required
                                className="w-full rounded-xl border border-white/20 bg-white/10 px-3 py-3 pr-24 text-sm text-white outline-none ring-cyan-300/50 placeholder:text-slate-400 focus:ring"
                                placeholder="Enter your password"
                            />
                            <button
                                type="button"
                                onClick={() => setShowPassword((value) => !value)}
                                className="absolute right-2 top-1/2 -translate-y-1/2 rounded-lg px-3 py-1.5 text-xs font-bold text-cyan-300 hover:bg-white/10"
                            >
                                {showPassword ? 'Hide' : 'Show'}
                            </button>
                        </div>
                        <FieldError errors={errors} name="password" />
                    </div>

                    <div className="flex flex-wrap items-center justify-between gap-3">
                        <label htmlFor="remember" className="flex items-center gap-2 text-sm text-slate-300">
                            <input
                                id="remember"
                                type="checkbox"
                                name="remember"
                                defaultChecked={Boolean(old.remember)}
                                className="rounded border-white/20 bg-white/10 text-cyan-500"
                            />
                            Keep me signed in
                        </label>
                        <p className="text-xs text-slate-400">MFA support for admin accounts</p>
                    </div>

                    <button
                        type="submit"
                        className="w-full rounded-xl bg-cyan-600 px-4 py-3 text-sm font-extrabold text-white transition hover:bg-cyan-500"
                    >
                        Sign In Securely
                    </button>
                </form>

                <div className="mt-5 grid grid-cols-1 gap-3 text-center text-xs font-semibold sm:grid-cols-2">
                    <a href={registerUrl} className="rounded-xl border border-white/20 px-3 py-2.5 text-cyan-300 hover:bg-white/10">
                        Create Account
                    </a>
                    <a href={pricingUrl} className="rounded-xl border border-white/20 px-3 py-2.5 text-cyan-300 hover:bg-white/10">
                        Compare Plans
                    </a>
                </div>
            </section>
        </div>
    );
};

const RegisterPage = ({
    csrfToken,
    registerUrl,
    loginUrl,
    pricingUrl,
    old = {},
    errors = {},
}) => {
    const [showPassword, setShowPassword] = useState(false);
    const [showConfirm, setShowConfirm] = useState(false);

    return (
        <AuthShell
            accent="SaaS Onboarding"
            title="Create Workspace Account"
            subtitle="Launch your ERP workspace in minutes with secure tenant setup."
        >
            <form method="POST" action={registerUrl} className="space-y-4">
                <input type="hidden" name="_token" value={csrfToken} />

                <div>
                    <label htmlFor="name" className="text-xs font-semibold uppercase tracking-wide text-slate-200">
                        Full Name
                    </label>
                    <input
                        id="name"
                        type="text"
                        name="name"
                        defaultValue={old.name || ''}
                        autoComplete="name"
                        required
                        className="mt-1 w-full rounded-xl border border-white/20 bg-white/10 px-3 py-2.5 text-sm text-white outline-none ring-emerald-300/40 placeholder:text-slate-400 focus:ring"
                        placeholder="Your full name"
                    />
                    <FieldError errors={errors} name="name" />
                </div>

                <div>
                    <label htmlFor="email" className="text-xs font-semibold uppercase tracking-wide text-slate-200">
                        Email
                    </label>
                    <input
                        id="email"
                        type="email"
                        name="email"
                        defaultValue={old.email || ''}
                        autoComplete="username"
                        required
                        className="mt-1 w-full rounded-xl border border-white/20 bg-white/10 px-3 py-2.5 text-sm text-white outline-none ring-emerald-300/40 placeholder:text-slate-400 focus:ring"
                        placeholder="you@company.com"
                    />
                    <FieldError errors={errors} name="email" />
                </div>

                <div>
                    <label htmlFor="phone" className="text-xs font-semibold uppercase tracking-wide text-slate-200">
                        Phone (Optional)
                    </label>
                    <input
                        id="phone"
                        type="text"
                        name="phone"
                        defaultValue={old.phone || ''}
                        autoComplete="tel"
                        className="mt-1 w-full rounded-xl border border-white/20 bg-white/10 px-3 py-2.5 text-sm text-white outline-none ring-emerald-300/40 placeholder:text-slate-400 focus:ring"
                        placeholder="+91-XXXXXXXXXX"
                    />
                    <FieldError errors={errors} name="phone" />
                </div>

                <div>
                    <label htmlFor="password" className="text-xs font-semibold uppercase tracking-wide text-slate-200">
                        Password
                    </label>
                    <div className="relative mt-1">
                        <input
                            id="password"
                            type={showPassword ? 'text' : 'password'}
                            name="password"
                            autoComplete="new-password"
                            required
                            className="w-full rounded-xl border border-white/20 bg-white/10 px-3 py-2.5 pr-20 text-sm text-white outline-none ring-emerald-300/40 placeholder:text-slate-400 focus:ring"
                            placeholder="Minimum 8 characters"
                        />
                        <button
                            type="button"
                            onClick={() => setShowPassword((value) => !value)}
                            className="absolute right-1 top-1 rounded-lg px-3 py-1.5 text-xs font-bold text-emerald-300 hover:bg-white/10"
                        >
                            {showPassword ? 'Hide' : 'Show'}
                        </button>
                    </div>
                    <FieldError errors={errors} name="password" />
                </div>

                <div>
                    <label htmlFor="password_confirmation" className="text-xs font-semibold uppercase tracking-wide text-slate-200">
                        Confirm Password
                    </label>
                    <div className="relative mt-1">
                        <input
                            id="password_confirmation"
                            type={showConfirm ? 'text' : 'password'}
                            name="password_confirmation"
                            autoComplete="new-password"
                            required
                            className="w-full rounded-xl border border-white/20 bg-white/10 px-3 py-2.5 pr-20 text-sm text-white outline-none ring-emerald-300/40 placeholder:text-slate-400 focus:ring"
                            placeholder="Retype password"
                        />
                        <button
                            type="button"
                            onClick={() => setShowConfirm((value) => !value)}
                            className="absolute right-1 top-1 rounded-lg px-3 py-1.5 text-xs font-bold text-emerald-300 hover:bg-white/10"
                        >
                            {showConfirm ? 'Hide' : 'Show'}
                        </button>
                    </div>
                    <FieldError errors={errors} name="password_confirmation" />
                </div>

                <button
                    type="submit"
                    className="w-full rounded-xl bg-emerald-600 px-4 py-2.5 text-sm font-extrabold text-white transition hover:bg-emerald-500"
                >
                    Create Account
                </button>
            </form>

            <div className="grid grid-cols-2 gap-3 text-center text-xs font-semibold">
                <a href={loginUrl} className="rounded-xl border border-white/20 px-3 py-2 text-emerald-300 hover:bg-white/10">
                    Sign In
                </a>
                <a href={pricingUrl} className="rounded-xl border border-white/20 px-3 py-2 text-emerald-300 hover:bg-white/10">
                    Compare Plans
                </a>
            </div>
        </AuthShell>
    );
};

const MarketingShell = ({ children }) => (
    <div className="min-h-screen bg-slate-950 text-white">
        <div className="absolute inset-0 bg-[radial-gradient(circle_at_top_right,_rgba(14,165,233,.25),transparent_35%),radial-gradient(circle_at_left,_rgba(16,185,129,.2),transparent_45%)]" />
        <div className="relative mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">{children}</div>
    </div>
);

const PlanCard = ({ plan, ctaLabel, ctaUrl, highlight = false }) => (
    <div className={`rounded-2xl border p-5 shadow-xl ${highlight ? 'border-cyan-400/60 bg-cyan-500/10' : 'border-white/10 bg-white/5'}`}>
        <p className="text-sm font-black uppercase tracking-wide text-cyan-300">{plan.name}</p>
        <p className="mt-3 text-4xl font-black">Rs {formatCurrency(plan.monthly_price)}</p>
        <p className="text-xs uppercase tracking-wide text-slate-300">Monthly Billing</p>
        <ul className="mt-4 space-y-2 text-sm text-slate-200">
            {(plan.features || []).slice(0, 6).map((feature) => (
                <li key={`${plan.slug}-${feature}`} className="flex items-start gap-2">
                    <span className="mt-1 h-1.5 w-1.5 rounded-full bg-emerald-300" />
                    <span>{feature}</span>
                </li>
            ))}
        </ul>
        <a
            href={ctaUrl}
            className={`mt-5 inline-flex rounded-lg px-4 py-2 text-xs font-black uppercase tracking-wide ${
                highlight ? 'bg-cyan-500 text-white hover:bg-cyan-400' : 'bg-emerald-500 text-white hover:bg-emerald-400'
            }`}
        >
            {ctaLabel}
        </a>
    </div>
);

const MarketingHomePage = ({ plans = [], routes = {} }) => {
    const spotlightPlans = useMemo(() => plans.slice(0, 3), [plans]);

    return (
        <MarketingShell>
            <header className="flex flex-wrap items-center justify-between gap-3">
                <div className="text-xl font-black tracking-tight">TSA Legacy Business OS</div>
                <div className="flex gap-2">
                    <a href={routes.pricing} className="rounded-lg border border-white/20 px-4 py-2 text-sm font-semibold hover:bg-white/10">
                        Pricing
                    </a>
                    <a href={routes.login} className="rounded-lg bg-cyan-500 px-4 py-2 text-sm font-black text-white hover:bg-cyan-400">
                        Login
                    </a>
                </div>
            </header>

            <section className="mt-14 grid gap-8 lg:grid-cols-[1.25fr_.75fr]">
                <div>
                    <p className="text-xs font-black uppercase tracking-[0.2em] text-cyan-300">Enterprise SaaS ERP</p>
                    <h1 className="mt-4 text-4xl font-black leading-tight sm:text-5xl">
                        Powerful ERP for modern Indian businesses.
                    </h1>
                    <p className="mt-5 max-w-2xl text-base text-slate-200">
                        Multi-tenant architecture, secure billing, and operations modules from inventory to accounting, all in one scalable platform.
                    </p>
                    <div className="mt-7 flex flex-wrap gap-3">
                        <a href={routes.register} className="rounded-xl bg-emerald-500 px-5 py-3 text-sm font-black text-white hover:bg-emerald-400">
                            Start Free Trial
                        </a>
                        <a href={routes.pricing} className="rounded-xl border border-white/20 px-5 py-3 text-sm font-black hover:bg-white/10">
                            Explore Plans
                        </a>
                    </div>
                </div>

                <div className="rounded-2xl border border-white/10 bg-white/5 p-5">
                    <p className="text-sm font-black uppercase tracking-wide text-cyan-300">Built for Scale</p>
                    <ul className="mt-3 space-y-3 text-sm text-slate-200">
                        <li>Tenant-wise data isolation for strong security boundaries.</li>
                        <li>Razorpay billing with webhook verification and replay protection.</li>
                        <li>Cloud Run friendly architecture with queue and cache readiness.</li>
                        <li>Plan-based feature controls for flexible SaaS monetization.</li>
                    </ul>
                </div>
            </section>

            <section className="mt-16">
                <div className="mb-5 flex items-end justify-between">
                    <h2 className="text-2xl font-black">Popular Plans</h2>
                    <a href={routes.pricing} className="text-sm font-semibold text-cyan-300 hover:text-cyan-200">
                        See full comparison
                    </a>
                </div>
                <div className="grid gap-5 md:grid-cols-3">
                    {spotlightPlans.map((plan, index) => (
                        <PlanCard
                            key={plan.slug}
                            plan={plan}
                            ctaLabel={`Choose ${plan.name}`}
                            ctaUrl={routes.register}
                            highlight={index === 1}
                        />
                    ))}
                </div>
            </section>
        </MarketingShell>
    );
};

const MarketingPricingPage = ({ plans = [], routes = {} }) => (
    <MarketingShell>
        <header className="flex flex-wrap items-center justify-between gap-3">
            <a href={routes.home} className="text-xl font-black tracking-tight">
                TSA Legacy Business OS
            </a>
            <div className="flex gap-2">
                <a href={routes.login} className="rounded-lg border border-white/20 px-4 py-2 text-sm font-semibold hover:bg-white/10">
                    Login
                </a>
                <a href={routes.register} className="rounded-lg bg-emerald-500 px-4 py-2 text-sm font-black text-white hover:bg-emerald-400">
                    Start Free Trial
                </a>
            </div>
        </header>

        <section className="mt-12 text-center">
            <p className="text-xs font-black uppercase tracking-[0.2em] text-cyan-300">Pricing</p>
            <h1 className="mt-3 text-4xl font-black sm:text-5xl">Simple plans, serious business scale.</h1>
            <p className="mx-auto mt-4 max-w-2xl text-slate-200">
                Pick the plan that matches your growth stage. Upgrade anytime without data loss.
            </p>
        </section>

        <section className="mt-10 grid gap-5 md:grid-cols-3">
            {plans.map((plan, index) => (
                <PlanCard
                    key={plan.slug}
                    plan={plan}
                    ctaLabel={`Start ${plan.name}`}
                    ctaUrl={routes.register}
                    highlight={index === 1}
                />
            ))}
        </section>
    </MarketingShell>
);

const componentMap = {
    'auth-login': LoginPage,
    'auth-register': RegisterPage,
    'marketing-home': MarketingHomePage,
    'marketing-pricing': MarketingPricingPage,
};

const rootElement = document.getElementById('tsa-react-root');

if (rootElement) {
    const pageKey = rootElement.dataset.page || '';
    const Component = componentMap[pageKey];

    if (Component) {
        const props = readProps();
        createRoot(rootElement).render(<Component {...props} />);
    }
}
