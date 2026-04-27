// import api from "@/lib/axios";

import api from "@/lib/axios";
import { AxiosError } from "axios";

// export const updatePatientPersonalInfo = async (
//     userId: string | number,
//     payload: any
// ) => {
//     try {
//         const formData = new FormData();

//         // ✅ Sirf jo payload me hai wahi bhejo
//         Object.keys(payload).forEach((key) => {
//             if (payload[key] !== undefined && payload[key] !== null) {
//                 formData.append(key, payload[key]);
//             }
//         });

//         const { data } = await api.post(`/patient/${userId}`, formData, {
//             headers: {
//                 "Content-Type": "multipart/form-data",
//             },
//         });

//         return data?.data;
//     } catch (err: any) {
//         console.error(
//             `Error updating user ${userId}:`,
//             err.response?.data || err
//         );
//         throw err;
//     }
// };

export const updatePatientPersonalInfo = async (userId: string | number,  payload: any) => {
    try {
        let response;

        // ✅ agar avatar hai → FormData
        if (payload.avatar) {
            const formData = new FormData();

            // Object.keys(payload).forEach((key) => {
            //     if (payload[key] !== undefined && payload[key] !== null) {
            //         formData.append(key, payload[key]);
            //     }
            // });

            Object.keys(payload).forEach((key) => {
                if (
                    payload[key] !== undefined &&
                    payload[key] !== null &&
                    payload[key] !== "" &&
                    !(key === "avatar" && !(payload[key] instanceof File)) 
                ) {
                    formData.append(key, payload[key]);
                }
            });

            response = await api.post(`/patient/${userId}`, formData, {
                headers: {
                    "Content-Type": "multipart/form-data",
                },
            });

        } else {
            // ✅ NO FILE → normal JSON
            response = await api.post(`/patient/${userId}`, payload);
        }

        return response.data?.data;

    } catch (err) {
        if (err instanceof AxiosError) {
            console.error("Error:", err.response?.data || err);
        } else {
            console.error("Error:", err);
        }
        throw err;
    }
};