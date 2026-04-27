import type { SortOption } from '@/types/browse-doctors';

interface SortDropdownProps {
  value: SortOption;
  onChange: (value: SortOption) => void;
}

const sortOptions = [
  { value: 'highest-rated', label: 'Highest Rated' },
  { value: 'price-low-high', label: 'Price: Low to High' },
  { value: 'next-available', label: 'Next Available' },
];

const SortDropdown = ({ value, onChange }: SortDropdownProps) => {
  return (
    <div className="flex items-center gap-2">
      <span className="text-sm text-on-surface-variant">Sort by:</span>
      <select
        value={value}
        onChange={(e) => onChange(e.target.value as SortOption)}
        className="bg-transparent border-none text-sm font-bold text-primary-container p-0 focus:ring-0 cursor-pointer"
      >
        {sortOptions.map((option) => (
          <option key={option.value} value={option.value}>
            {option.label}
          </option>
        ))}
      </select>
    </div>
  );
};

export default SortDropdown;