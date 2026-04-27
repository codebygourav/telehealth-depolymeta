"use client";

import React from "react";
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
  SelectGroup,
  SelectLabel,
  SelectSeparator,
} from "@/components/ui/select";
import { useFormContext, Controller, RegisterOptions } from "react-hook-form";
import { cn } from "@/lib/utils";

export type SelectOption = {
  label: string;
  value: string;
  disabled?: boolean;
};

export type SelectOptionGroup = {
  label: string;
  options: SelectOption[];
};

type BaseSelectFieldProps = {
  name: string;
  label?: string;
  required?: boolean;
  placeholder?: string;
  className?: string;
  triggerClassName?: string;
  contentClassName?: string;
  disabled?: boolean;
  error?: string;
  size?: "sm" | "default";
  description?: string;
};

type FormSelectFieldProps = BaseSelectFieldProps & {
  options: SelectOption[];
  groups?: never;
  validation?: RegisterOptions;
  value?: never;
  onChange?: never;
  control?: never;
};

type GroupedFormSelectFieldProps = BaseSelectFieldProps & {
  options?: never;
  groups: SelectOptionGroup[];
  validation?: RegisterOptions;
  value?: never;
  onChange?: never;
  control?: never;
};

type ControlledSelectFieldProps = BaseSelectFieldProps & {
  options: SelectOption[];
  groups?: never;
  value: string;
  onChange: (value: string) => void;
  validation?: never;
  control?: never;
};

type GroupedControlledSelectFieldProps = BaseSelectFieldProps & {
  options?: never;
  groups: SelectOptionGroup[];
  value: string;
  onChange: (value: string) => void;
  validation?: never;
  control?: never;
};

type SelectFieldProps =
  | FormSelectFieldProps
  | GroupedFormSelectFieldProps
  | ControlledSelectFieldProps
  | GroupedControlledSelectFieldProps;

const SelectField: React.FC<SelectFieldProps> = ({
  name,
  label,
  required = false,
  placeholder = "Select an option",
  options,
  groups,
  className = "",
  triggerClassName = "",
  contentClassName = "",
  disabled = false,
  error,
  size = "default",
  validation,
  value,
  onChange,
  description,
}) => {
  const formContext = useFormContext();
  const hasFormContext = !!formContext;

  const errorMessage = error || (hasFormContext ? (formContext.formState.errors[name]?.message as string | undefined) : undefined);

  const renderSelectContent = () => (
    <SelectContent position="popper" className={cn("font-source-sans", contentClassName)}>
      {groups ? (
        groups.map((group, groupIndex) => (
          <React.Fragment key={group.label}>
            <SelectGroup>
              <SelectLabel className="text-xs font-semibold text-muted-foreground">
                {group.label}
              </SelectLabel>
              {group.options.map((option, index) => (
                <SelectItem
                  key={`${group.label}-${option.value}-${index}`}
                  value={option.value}
                  disabled={option.disabled}
                  className="cursor-pointer"
                >
                  {option.label}
                </SelectItem>
              ))}
            </SelectGroup>
            {groupIndex < groups.length - 1 && <SelectSeparator />}
          </React.Fragment>
        ))
      ) : (
          options?.map((option, index) => (
          <SelectItem
            key={`${option.value}-${index}`}
            value={option.value}
            disabled={option.disabled}
            className="cursor-pointer"
          >
            {option.label}
          </SelectItem>
        ))
      )}
    </SelectContent>
  );

  const renderTrigger = (selectedValue?: string) => (
    <SelectTrigger
      size={size}
      disabled={disabled}
      className={cn(
        "font-source-sans bg-accent/30",
        errorMessage ? "border-destructive" : "border-border",
        "focus:ring-1 focus:ring-primary focus:border-transparent",
        triggerClassName
      )}
    >
      <SelectValue placeholder={placeholder} />
    </SelectTrigger>
  );

  return (
    <div className={cn("flex flex-col", className)}>
      {label && (
        <label
          htmlFor={name}
          className="font-source-sans text-muted-foreground text-sm font-medium mb-2"
        >
          {label}
          {required && (
            <span className="text-destructive text-base font-semibold ml-0.5">*</span>
          )}
        </label>
      )}

      {description && (
        <p className="text-xs text-muted-foreground mb-2">{description}</p>
      )}

      {hasFormContext && value === undefined ? (
        <Controller
          name={name}
          control={formContext.control}
          rules={validation}
          render={({ field }) => (
            <Select
              value={field.value}
              onValueChange={field.onChange}
              disabled={disabled}
              name={field.name}
            >
              {renderTrigger(field.value)}
              {renderSelectContent()}
            </Select>
          )}
        />
      ) : (
        <Select
          value={value}
          onValueChange={onChange}
          disabled={disabled}
          name={name}
        >
          {renderTrigger(value)}
          {renderSelectContent()}
        </Select>
      )}

      {errorMessage && (
        <p className="text-destructive text-xs mt-1 font-normal">{errorMessage}</p>
      )}
    </div>
  );
};

export default SelectField;
