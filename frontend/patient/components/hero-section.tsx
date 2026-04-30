interface HeroSectionProps {
    title: string;
    description: string;
}

const HeroSection = ({ title, description }: HeroSectionProps) => {
    return (
        <div className="max-w-1440 w-full py-6 md:py-12 px-4 md:px-10 mx-auto bg-secondary-menu-color rounded-xl border border-solid border-light-gray">
            <div className="max-w-[600px] mx-auto flex flex-col items-center justify-center">
                <h1 className="text-2xl md:text-4xl font-bold text-black text-center">{title}</h1>
                <p className="text-base md:text-lg text-gray-500 text-center text-gray mt-2 md:mt-4">{description}</p>
            </div>
        </div>
    );
};

export default HeroSection;