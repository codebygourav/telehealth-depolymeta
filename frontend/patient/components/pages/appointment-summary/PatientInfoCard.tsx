interface PatientInfoCardProps {
  name: string;
  age: number;
  gender: string;
}

const PatientInfoCard = ({ name, age, gender }: PatientInfoCardProps) => {
  return (
    <div className="grid grid-cols-2 gap-4">
      <div className="p-4 bg-white rounded-2xl border border-outline-variant/5">
        <p className="text-[10px] font-bold text-on-surface-variant uppercase tracking-widest mb-2">
          Patient Name
        </p>
        <p className="font-bold text-primary">{name}</p>
      </div>
      <div className="p-4 bg-white rounded-2xl border border-outline-variant/5">
        <p className="text-[10px] font-bold text-on-surface-variant uppercase tracking-widest mb-2">
          Age & Gender
        </p>
        <p className="font-bold text-primary">{age} Yrs, {gender}</p>
      </div>
    </div>
  );
};

export default PatientInfoCard;