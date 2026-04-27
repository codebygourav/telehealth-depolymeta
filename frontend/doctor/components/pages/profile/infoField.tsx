interface InfoFieldProps {
  label: string;
  value?: string | null;
}

export default function InfoField({ label, value }: InfoFieldProps) {
  return (
    <div>
      <p className="text-sm text-muted-foreground mb-1">{label}</p>
      <p className="font-medium">{value || "-"}</p>
    </div>
  );
}