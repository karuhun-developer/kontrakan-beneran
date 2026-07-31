import { Form, Head } from '@inertiajs/react';
import InputError from '@/components/input-error';
import PasskeyVerify from '@/components/passkey-verify';
import PasswordInput from '@/components/password-input';
import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { register } from '@/routes';
import { store } from '@/routes/login';
import { request } from '@/routes/password';

type DemoAccount = {
    role: string;
    email: string;
    password: string;
};

type Props = {
    status?: string;
    canResetPassword: boolean;
    demo?: DemoAccount[] | null;
};

export default function Login({ status, canResetPassword, demo }: Props) {
    const fillDemo = (account: DemoAccount) => {
        const email = document.getElementById(
            'email',
        ) as HTMLInputElement | null;
        const password = document.getElementById(
            'password',
        ) as HTMLInputElement | null;

        if (email) {
            email.value = account.email;
        }

        if (password) {
            password.value = account.password;
        }

        email?.focus();
    };

    return (
        <>
            <Head title="Log in" />

            <PasskeyVerify />

            {demo && demo.length > 0 && (
                <div className="mb-6 rounded-lg border border-border bg-muted/40 p-3 text-sm">
                    <p className="mb-2 font-medium">
                        Akun demo — klik untuk mengisi
                    </p>
                    <div className="flex flex-col gap-1.5">
                        {demo.map((account) => (
                            <button
                                key={account.email}
                                type="button"
                                onClick={() => fillDemo(account)}
                                className="flex items-center justify-between gap-2 rounded-md border border-border/60 bg-background px-3 py-2 text-left transition hover:border-primary/50 hover:bg-accent"
                            >
                                <span>
                                    <span className="font-medium">
                                        {account.role}
                                    </span>
                                    <span className="block text-xs text-muted-foreground">
                                        {account.email}
                                    </span>
                                </span>
                                <span className="text-xs text-muted-foreground">
                                    {account.password}
                                </span>
                            </button>
                        ))}
                    </div>
                </div>
            )}

            <Form
                {...store.form()}
                resetOnSuccess={['password']}
                className="flex flex-col gap-6"
            >
                {({ processing, errors }) => (
                    <>
                        <div className="grid gap-6">
                            <div className="grid gap-2">
                                <Label htmlFor="email">Email address</Label>
                                <Input
                                    id="email"
                                    type="email"
                                    name="email"
                                    required
                                    autoFocus
                                    tabIndex={1}
                                    autoComplete="email"
                                    placeholder="email@example.com"
                                />
                                <InputError message={errors.email} />
                            </div>

                            <div className="grid gap-2">
                                <div className="flex items-center">
                                    <Label htmlFor="password">Password</Label>
                                    {canResetPassword && (
                                        <TextLink
                                            href={request()}
                                            className="ml-auto text-sm"
                                            tabIndex={5}
                                        >
                                            Forgot your password?
                                        </TextLink>
                                    )}
                                </div>
                                <PasswordInput
                                    id="password"
                                    name="password"
                                    required
                                    tabIndex={2}
                                    autoComplete="current-password"
                                    placeholder="Password"
                                />
                                <InputError message={errors.password} />
                            </div>

                            <div className="flex items-center space-x-3">
                                <Checkbox
                                    id="remember"
                                    name="remember"
                                    tabIndex={3}
                                />
                                <Label htmlFor="remember">Remember me</Label>
                            </div>

                            <Button
                                type="submit"
                                className="mt-4 w-full"
                                tabIndex={4}
                                disabled={processing}
                                data-test="login-button"
                            >
                                {processing && <Spinner />}
                                Log in
                            </Button>
                        </div>

                        <div className="text-center text-sm text-muted-foreground">
                            Don't have an account?{' '}
                            <TextLink href={register()} tabIndex={5}>
                                Sign up
                            </TextLink>
                        </div>
                    </>
                )}
            </Form>

            {status && (
                <div className="mb-4 text-center text-sm font-medium text-green-600">
                    {status}
                </div>
            )}
        </>
    );
}

Login.layout = {
    title: 'Log in to your account',
    description: 'Enter your email and password below to log in',
};
