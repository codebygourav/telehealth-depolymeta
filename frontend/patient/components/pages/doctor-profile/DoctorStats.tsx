import { Group, History } from 'lucide-react';

interface DoctorStatsProps {
    patientsHelped: number;
    experience: number;
}

const DoctorStats = ({ patientsHelped, experience }: DoctorStatsProps) => {
    return (
        <div className='flex items-center gap-5'>

            <div className="flex-1 rounded-lg p-5 flex items-center gap-x-4 border border-[#E7E8EB] shadow-[0px_2px_4px_0px_#0000001A]">
                <div className="h-11 w-11 rounded-md flex items-center justify-center bg-[#F5F6F8]">
                    <Group className="w-8 h-8" />
                </div>
                <div>
                    <div className="text-[#1F1E1E] text-2xl font-bold">
                        {patientsHelped.toLocaleString()}+
                    </div>
                    <div className="text-[#4D4D4D] text-sm mt-1">
                        Patients Helped
                    </div>
                </div>
            </div>


            <div className="flex-1 rounded-lg p-5 flex items-center gap-x-4 border border-[#E7E8EB] shadow-[0px_2px_4px_0px_#0000001A]">
                <div className="h-11 w-11 rounded-md flex items-center justify-center bg-[#F5F6F8]">
                    <History className="w-8 h-8" />
                </div>
                <div>
                    <div className="text-[#1F1E1E] text-2xl font-bold">
                        {experience}+ Years
                    </div>
                    <div className="text-[#4D4D4D] text-sm mt-1">
                        Experience
                    </div>
                </div>
            </div>

        </div>
    );
};

export default DoctorStats;