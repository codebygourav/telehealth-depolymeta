"use client";
import React from "react";
import { Checkbox } from "@/components/ui/checkbox";
import { Label } from "@/components/ui/label";
import { useFormContext, Controller } from "react-hook-form";
import { cn } from "@/lib/utils";

type Option = {
  label: string;
  value: string;
};

type MultiSelectFieldProps = {
  name: string;
  label?: string;
  required?: boolean;
  options: Option[];
  className?: string;
  disabled?: boolean;
  direction?: "row" | "column";
  description?: string;
};

const MultiSelectField: React.FC<MultiSelectFieldProps> = ({
  name,
  label,
  required = false,
  options,
  className = "",
  disabled = false,
  direction = "column",
  description,
}) => {
  const {
    control,
    formState: { errors },
  } = useFormContext();

  const errorMessage = errors[name]?.message as string | undefined;

  return (
    <div className={`flex flex-col ${className}`}>
      {label && (
        <label className="font-source-sans text-muted-foreground text-sm font-medium mb-2">
          {label}
          {required && (
            <span className="text-destructive text-base font-semibold ml-0.5">*</span>
          )}
        </label>
      )}

      {description && (
        <p className="text-xs text-muted-foreground mb-2">{description}</p>
      )}

      <Controller
        name={name}
        control={control}
        render={({ field }) => {
          const selectedValues = (field.value as string[]) || [];

          const handleToggle = (value: string) => {
            const newValues = selectedValues.includes(value)
              ? selectedValues.filter((v) => v !== value)
              : [...selectedValues, value];
            field.onChange(newValues);
          };

          return (
            <div
              className={cn(
                "flex gap-3",
                direction === "row"
                  ? "flex-row flex-wrap"
                  : "flex-col"
              )}
            >
              {options.map((option) => (
                <div
                  key={option.value}
                  className={cn(
                    "flex items-center space-x-2 rounded-md border p-3 transition-colors",
                    selectedValues.includes(option.value)
                      ? "border-primary bg-primary/5"
                      : "border-border bg-accent/30",
                    disabled && "opacity-50 cursor-not-allowed"
                  )}
                >
                  <Checkbox
                    id={`${name}-${option.value}`}
                    checked={selectedValues.includes(option.value)}
                    onCheckedChange={() => handleToggle(option.value)}
                    disabled={disabled}
                  />
                  <Label
                    htmlFor={`${name}-${option.value}`}
                    className="cursor-pointer text-sm font-normal"
                  >
                    {option.label}
                  </Label>
                </div>
              ))}
            </div>
          );
        }}
      />

      {errorMessage && (
        <p className="text-destructive text-xs mt-1 font-normal">{errorMessage}</p>
      )}
    </div>
  );
};

export default MultiSelectField;
