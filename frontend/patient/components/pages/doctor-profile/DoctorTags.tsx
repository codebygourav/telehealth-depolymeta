interface DoctorTagsProps {
  specialties: string[];
}

const DoctorTags = ({ specialties }: DoctorTagsProps) => {
  return (
    <div className="flex flex-wrap justify-center md:justify-start gap-2 pt-2">
      {specialties.map((tag) => (
        <span 
          key={tag} 
          className="bg-secondary-container text-on-secondary-container px-4 py-1.5 rounded-full text-xs font-bold tracking-wide uppercase"
        >
          {tag}
        </span>
      ))}
    </div>
  );
};

export default DoctorTags;