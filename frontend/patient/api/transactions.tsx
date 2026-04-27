import { getAuthToken } from "@/lib/authToken";
import api from "@/lib/axios";
// Import your token getter function

export const fetchMyTransactions = async () => {
    const token = getAuthToken(); // Get token from storage/cookies
    const { data } = await api.get("/patient/my-transactions", {
        headers: token ? { Authorization: `Bearer ${token}` } : undefined
    });
    return data;
};

// ✅ fetch single transaction by ID
export const fetchTransactionById = async (id: string, token?: string) => {
    const url = `/patient/transactions/${id}`;

    // If token not passed, try to get it from storage
    const authToken = token || getAuthToken();
    const headers = authToken ? { Authorization: `Bearer ${authToken}` } : undefined;

    try {
        const response = await api.get(url, { headers });
        // Your API returns { success: true, data: {...}, ... }
        return response.data?.data || response.data;
    } catch (error) {
        console.error("Error fetching transaction:", error);
        throw error;
    }
};