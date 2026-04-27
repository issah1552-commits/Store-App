import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { cn } from '@/lib/utils';
import { Head, Link, useForm } from '@inertiajs/react';
import { Eye, EyeOff, LoaderCircle, Lock, UserRound } from 'lucide-react';
import { FormEventHandler, useState } from 'react';

type LoginForm = {
    login: string;
    password: string;
    remember: boolean;
    _token: string;
};

interface LoginProps {
    status?: string;
    canResetPassword: boolean;
}

const fieldClasses =
    'h-11 rounded-full border border-white/12 bg-white/12 pl-11 pr-11 text-sm text-white shadow-[inset_0_1px_0_rgba(255,255,255,0.16),0_12px_24px_rgba(8,15,30,0.16)] backdrop-blur-md transition-all duration-200 placeholder:text-white/80 focus:border-white/30 focus:bg-white/16 focus-visible:ring-2 focus-visible:ring-white/15 focus-visible:ring-offset-0';

const csrfToken = typeof document !== 'undefined' ? document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '' : '';

export default function Login({ status, canResetPassword }: LoginProps) {
    const [showPassword, setShowPassword] = useState(false);
    const loginUrl = route('login', undefined, false);
    const forgotPasswordUrl = canResetPassword ? route('password.request', undefined, false) : null;

    const { data, setData, post, processing, errors, reset } = useForm<LoginForm>({
        login: '',
        password: '',
        remember: false,
        _token: csrfToken,
    });

    const submit: FormEventHandler = (event) => {
        event.preventDefault();

        post(loginUrl, {
            onFinish: () => reset('password'),
        });
    };

    return (
        <>
            <Head title="Log In" />

            <div className="relative min-h-screen overflow-hidden bg-[#111827] text-white">
                <div
                    className="absolute inset-0 bg-cover bg-center bg-no-repeat"
                    style={{ backgroundImage: "url('/images/mountain.jpg')" }}
                />
                <div className="absolute inset-0 bg-[linear-gradient(to_bottom,rgba(143,102,97,0.52)_0%,rgba(197,146,95,0.28)_34%,rgba(17,28,49,0.32)_58%,rgba(7,12,24,0.62)_100%)]" />
                <div className="absolute inset-0 bg-[radial-gradient(circle_at_top,rgba(255,237,213,0.22),transparent_38%)]" />

                <div className="relative flex min-h-screen flex-col px-4 py-6 sm:px-6">
                    <div className="flex flex-1 items-center justify-center">
                        <div className="w-full max-w-[19rem]">
                            <div className="flex justify-center">
                                <div className="flex h-16 w-16 items-center justify-center rounded-full border border-white/45 bg-white/14 shadow-[0_14px_30px_rgba(0,0,0,0.28),inset_0_1px_0_rgba(255,255,255,0.5)] backdrop-blur-md">
                                    <UserRound className="h-8 w-8 text-white" />
                                </div>
                            </div>

                            {status && (
                                <div className="mt-5 rounded-full border border-white/15 bg-white/10 px-4 py-2 text-center text-sm text-white/90 backdrop-blur-md">
                                    {status}
                                </div>
                            )}

                            <form onSubmit={submit} className="mt-6 space-y-3">
                                <div className="space-y-1.5">
                                    <Label htmlFor="login" className="sr-only">
                                        Username
                                    </Label>
                                    <div className="relative">
                                        <UserRound className="pointer-events-none absolute left-4 top-1/2 h-4 w-4 -translate-y-1/2 text-white/85" />
                                        <Input
                                            id="login"
                                            type="text"
                                            required
                                            autoFocus
                                            tabIndex={1}
                                            autoComplete="username"
                                            value={data.login}
                                            onChange={(event) => setData('login', event.target.value)}
                                            placeholder="Username"
                                            className={fieldClasses}
                                        />
                                    </div>
                                    <InputError message={errors.login} className="pl-3 text-xs text-rose-100 dark:text-rose-100" />
                                </div>

                                <div className="space-y-1.5">
                                    <Label htmlFor="password" className="sr-only">
                                        Password
                                    </Label>
                                    <div className="relative">
                                        <Lock className="pointer-events-none absolute left-4 top-1/2 h-4 w-4 -translate-y-1/2 text-white/85" />
                                        <Input
                                            id="password"
                                            type={showPassword ? 'text' : 'password'}
                                            required
                                            tabIndex={2}
                                            autoComplete="current-password"
                                            value={data.password}
                                            onChange={(event) => setData('password', event.target.value)}
                                            placeholder="Password"
                                            className={fieldClasses}
                                        />
                                        <button
                                            type="button"
                                            onClick={() => setShowPassword((value) => !value)}
                                            className="absolute inset-y-0 right-3 flex items-center rounded-full px-1 text-white/70 transition hover:text-white focus:outline-none focus:ring-2 focus:ring-white/20"
                                            aria-label={showPassword ? 'Hide password' : 'Show password'}
                                            aria-pressed={showPassword}
                                            tabIndex={3}
                                        >
                                            {showPassword ? <EyeOff className="h-4 w-4" /> : <Eye className="h-4 w-4" />}
                                        </button>
                                    </div>
                                    <InputError message={errors.password} className="pl-3 text-xs text-rose-100 dark:text-rose-100" />
                                </div>

                                <Button
                                    type="submit"
                                    disabled={processing}
                                    tabIndex={4}
                                    className={cn(
                                        'mt-1 h-11 w-full rounded-full border-0 bg-[#d95f71] text-sm font-semibold text-white shadow-[0_16px_30px_rgba(113,30,44,0.38)] transition-all duration-200 hover:bg-[#d25668] hover:shadow-[0_18px_34px_rgba(113,30,44,0.42)] focus-visible:ring-white/20',
                                        processing && 'hover:bg-[#d95f71]',
                                    )}
                                >
                                    {processing ? <LoaderCircle className="h-4 w-4 animate-spin" /> : null}
                                    Log In
                                </Button>
                            </form>

                            {canResetPassword && (
                                <div className="mt-4 text-center">
                                    <Link
                                        href={forgotPasswordUrl ?? '#'}
                                        className="text-sm text-white/88 transition hover:text-white"
                                        tabIndex={5}
                                    >
                                        Forgot password?
                                    </Link>
                                </div>
                            )}
                        </div>
                    </div>
                </div>
            </div>
        </>
    );
}
