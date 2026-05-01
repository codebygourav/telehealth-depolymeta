import { Search } from 'lucide-react';

interface SearchBarProps {
    value: string;
    onChange: (value: string) => void;
}

const SearchBar = ({ value, onChange }: SearchBarProps) => {
    return (
        <div className="relative group w-full md:max-w-80">
            <div className="absolute inset-y-0 left-5 flex items-center pointer-events-none text-outline-variant">
                <Search size={16} color='#4D4D4D' />
            </div>
            <input
                type="text"
                value={value}
                onChange={(e) => onChange(e.target.value)}
                placeholder="Search Doctor"
                className="max-w-80 w-full border border-light-gray rounded-md pl-12 pr-5 py-3 outline-none"
            />
        </div>
    );
};

export default SearchBar;