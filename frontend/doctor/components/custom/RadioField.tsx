import * as React from 'react';
import { Label } from '../ui/label';
import { RadioGroup, RadioGroupItem } from '../ui/radio-group';
import { cn } from '@/lib/utils';


interface Option {
    label: string;
    value: string;
}

interface RadioFieldProps extends Omit<React.ComponentProps<typeof RadioGroup>, 'onChange'> {
    label?: string;
    error?: string;
    value: string;
    onChange: (value: string) => void;
    options: Option[];
    direction?: 'row' | 'column';
    labelClass?: string;
    name?: string;
}

export const RadioField = React.forwardRef<HTMLDivElement, RadioFieldProps>(
    ({ label, error, options, direction = 'column', labelClass, className, value, onChange, name, ...props }, ref) => {
        const radioGroupName = name || `radio-group-${React.useId()}`;
        return (
            <div className="space-y-2">
                {label && <Label className={cn('block mb-1.5 font-normal text-base text-black', labelClass)}>{label}</Label>}

                <RadioGroup 
                    value={value} 
                    ref={ref} 
                    name={radioGroupName} 
                    className={cn(direction === 'row' ? 'flex flex-row gap-4' : 'flex flex-col gap-2', className)} 
                    onValueChange={onChange}
                    {...props}
                >
                    {options.map((option) => (
                        <div key={option.value} className="flex items-center space-x-2">
                            <RadioGroupItem value={option.value} id={option.value} />
                            <Label htmlFor={option.value} className="cursor-pointer">
                                {option.label}
                            </Label>
                        </div>
                    ))}
                </RadioGroup>

                {error && <p className="text-sm text-red-500">{error}</p>}
            </div>
        );
    },
);

RadioField.displayName = 'RadioField';