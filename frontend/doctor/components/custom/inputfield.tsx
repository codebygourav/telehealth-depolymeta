// components/ui/InputField.tsx
"use client";
import React from "react";
import { Input as ShadcnInput } from "@/components/ui/input";
import { RegisterOptions, useFormContext } from "react-hook-form";

type InputFieldProps = {
    name: string;
    label?: string;
    type?: string;
    required?: boolean;
    placeholder?: string;
    className?: string;
    disabled?: boolean;
    validation?: RegisterOptions;
};

const InputField: React.FC<InputFieldProps> = ({
    name,
    label,
    type = "text",
    required = false,
    placeholder,
    className = "",
    disabled = false,
    validation,
}) => {
    const {
        register,
        formState: { errors },
    } = useFormContext();

    const errorMessage = errors[name]?.message as string | undefined;

    return (
        <div className={`flex flex-col ${className}`}>
            {label && (
                <label
                    htmlFor={name}
                    className="font-source-sans text-muted-foreground text-sm font-medium mb-2"
                >
                    {label}
                    {required && (
                        <span className="text-destructive text-base font-semibold ml-0.5">
                            *
                        </span>
                    )}
                </label>
            )}

            <ShadcnInput
                {...register(name, validation)}
                id={name}
                type={type}
                placeholder={placeholder}
                disabled={disabled}
                aria-invalid={!!errorMessage}
                className={`font-source-sans bg-accent/30 text-foreground border ${errorMessage ? "border-destructive" : "border-border"
                    } focus:ring-1 focus:ring-primary focus:border-transparent ${type === 'password' ? 'pr-10' : ''}`}
            />

            {errorMessage && (
                <p className="text-destructive text-xs mt-1 font-normal">
                    {errorMessage}
                </p>
            )}
        </div>
    );
};

export default InputField;