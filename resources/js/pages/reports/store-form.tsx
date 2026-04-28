import InputError from '@/components/input-error';
import { PageHeader } from '@/components/shared/page-header';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, useForm } from '@inertiajs/react';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Stores', href: '/stores' },
    { title: 'Add Store / Warehouse', href: '#' },
];

export default function StoreForm() {
    const form = useForm({
        name: '',
        code: '',
        type: 'shop',
        region_name: '',
        is_active: true,
    });

    const submit = () => {
        form.post(route('stores.store'));
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Add Store / Warehouse" />

            <div className="space-y-6 p-4 md:p-6">
                <PageHeader title="Add Store / Warehouse" description="Create a new shop location or warehouse for inventory workflows." />

                <Card className="rounded-2xl shadow-sm">
                    <CardContent className="space-y-6 p-6">
                        <div className="grid gap-4 md:grid-cols-2">
                            <div className="grid gap-2">
                                <Label htmlFor="name">Location Name</Label>
                                <Input id="name" value={form.data.name} onChange={(event) => form.setData('name', event.target.value)} />
                                <InputError message={form.errors.name} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="code">Location Code</Label>
                                <Input id="code" value={form.data.code} onChange={(event) => form.setData('code', event.target.value)} placeholder="SHOP-DSM" />
                                <InputError message={form.errors.code} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="type">Type</Label>
                                <select
                                    id="type"
                                    value={form.data.type}
                                    onChange={(event) => form.setData('type', event.target.value)}
                                    className="h-10 rounded-md border border-input bg-background px-3 text-sm"
                                >
                                    <option value="shop">Store</option>
                                    <option value="warehouse">Warehouse</option>
                                </select>
                                <InputError message={form.errors.type} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="region_name">Region</Label>
                                <Input id="region_name" value={form.data.region_name} onChange={(event) => form.setData('region_name', event.target.value)} />
                                <InputError message={form.errors.region_name} />
                            </div>
                        </div>

                        <label className="flex items-center gap-3 rounded-xl border border-border/70 p-4 text-sm">
                            <Checkbox checked={form.data.is_active} onCheckedChange={(checked) => form.setData('is_active', checked === true)} />
                            <span>
                                <span className="block font-medium">Active location</span>
                                <span className="text-muted-foreground">Active locations can appear in transfers, dashboards, orders, and invoices.</span>
                            </span>
                        </label>
                        <InputError message={form.errors.is_active} />

                        <div className="flex justify-end gap-3">
                            <Button type="button" variant="outline" onClick={() => history.back()}>
                                Cancel
                            </Button>
                            <Button type="button" onClick={submit} disabled={form.processing}>
                                Create Location
                            </Button>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
