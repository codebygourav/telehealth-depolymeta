import axiosInstance from "@/lib/axios";
import type { GetTransactionsResponse } from "@/types/transactions";

export interface GetTransactionsParams {
  page?: number;
  per_page?: number;
  search?: string;
}

export const getTransactions = async ({
  page = 1,
  per_page = 10,
  search = "",
}: GetTransactionsParams = {}): Promise<GetTransactionsResponse> => {
  const response = await axiosInstance.get<GetTransactionsResponse>(
    "/patient/my-transactions",
    {
      params: {
        page,
        per_page,
        ...(search ? { search } : {}),
      },
    }
  );

  return response.data;
};