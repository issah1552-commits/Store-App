import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { cn } from '@/lib/utils';
import { Eye, EyeOff } from 'lucide-react';
import type { ComponentProps } from 'react';
import { forwardRef, useState } from 'react';

interface PasswordFieldProps extends Omit<ComponentProps<typeof Input>, 'type' | 'value' | 'onChange'> {
    id: string;
    label: string;
    value: string;
    onChange: (value: string) => void;
}

export const PasswordField = forwardRef<HTMLInputElement, PasswordFieldProps>(function PasswordField(
    { id, label, value, onChange, autoComplete, placeholder, className = '', ...props },
    ref,
) {
    const [visible, setVisible] = useState(false);

    return (
        <div className={cn('grid gap-2', className)}>
            <Label htmlFor={id}>{label}</Label>
            <div className="relative">
                <Input
                    id={id}
                    type={visible ? 'text' : 'password'}
                    value={value}
                    autoComplete={autoComplete}
                    onChange={(event) => onChange(event.target.value)}
                    placeholder={placeholder}
                    className="pr-11"
                    ref={ref}
                    {...props}
                />
                <button
                    type="button"
                    onClick={() => setVisible((current) => !current)}
                    className="absolute inset-y-0 right-3 flex items-center text-muted-foreground transition hover:text-foreground"
                    aria-label={visible ? 'Hide password' : 'Show password'}
                >
                    {visible ? <EyeOff className="h-4 w-4" /> : <Eye className="h-4 w-4" />}
                </button>
            </div>
        </div>
    );
});
