import { ChevronLeft } from 'lucide-react';
import { Button } from '@/components/ui/button';

interface ConfirmButtonProps {
  onClick: () => void;
  isLoading?: boolean;
}

const ConfirmButton = ({ onClick, isLoading = false }: ConfirmButtonProps) => {
  return (
    <Button
      onClick={onClick}
      disabled={isLoading}
      variant="default"
      size="lg"
      className="w-full btn-primary-cta"
    >
      {isLoading ? "Processing..." : "Confirm & Book"}
      <ChevronLeft className="w-4 h-4 transition-transform rotate-180 group-hover:translate-x-1" />
    </Button>
  );
};

export default ConfirmButton;