// components/auth/AuthForm.tsx
'use client';

import { useForm, FormProvider } from 'react-hook-form';
import { zodResolver } from '@hookform/resolvers/zod';
import { z } from 'zod';
import Link from 'next/link';
import { useState } from 'react';
import { Eye, EyeOff } from 'lucide-react';
import InputField from '../custom/inputfield';

interface FieldProps {
  name: string;
  type: string;
  label: string;
  placeholder: string;
  required: boolean;
}

interface AuthFormProps<T extends Record<string, any>> {
  fields: FieldProps[];
  buttonText: string;
  onSubmit: (data: T) => Promise<void>;
  showForgotPassword?: boolean;
  alternateLink?: {
    text: string;
    href: string;
    linkText: string;
  } | null;
  defaultValues?: Partial<T>;
}

function AuthForm<T extends Record<string, any>>({
  fields,
  buttonText,
  onSubmit,
  showForgotPassword = false,
  alternateLink = null,
  defaultValues = {}
}: AuthFormProps<T>) {
  const [showPasswords, setShowPasswords] = useState<Record<string, boolean>>({});

  // Build Zod schema
  const getFieldSchema = (field: FieldProps) => {
    let schema: z.ZodTypeAny;

    if (field.type === 'email') {
      schema = z.string().email('Invalid email address');
    } else if (field.type === 'password' && field.name === 'password') {
      schema = z.string().min(6, 'Password must be at least 6 characters');
    } else {
      schema = z.string();
    }

    if (field.required && field.type !== 'email' && field.type !== 'password') {
      schema = (schema as z.ZodString).min(1, `${field.label} is required`);
    }

    if (!field.required) {
      schema = schema.optional();
    }

    return schema;
  };

  // Create schema object
  const schemaObj: Record<string, z.ZodTypeAny> = {};
  fields.forEach(field => {
    schemaObj[field.name] = getFieldSchema(field);
  });

  // Add password match validation
  const finalSchema = fields.some(f => f.name === 'confirmPassword')
    ? z.object(schemaObj).refine(data => data.password === data.confirmPassword, {
      message: "Passwords do not match",
      path: ["confirmPassword"]
    })
    : z.object(schemaObj);

  type FormValues = z.infer<typeof finalSchema>;

  const methods = useForm<FormValues>({
    resolver: zodResolver(finalSchema),
    defaultValues: fields.reduce((acc, field) => ({
      ...acc,
      [field.name]: (defaultValues as any)[field.name] || ''
    }), {}) as FormValues
  });

  const {
    handleSubmit,
    formState: { isSubmitting },
    setError,
  } = methods;

  const togglePasswordVisibility = (fieldName: string) => {
    setShowPasswords(prev => ({
      ...prev,
      [fieldName]: !prev[fieldName]
    }));
  };

  const getInputType = (field: FieldProps) => {
    if (field.type !== 'password') return field.type;
    return showPasswords[field.name] ? 'text' : 'password';
  };

  const onSubmitHandler = async (data: FormValues) => {
    try {
      await onSubmit(data as T);
    } catch (error: any) {
      setError('root', {
        type: 'manual',
        message: error.message || 'Something went wrong'
      });
    }
  };

  return (
    <FormProvider {...methods}>
      <form onSubmit={handleSubmit(onSubmitHandler)} className="space-y-5">
        {/* Root form error */}
        {methods.formState.errors.root && (
          <div className="bg-destructive/10 text-destructive p-3 rounded-lg text-sm border border-destructive/20">
            {methods.formState.errors.root.message}
          </div>
        )}

        {fields.map((field) => (
          <div key={field.name} className="relative">
            {/* ✅ Using your custom InputField */}
            <InputField
              name={field.name}
              label={field.label}
              type={getInputType(field)}
              placeholder={field.placeholder}
              required={field.required}
              disabled={isSubmitting}
            />

            {/* Password visibility toggle button - positioned absolutely */}
            {field.type === 'password' && (
              <button
                type="button"
                onClick={() => togglePasswordVisibility(field.name)}
                className="absolute right-3 top-9.5 text-muted-foreground hover:text-foreground transition-colors"
                tabIndex={-1}
              >
                {showPasswords[field.name] ? (
                  <EyeOff className="h-4 w-4" />
                ) : (
                  <Eye className="h-4 w-4" />
                )}
              </button>
            )}
          </div>
        ))}

        {/* Forgot Password Link */}
        {showForgotPassword && (
          <div className="text-right">
            <Link
              href="/auth/forgot-password"
              className="text-sm text-primary hover:text-primary/80 transition-colors"
            >
              Forgot password?
            </Link>
          </div>
        )}

        {/* Submit Button */}
        <button
          type="submit"
          disabled={isSubmitting}
          className="w-full bg-primary text-primary-foreground hover:bg-primary/90 font-medium py-2.5 rounded-lg transition duration-200 disabled:opacity-50 disabled:cursor-not-allowed"
        >
          {isSubmitting ? (
            <span className="flex items-center justify-center gap-2">
              <svg className="animate-spin h-5 w-5 text-primary-foreground" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                <circle className="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="4"></circle>
                <path className="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
              </svg>
              Processing...
            </span>
          ) : buttonText}
        </button>

        {/* Alternate Link */}
        {alternateLink && (
          <p className="text-center text-sm text-muted-foreground">
            {alternateLink.text}{' '}
            <Link
              href={alternateLink.href}
              className="text-primary hover:text-primary/80 font-medium transition-colors"
            >
              {alternateLink.linkText}
            </Link>
          </p>
        )}
      </form>
    </FormProvider>
  );
}

export default AuthForm;