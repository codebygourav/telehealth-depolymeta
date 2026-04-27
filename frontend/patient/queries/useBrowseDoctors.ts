import { useQuery } from "@tanstack/react-query";
import { getBrowseDoctors } from "@/api/browseDoctors";

export const browseDoctorsKeys = {
  all: ["browse-doctors"] as const,
};

export const useBrowseDoctors = () => {
  return useQuery({
    queryKey: browseDoctorsKeys.all,
    queryFn: getBrowseDoctors,
  });
};