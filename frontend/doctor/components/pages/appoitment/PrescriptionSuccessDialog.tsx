import { Button } from "@/components/ui/button";
import { Dialog, DialogContent, DialogHeader, DialogTitle } from "@/components/ui/dialog";

interface PrescriptionSuccessDialogProps {
    open: boolean;
    onClose: () => void;
}

export default function PrescriptionSuccessDialog({ open, onClose }: PrescriptionSuccessDialogProps) {
    return (
        <Dialog open={open} onOpenChange={onClose}>
            <DialogContent className="w-[90vw] max-w-sm rounded-xl">
                <DialogHeader>
                    <DialogTitle>Prescription Added</DialogTitle>
                </DialogHeader>
                <p className="text-sm text-muted-foreground">The prescription has been added successfully.</p>
                <Button onClick={onClose} className="mt-4 w-full">
                    OK
                </Button>
            </DialogContent>
        </Dialog>
    );
}
