export function EmptyState({ title, description }: { title: string; description: string }) {
    return (
        <div className="rounded-2xl border border-dashed border-border bg-card p-10 text-center shadow-sm">
            <h3 className="text-lg font-semibold">{title}</h3>
            <p className="mt-2 text-sm text-muted-foreground">{description}</p>
        </div>
    );
}
