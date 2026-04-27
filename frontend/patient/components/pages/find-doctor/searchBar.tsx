import { Search } from 'lucide-react';

interface SearchBarProps {
  value: string;
  onChange: (value: string) => void;
}

const SearchBar = ({ value, onChange }: SearchBarProps) => {
  return (
    <div className="relative group">
      <div className="absolute inset-y-0 left-5 flex items-center pointer-events-none text-outline-variant">
        <Search className="w-6 h-6" />
      </div>
      <input
        type="text"
        value={value}
        onChange={(e) => onChange(e.target.value)}
        placeholder="Search by name, specialty, or hospital..."
        className="w-full h-16 pl-14 pr-6 bg-surface-container-lowest border border-border rounded-2xl shadow-sm text-on-surface placeholder:text-outline-variant/60 focus:ring-2 focus:ring-surface-tint/20 focus:border-transparent transition-all text-lg font-medium"
      />
    </div>
  );
};

export default SearchBar;