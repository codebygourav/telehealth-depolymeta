import axiosInstance from "@/lib/axios";
import type { GetMedicinesResponse } from "@/types/medicines";

export interface GetMedicinesParams {
  page?: number;
  per_page?: number;
  search?: string;
}

export const getMedicines = async ({
  page = 1,
  per_page = 5,
  search = "",
}: GetMedicinesParams = {}): Promise<GetMedicinesResponse> => {
  const response = await axiosInstance.get<GetMedicinesResponse>("/medicines", {
    params: {
      page,
      per_page,
      ...(search ? { search } : {}),
    },
  });

  return response.data;
};