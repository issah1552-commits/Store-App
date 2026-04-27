import InputError from '@/components/input-error';
import { PasswordField } from '@/components/forms/password-field';
import { PageHeader } from '@/components/shared/page-header';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, useForm } from '@inertiajs/react';

export default function UserForm({ mode, roles, locations, userRecord }: { mode: 'create' | 'edit'; roles: any[]; locations: any[]; userRecord?: any }) {
    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Users', href: '/users' },
        { title: mode === 'create' ? 'Create User' : 'Edit User', href: '#' },
    ];

    const form = useForm({
        name: userRecord?.name ?? '',
        username: userRecord?.username ?? '',
        email: userRecord?.email ?? '',
        phone: userRecord?.phone ?? '',
        role_id: String(userRecord?.role_id ?? userRecord?.role?.id ?? ''),
        default_location_id: String(userRecord?.default_location_id ?? userRecord?.default_location?.id ?? ''),
        location_ids: userRecord?.assigned_locations?.map((location: any) => location.id) ?? [],
        password: '',
        password_confirmation: '',
        is_active: userRecord?.is_active ?? true,
    });

    const toggleLocation = (locationId: number, checked: boolean) => {
        form.setData(
            'location_ids',
            checked ? [...form.data.location_ids, locationId] : form.data.location_ids.filter((id) => id !== locationId),
        );
    };

    const submit = () => {
        if (mode === 'create') {
            form.post(route('users.store'));
            return;
        }

        form.put(route('users.update', userRecord.id));
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={mode === 'create' ? 'Create User' : 'Edit User'} />

            <div className="space-y-6 p-4 md:p-6">
                <PageHeader title={mode === 'create' ? 'Create User' : 'Edit User'} description="Assign role, region scope, and secure credentials." />

                <Card className="rounded-2xl shadow-sm">
                    <CardContent className="space-y-6 p-6">
                        <div className="grid gap-4 md:grid-cols-2">
                            <div className="grid gap-2">
                                <Label htmlFor="name">Full Name</Label>
                                <Input id="name" value={form.data.name} onChange={(event) => form.setData('name', event.target.value)} />
                                <InputError message={form.errors.name} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="username">Username</Label>
                                <Input id="username" value={form.data.username} onChange={(event) => form.setData('username', event.target.value)} />
                                <InputError message={form.errors.username} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="email">Email</Label>
                                <Input id="email" type="email" value={form.data.email} onChange={(event) => form.setData('email', event.target.value)} />
                                <InputError message={form.errors.email} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="phone">Phone</Label>
                                <Input id="phone" value={form.data.phone} onChange={(event) => form.setData('phone', event.target.value)} />
                                <InputError message={form.errors.phone} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="role_id">Role</Label>
                                <select
                                    id="role_id"
                                    value={form.data.role_id}
                                    onChange={(event) => form.setData('role_id', event.target.value)}
                                    className="h-10 rounded-md border border-input bg-background px-3 text-sm"
                                >
                                    <option value="">Select role</option>
                                    {roles.map((role) => (
                                        <option key={role.id} value={role.id}>
                                            {role.display_name}
                                        </option>
                                    ))}
                                </select>
                                <InputError message={form.errors.role_id} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="default_location_id">Default Location</Label>
                                <select
                                    id="default_location_id"
                                    value={form.data.default_location_id}
                                    onChange={(event) => form.setData('default_location_id', event.target.value)}
                                    className="h-10 rounded-md border border-input bg-background px-3 text-sm"
                                >
                                    <option value="">Select default location</option>
                                    {locations.map((location) => (
                                        <option key={location.id} value={location.id}>
                                            {location.name} ({location.code})
                                        </option>
                                    ))}
                                </select>
                                <InputError message={form.errors.default_location_id} />
                            </div>
                        </div>

                        <div className="grid gap-4 md:grid-cols-2">
                            <PasswordField
                                id="password"
                                label={mode === 'create' ? 'Password' : 'New Password'}
                                value={form.data.password}
                                onChange={(value) => form.setData('password', value)}
                                autoComplete="new-password"
                                placeholder="Enter a secure password"
                            />
                            <PasswordField
                                id="password_confirmation"
                                label="Confirm Password"
                                value={form.data.password_confirmation}
                                onChange={(value) => form.setData('password_confirmation', value)}
                                autoComplete="new-password"
                                placeholder="Confirm password"
                            />
                        </div>
                        <InputError message={form.errors.password} />
                        <InputError message={form.errors.password_confirmation} />

                        <div className="space-y-4">
                            <div className="flex items-center justify-between">
                                <div>
                                    <h2 className="text-lg font-semibold">Location Scope</h2>
                                    <p className="text-sm text-muted-foreground">Users can only see the Tanzanian regions assigned to them.</p>
                                </div>
                                <label className="flex items-center gap-2 text-sm">
                                    <Checkbox checked={form.data.is_active} onCheckedChange={(checked) => form.setData('is_active', checked === true)} />
                                    Active user
                                </label>
                            </div>

                            <div className="grid gap-3 md:grid-cols-2 xl:grid-cols-3">
                                {locations.map((location) => (
                                    <label key={location.id} className="flex items-center gap-3 rounded-xl border border-border/70 p-4 text-sm">
                                        <Checkbox
                                            checked={form.data.location_ids.includes(location.id)}
                                            onCheckedChange={(checked) => toggleLocation(location.id, checked === true)}
                                        />
                                        <div>
                                            <div className="font-medium">{location.name}</div>
                                            <div className="text-xs text-muted-foreground">{location.code} - {location.type}</div>
                                        </div>
                                    </label>
                                ))}
                            </div>
                            <InputError message={form.errors.location_ids} />
                        </div>

                        <div className="flex justify-end gap-3">
                            <Button type="button" variant="outline" onClick={() => history.back()}>
                                Cancel
                            </Button>
                            <Button type="button" onClick={submit} disabled={form.processing}>
                                {mode === 'create' ? 'Create User' : 'Save User'}
                            </Button>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
