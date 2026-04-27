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
      className="w-full py-6 text-lg font-bold shadow-xl hover:shadow-2xl transition-all group"
    >
      {isLoading ? "Processing..." : "Confirm & Book"}
      <ChevronLeft className="w-4 h-4 rotate-180 group-hover:translate-x-1 transition-transform" />
    </Button>
  );
};

export default ConfirmButton;