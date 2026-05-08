interface InfoFieldProps {
  label: string;
  value?: string | null;
}

export default function InfoField({ label, value }: InfoFieldProps) {
  return (
    <div>
      <p className="text-[#4D4D4D] text-sm mb-1">{label}</p>
      <p className="font-semibold text-[#4D4D4D] break-all">{value || "-"}</p>
    </div>
  );
}