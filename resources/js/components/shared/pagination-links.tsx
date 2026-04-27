import { Button } from '@/components/ui/button';
import { Link } from '@inertiajs/react';

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

export function PaginationLinks({ links = [] }: { links?: PaginationLink[] }) {
    if (links.length <= 3) {
        return null;
    }

    return (
        <div className="flex flex-wrap items-center gap-2">
            {links.map((link) => (
                <Button key={link.label} variant={link.active ? 'default' : 'outline'} size="sm" disabled={!link.url} asChild={Boolean(link.url)}>
                    {link.url ? (
                        <Link href={link.url} dangerouslySetInnerHTML={{ __html: link.label }} />
                    ) : (
                        <span dangerouslySetInnerHTML={{ __html: link.label }} />
                    )}
                </Button>
            ))}
        </div>
    );
}
