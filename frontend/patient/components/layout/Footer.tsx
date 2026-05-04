import Link from "next/link";

export function Footer() {

    const currentYear = new Date().getFullYear();

    return (
        <footer className="container-max-width w-full mx-auto py-6 mt-auto border-t border-gray-200 px-5 sm:px-5">

            <div className="mx-auto text-sm text-center container-max-width">
                {currentYear} ©
                <Link href="https://deploymeta.com" target="_blank" rel="noopener noreferrer" className="text-primary"> Deploymeta.com</Link>
                , All Rights Reserved
            </div>
        </footer>
    );
}
