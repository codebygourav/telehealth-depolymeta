interface HeroSectionProps {
    title: string;
    description: string;
}

const HeroSection = ({ title, description }: HeroSectionProps) => {
    return (
        <div className="container-max-width mx-auto w-full px-4 py-6 md:py-12 md:px-10 bg-[#F5F6F8] rounded-lg g-border-light mb-5">
            <div className="max-w-[600px] mx-auto flex flex-col items-center justify-center">
                <h1 className="text-2xl font-bold text-center text-black md:text-4xl">{title}</h1>
                <p className="mt-2 text-base text-center text-gray-500 md:text-lg text-gray md:mt-4">{description}</p>
            </div>
        </div>
    );
};

export default HeroSection;