import { PageHeader } from '@/components/shared/page-header';
import { PaginationLinks } from '@/components/shared/pagination-links';
import { StatusBadge } from '@/components/shared/status-badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem, type PaginatedResponse } from '@/types';
import { Head, Link, router } from '@inertiajs/react';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Users', href: '/users' },
];

export default function UsersIndex({ users }: { users: PaginatedResponse<any> }) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Users" />

            <div className="space-y-6 p-4 md:p-6">
                <PageHeader title="Users" description="Create, scope, and control access to enterprise users." actionLabel="Create User" actionHref={route('users.create')} />

                <Card className="rounded-2xl shadow-sm">
                    <CardContent className="p-0">
                        <div className="overflow-x-auto">
                            <table className="min-w-full text-sm">
                                <thead className="bg-muted/50 text-left text-muted-foreground">
                                    <tr>
                                        <th className="px-6 py-4">Name</th>
                                        <th className="px-6 py-4">Role</th>
                                        <th className="px-6 py-4">Default Location</th>
                                        <th className="px-6 py-4">Status</th>
                                        <th className="px-6 py-4 text-right">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {users.data.map((user) => (
                                        <tr key={user.id} className="border-b border-border/60 last:border-b-0">
                                            <td className="px-6 py-4">
                                                <div className="font-medium">{user.name}</div>
                                                <div className="text-xs text-muted-foreground">{user.username} • {user.email}</div>
                                            </td>
                                            <td className="px-6 py-4">{user.role?.display_name}</td>
                                            <td className="px-6 py-4">{user.default_location?.name ?? 'Unassigned'}</td>
                                            <td className="px-6 py-4">
                                                <StatusBadge status={user.is_active ? 'active' : 'inactive'} />
                                            </td>
                                            <td className="px-6 py-4">
                                                <div className="flex justify-end gap-2">
                                                    <Button asChild variant="outline" size="sm">
                                                        <Link href={route('users.edit', user.id)}>Edit</Link>
                                                    </Button>
                                                    <Button
                                                        variant="ghost"
                                                        size="sm"
                                                        onClick={() => router.post(route('users.toggle-active', user.id))}
                                                    >
                                                        {user.is_active ? 'Deactivate' : 'Activate'}
                                                    </Button>
                                                </div>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </CardContent>
                </Card>

                <PaginationLinks links={users.links} />
            </div>
        </AppLayout>
    );
}
