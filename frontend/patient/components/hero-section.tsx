import { Button } from "@base-ui/react/button";

import { ChevronLeft } from "lucide-react";

interface HeroSectionProps {
    title: string;
    description: string;
    onBack?: () => void;
    showBackButton?: boolean;
}

const HeroSection = ({ title, description, onBack, showBackButton = false }: HeroSectionProps) => {
    return (
        <>
            <div className="container-max-width mx-auto w-full px-4 py-6 md:py-12 md:px-10 bg-secondary-menu-color rounded-xl g-border-light mb-5">
                <div className="max-w-[600px] mx-auto flex flex-col items-center justify-center">
                    <h1 className="text-2xl font-bold text-center text-black md:text-4xl">{title}</h1>
                    <p className="mt-2 text-base text-center text-gray-500 md:text-lg text-gray md:mt-4">{description}</p>
                </div>
            </div>
            {showBackButton && (
                <div className="mt-4 mb-4">
                    <Button onClick={onBack} className="bg-light-gray g-border rounded-full text-primary h-10 w-10 flex justify-center items-center">
                        <ChevronLeft className="size-5" />
                    </Button>
                </div>
            )}
        </>

    );
};

export default HeroSection;